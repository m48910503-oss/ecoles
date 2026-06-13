<?php

namespace App\Services\Academic;

use App\Enums\Academic\CalendarScopeType;
use App\Events\SchoolDayConfigurationUpdated;
use App\Models\AcademicConfiguration;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolDayConfiguration;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Core\AuditLogService;
use App\Services\Core\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SchoolDayConfigurationService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, User $actor): SchoolDayConfiguration
    {
        return DB::transaction(function () use ($validated, $actor): SchoolDayConfiguration {
            $school = $this->resolveSchool($validated, $actor);
            $this->assertUniqueScope($school->id, (int) $validated['academic_year_id'], (string) $validated['scope_type'], $validated['cycle_id'] ?? null);
            $this->assertCycleBelongsToAcademicYear((int) $validated['academic_year_id'], (string) $validated['scope_type'], $validated['cycle_id'] ?? null);

            $dayConfiguration = SchoolDayConfiguration::query()->create([
                'school_id' => $school->id,
                'academic_year_id' => $validated['academic_year_id'],
                'scope_type' => $validated['scope_type'],
                'cycle_id' => $validated['scope_type'] === CalendarScopeType::Cycle->value ? ($validated['cycle_id'] ?? null) : null,
                'day_type' => $validated['day_type'],
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'morning_start_time' => $validated['morning_start_time'] ?? null,
                'morning_end_time' => $validated['morning_end_time'] ?? null,
                'afternoon_start_time' => $validated['afternoon_start_time'] ?? null,
                'afternoon_end_time' => $validated['afternoon_end_time'] ?? null,
                'break_start_time' => $validated['break_start_time'] ?? null,
                'break_end_time' => $validated['break_end_time'] ?? null,
                'is_active' => (bool) $validated['is_active'],
                'is_locked' => false,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->syncAcademicConfigurationDayType($dayConfiguration);

            $freshConfiguration = $dayConfiguration->fresh();

            $this->auditLogService->log(
                module: 'academic',
                action: 'school_day_configuration.created',
                auditable: $dayConfiguration,
                newValues: $freshConfiguration?->toArray(),
                description: "Création d'une configuration de journée pour {$dayConfiguration->academicYear->name}.",
                user: $actor,
            );

            event(new SchoolDayConfigurationUpdated($freshConfiguration));

            return $dayConfiguration->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(SchoolDayConfiguration $dayConfiguration, array $validated, User $actor): SchoolDayConfiguration
    {
        return DB::transaction(function () use ($dayConfiguration, $validated, $actor): SchoolDayConfiguration {
            if ($dayConfiguration->is_locked && ! $actor->isAdministrator()) {
                throw ValidationException::withMessages([
                    'day_configuration' => __('academic.day_config.validation.locked_update_forbidden'),
                ]);
            }

            $school = $this->resolveSchool($validated, $actor);
            $this->assertUniqueScope(
                $school->id,
                (int) $validated['academic_year_id'],
                (string) $validated['scope_type'],
                $validated['cycle_id'] ?? null,
                $dayConfiguration
            );
            $this->assertCycleBelongsToAcademicYear((int) $validated['academic_year_id'], (string) $validated['scope_type'], $validated['cycle_id'] ?? null);

            $oldValues = $dayConfiguration->toArray();

            $dayConfiguration->update([
                'school_id' => $school->id,
                'academic_year_id' => $validated['academic_year_id'],
                'scope_type' => $validated['scope_type'],
                'cycle_id' => $validated['scope_type'] === CalendarScopeType::Cycle->value ? ($validated['cycle_id'] ?? null) : null,
                'day_type' => $validated['day_type'],
                'start_time' => $validated['start_time'] ?? null,
                'end_time' => $validated['end_time'] ?? null,
                'morning_start_time' => $validated['morning_start_time'] ?? null,
                'morning_end_time' => $validated['morning_end_time'] ?? null,
                'afternoon_start_time' => $validated['afternoon_start_time'] ?? null,
                'afternoon_end_time' => $validated['afternoon_end_time'] ?? null,
                'break_start_time' => $validated['break_start_time'] ?? null,
                'break_end_time' => $validated['break_end_time'] ?? null,
                'is_active' => (bool) $validated['is_active'],
                'updated_by' => $actor->id,
            ]);

            $this->syncAcademicConfigurationDayType($dayConfiguration->refresh());

            $freshConfiguration = $dayConfiguration->fresh();

            $this->auditLogService->log(
                module: 'academic',
                action: 'school_day_configuration.updated',
                auditable: $dayConfiguration,
                oldValues: $oldValues,
                newValues: $freshConfiguration?->toArray(),
                description: "Mise à jour d'une configuration de journée pour {$dayConfiguration->academicYear->name}.",
                user: $actor,
            );

            event(new SchoolDayConfigurationUpdated($freshConfiguration));

            return $dayConfiguration->refresh();
        });
    }

    public function toggleLock(SchoolDayConfiguration $dayConfiguration, User $actor): SchoolDayConfiguration
    {
        return DB::transaction(function () use ($dayConfiguration, $actor): SchoolDayConfiguration {
            $oldValues = $dayConfiguration->toArray();

            $dayConfiguration->update([
                'is_locked' => ! $dayConfiguration->is_locked,
                'updated_by' => $actor->id,
            ]);

            $freshConfiguration = $dayConfiguration->fresh();

            $this->auditLogService->log(
                module: 'academic',
                action: $freshConfiguration?->is_locked ? 'school_day_configuration.locked' : 'school_day_configuration.unlocked',
                auditable: $dayConfiguration,
                oldValues: $oldValues,
                newValues: $freshConfiguration?->toArray(),
                description: ($freshConfiguration?->is_locked ? 'Verrouillage' : 'Déverrouillage')." d'une configuration de journée pour {$dayConfiguration->academicYear->name}.",
                user: $actor,
            );

            event(new SchoolDayConfigurationUpdated($freshConfiguration));

            return $dayConfiguration->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveSchool(array $validated, User $actor): School
    {
        $selectedSchoolId = isset($validated['school_id']) ? (int) $validated['school_id'] : null;

        if ($selectedSchoolId && $actor->can('manageMultiSchool', SystemSetting::class)) {
            return School::query()->active()->findOrFail($selectedSchoolId);
        }

        return $this->schoolContext->current();
    }

    private function assertUniqueScope(int $schoolId, int $academicYearId, string $scopeType, mixed $cycleId, ?SchoolDayConfiguration $existing = null): void
    {
        $query = SchoolDayConfiguration::query()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->where('scope_type', $scopeType)
            ->where('cycle_id', $scopeType === CalendarScopeType::Cycle->value ? $cycleId : null);

        if ($existing) {
            $query->whereKeyNot($existing->id);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'scope_type' => __('academic.day_config.validation.duplicate_scope'),
            ]);
        }
    }

    private function assertCycleBelongsToAcademicYear(int $academicYearId, string $scopeType, mixed $cycleId): void
    {
        if ($scopeType !== CalendarScopeType::Cycle->value || ! filled($cycleId)) {
            return;
        }

        $academicYear = AcademicYear::query()
            ->with('cycles:id')
            ->find($academicYearId);

        if (! $academicYear || ! $academicYear->cycles->contains('id', (int) $cycleId)) {
            throw ValidationException::withMessages([
                'cycle_id' => __('academic.day_config.validation.cycle_not_in_year'),
            ]);
        }
    }

    private function syncAcademicConfigurationDayType(SchoolDayConfiguration $dayConfiguration): void
    {
        if (! $dayConfiguration->is_active) {
            return;
        }

        $scopeType = $dayConfiguration->scope_type instanceof CalendarScopeType
            ? $dayConfiguration->scope_type
            : CalendarScopeType::tryFrom((string) $dayConfiguration->scope_type) ?? CalendarScopeType::Global;

        $configuration = AcademicConfiguration::query()
            ->where('school_id', $dayConfiguration->school_id)
            ->where('academic_year_id', $dayConfiguration->academic_year_id)
            ->where('scope_type', $scopeType->value)
            ->when(
                $scopeType === CalendarScopeType::Cycle,
                fn ($query) => $query->where('cycle_id', $dayConfiguration->cycle_id),
                fn ($query) => $query->whereNull('cycle_id'),
            )
            ->first();

        if (! $configuration && $scopeType !== CalendarScopeType::Global) {
            $configuration = AcademicConfiguration::query()
                ->where('school_id', $dayConfiguration->school_id)
                ->where('academic_year_id', $dayConfiguration->academic_year_id)
                ->where('scope_type', CalendarScopeType::Global->value)
                ->whereNull('cycle_id')
                ->first();
        }

        if ($configuration) {
            $configuration->update([
                'school_day_type' => $dayConfiguration->day_type->value,
            ]);
        }
    }
}
