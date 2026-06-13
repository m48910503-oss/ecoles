<?php

namespace App\Services\Core;

use App\Enums\Academic\AcademicPeriodType;
use App\Enums\Academic\CalendarScopeType;
use App\Services\Academic\AcademicConfigurationService;
use App\Models\AcademicCalendar;
use App\Models\AcademicClassroom;
use App\Models\AcademicConfiguration;
use App\Models\AcademicYear;
use App\Models\SchoolDayConfiguration;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicYearService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly AcademicConfigurationService $academicConfigurationService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, User $actor): AcademicYear
    {
        return DB::transaction(function () use ($validated, $actor): AcademicYear {
            $label = $this->buildYearLabel($validated['start_date'], $validated['end_date']);
            $shouldActivate = (bool) ($validated['is_active'] ?? false);

            if ($shouldActivate && AcademicYear::query()->where('is_active', true)->exists()) {
                throw ValidationException::withMessages([
                    'is_active' => __('core.academic_years.validation.active_creation_requires_deactivation'),
                ]);
            }

            if ($shouldActivate) {
                AcademicYear::query()->update(['is_active' => false]);
            }

            $academicYear = AcademicYear::create([
                'name' => $label,
                'code' => $label,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'is_active' => $shouldActivate,
                'enrollment_open' => (bool) ($validated['enrollment_open'] ?? false),
                'multi_school_enabled' => $this->resolveMultiSchoolEnabled($validated, $actor),
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->syncCycles($academicYear, $validated);
            $this->ensureDefaultGlobalCalendar($academicYear, $actor);

            $this->auditLogService->log(
                module: 'core',
                action: $academicYear->is_active ? 'academic_year.activated' : 'academic_year.created',
                auditable: $academicYear,
                newValues: $academicYear->toArray(),
                description: "Création de l'année académique {$academicYear->name}.",
                user: $actor,
            );

            if (! empty($validated['configure_now'])) {
                $this->academicConfigurationService->upsertForAcademicYear(
                    $academicYear,
                    $validated,
                    $actor,
                    $this->schoolContext->current()->id,
                );
            }

            return $academicYear;
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(AcademicYear $academicYear, array $validated, User $actor): AcademicYear
    {
        return DB::transaction(function () use ($academicYear, $validated, $actor): AcademicYear {
            $oldValues = $academicYear->toArray();
            $label = $this->buildYearLabel($validated['start_date'], $validated['end_date']);
            $shouldActivate = (bool) ($validated['is_active'] ?? false);

            if (! $shouldActivate && $academicYear->is_active) {
                $anotherActiveYearExists = AcademicYear::query()
                    ->whereKeyNot($academicYear->id)
                    ->where('is_active', true)
                    ->exists();

                if (! $anotherActiveYearExists) {
                    throw ValidationException::withMessages([
                        'is_active' => "Une année académique active est obligatoire.",
                    ]);
                }
            }

            if (
                $academicYear->is_active
                && $academicYear->multi_school_enabled
                && array_key_exists('multi_school_enabled', $validated)
                && ! $validated['multi_school_enabled']
            ) {
                throw ValidationException::withMessages([
                    'multi_school_enabled' => __('core.academic_years.validation.multi_disable_forbidden_active'),
                ]);
            }

            if ($shouldActivate) {
                AcademicYear::query()
                    ->whereKeyNot($academicYear->id)
                    ->update(['is_active' => false]);
            }

            $academicYear->update([
                'name' => $label,
                'code' => $label,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'is_active' => $shouldActivate,
                'enrollment_open' => (bool) ($validated['enrollment_open'] ?? false),
                'multi_school_enabled' => $this->resolveMultiSchoolEnabled($validated, $actor, $academicYear),
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->syncCycles($academicYear, $validated);

            $this->auditLogService->log(
                module: 'core',
                action: $academicYear->is_active ? 'academic_year.activated' : 'academic_year.updated',
                auditable: $academicYear,
                oldValues: $oldValues,
                newValues: $academicYear->fresh()->toArray(),
                description: "Mise à jour de l'année académique {$academicYear->name}.",
                user: $actor,
            );

            if (! empty($validated['configure_now'])) {
                $this->academicConfigurationService->upsertForAcademicYear(
                    $academicYear,
                    $validated,
                    $actor,
                    $this->schoolContext->current()->id,
                );
            }

            return $academicYear->refresh();
        });
    }

    public function delete(AcademicYear $academicYear, User $actor): void
    {
        DB::transaction(function () use ($academicYear, $actor): void {
            $oldValues = $academicYear->toArray();
            $label = $academicYear->name;

            $academicYear->delete();

            $this->auditLogService->log(
                module: 'core',
                action: 'academic_year.deleted',
                oldValues: $oldValues,
                description: "Suppression de l'année académique {$label}.",
                user: $actor,
            );
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveMultiSchoolEnabled(array $validated, User $actor, ?AcademicYear $academicYear = null): bool
    {
        if (! array_key_exists('multi_school_enabled', $validated)) {
            return $academicYear?->multi_school_enabled ?? false;
        }

        if (! $actor->can('manageMultiSchool', SystemSetting::class)) {
            throw new AuthorizationException();
        }

        return (bool) $validated['multi_school_enabled'];
    }

    private function buildYearLabel(CarbonInterface|string $startDate, CarbonInterface|string $endDate): string
    {
        $startYear = ($startDate instanceof CarbonInterface ? $startDate : Carbon::parse($startDate))->year;
        $endYear = ($endDate instanceof CarbonInterface ? $endDate : Carbon::parse($endDate))->year;

        return "{$startYear}-{$endYear}";
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncCycles(AcademicYear $academicYear, array $validated): void
    {
        if (! array_key_exists('cycle_ids', $validated)) {
            return;
        }

        $currentCycleIds = $academicYear->cycles()->pluck('academic_cycles.id')->map(fn ($cycleId) => (int) $cycleId);
        $cycleIds = collect($validated['cycle_ids'])
            ->map(fn ($cycleId) => (int) $cycleId)
            ->filter(fn (int $cycleId) => $cycleId > 0)
            ->values();
        $removedCycleIds = $currentCycleIds->diff($cycleIds)->values()->all();

        $this->guardCycleRemoval($academicYear, $removedCycleIds);

        $academicYear->cycles()->sync($cycleIds->all());

        if ($removedCycleIds === []) {
            return;
        }

        $levelIdsToDetach = $academicYear->levels()
            ->whereIn('academic_levels.cycle_id', $removedCycleIds)
            ->pluck('academic_levels.id')
            ->map(fn ($levelId) => (int) $levelId)
            ->all();

        if ($levelIdsToDetach !== []) {
            $academicYear->levels()->detach($levelIdsToDetach);
        }

        $seriesIdsToDetach = $academicYear->series()
            ->whereIn('academic_series.cycle_id', $removedCycleIds)
            ->pluck('academic_series.id')
            ->map(fn ($seriesId) => (int) $seriesId)
            ->all();

        if ($seriesIdsToDetach !== []) {
            $academicYear->series()->detach($seriesIdsToDetach);
        }
    }

    /**
     * @param  array<int, int>  $removedCycleIds
     */
    private function guardCycleRemoval(AcademicYear $academicYear, array $removedCycleIds): void
    {
        if ($removedCycleIds === []) {
            return;
        }

        $hasClasses = AcademicClassroom::query()
            ->where('academic_year_id', $academicYear->id)
            ->whereHas('level', fn ($query) => $query->whereIn('cycle_id', $removedCycleIds))
            ->exists();

        $hasCalendars = AcademicCalendar::query()
            ->where('academic_year_id', $academicYear->id)
            ->whereIn('cycle_id', $removedCycleIds)
            ->exists();

        $hasConfigurations = AcademicConfiguration::query()
            ->where('academic_year_id', $academicYear->id)
            ->whereIn('cycle_id', $removedCycleIds)
            ->exists();

        $hasDayConfigurations = SchoolDayConfiguration::query()
            ->where('academic_year_id', $academicYear->id)
            ->whereIn('cycle_id', $removedCycleIds)
            ->exists();

        if ($hasClasses || $hasCalendars || $hasConfigurations || $hasDayConfigurations) {
            throw ValidationException::withMessages([
                'cycle_ids' => __('core.academic_years.validation.cycles_removal_blocked'),
            ]);
        }
    }

    public function ensureDefaultGlobalCalendar(AcademicYear $academicYear, User $actor): AcademicCalendar
    {
        $schoolId = $this->schoolContext->current()->id;

        $existingGlobalCalendar = AcademicCalendar::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYear->id)
            ->where('scope_type', CalendarScopeType::Global->value)
            ->whereNull('cycle_id')
            ->first();

        if ($existingGlobalCalendar) {
            return $existingGlobalCalendar;
        }

        return AcademicCalendar::query()->create([
            'school_id' => $schoolId,
            'academic_year_id' => $academicYear->id,
            'academic_configuration_id' => null,
            'name' => $academicYear->name,
            'description' => null,
            'scope_type' => CalendarScopeType::Global->value,
            'cycle_id' => null,
            'level_id' => null,
            'class_id' => null,
            'period_type' => AcademicPeriodType::Trimester,
            'period_count' => AcademicPeriodType::Trimester->defaultCount(),
            'is_active' => true,
            'validated_at' => null,
            'validated_by' => null,
            'created_by' => $actor->id,
        ]);
    }
}
