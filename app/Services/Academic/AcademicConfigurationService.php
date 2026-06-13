<?php

namespace App\Services\Academic;

use App\Enums\Academic\AcademicPeriodType;
use App\Enums\Academic\CalendarScopeType;
use App\Events\AcademicConfigurationUpdated;
use App\Models\AcademicConfiguration;
use App\Models\AcademicCycle;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolDayConfiguration;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Core\AuditLogService;
use App\Services\Core\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicConfigurationService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly AcademicPeriodService $academicPeriodService,
        private readonly AcademicCalendarService $academicCalendarService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, User $actor): AcademicConfiguration
    {
        return DB::transaction(function () use ($validated, $actor): AcademicConfiguration {
            $school = $this->resolveSchool($validated, $actor);
            $scopeType = $this->resolveScopeType($validated);
            $cycleIds = $this->resolveScopeCycleIds($validated, $scopeType);
            $cycleId = $this->resolvePrimaryCycleId($scopeType, $cycleIds);

            $this->assertScopeBelongsToAcademicYear((int) $validated['academic_year_id'], $scopeType, $cycleIds);

            $this->assertUniqueScope($school->id, (int) $validated['academic_year_id'], $scopeType, $cycleIds);

            $configuration = AcademicConfiguration::query()->create([
                'school_id' => $school->id,
                'academic_year_id' => $validated['academic_year_id'],
                'scope_type' => $scopeType->value,
                'cycle_id' => $cycleId,
                'scope_signature' => $this->buildScopeSignature($scopeType, $cycleIds),
                'period_type' => $validated['period_type'],
                'period_count' => $this->resolvedPeriodCount($validated),
                'school_day_type' => $validated['school_day_type'],
                'is_active' => (bool) $validated['is_active'],
                'is_locked' => (bool) $validated['is_locked'],
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->syncCoveredCycles($configuration, $cycleIds);
            $configuration->load(['academicYear', 'cycle', 'coveredCycles']);
            $this->syncDayConfiguration($configuration, $validated, $actor, $cycleIds);
            $this->academicCalendarService->syncForConfiguration($configuration, $actor);
            $this->academicPeriodService->syncForConfiguration($configuration, $actor);

            $freshConfiguration = $configuration->fresh();

            $this->auditLogService->log(
                module: 'academic',
                action: $configuration->is_locked ? 'academic_configuration.locked' : 'academic_configuration.created',
                auditable: $configuration,
                newValues: $freshConfiguration?->toArray(),
                description: "Création de la configuration académique {$configuration->academicYear->name}.",
                user: $actor,
            );

            event(new AcademicConfigurationUpdated($freshConfiguration));

            return $configuration->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(AcademicConfiguration $configuration, array $validated, User $actor): AcademicConfiguration
    {
        return DB::transaction(function () use ($configuration, $validated, $actor): AcademicConfiguration {
            if ($configuration->is_locked && ! $actor->isAdministrator()) {
                throw ValidationException::withMessages([
                    'configuration' => __('academic.configuration.validation.locked_update_forbidden'),
                ]);
            }

            $school = $this->resolveSchool($validated, $actor);
            $scopeType = $this->resolveScopeType($validated);
            $cycleIds = $this->resolveScopeCycleIds($validated, $scopeType);
            $cycleId = $this->resolvePrimaryCycleId($scopeType, $cycleIds);
            $oldValues = $configuration->toArray();

            $this->assertScopeBelongsToAcademicYear((int) $validated['academic_year_id'], $scopeType, $cycleIds);

            if ($this->scopeChanged($configuration, $scopeType, $cycleIds) && $this->hasLinkedResources($configuration)) {
                throw ValidationException::withMessages([
                    'scope_type' => __('academic.configuration.validation.scope_change_blocked'),
                ]);
            }

            $this->assertUniqueScope($school->id, (int) $validated['academic_year_id'], $scopeType, $cycleIds, $configuration->id);

            $configuration->update([
                'school_id' => $school->id,
                'academic_year_id' => $validated['academic_year_id'],
                'scope_type' => $scopeType->value,
                'cycle_id' => $cycleId,
                'scope_signature' => $this->buildScopeSignature($scopeType, $cycleIds),
                'period_type' => $validated['period_type'],
                'period_count' => $this->resolvedPeriodCount($validated),
                'school_day_type' => $validated['school_day_type'],
                'is_active' => (bool) $validated['is_active'],
                'is_locked' => (bool) $validated['is_locked'],
                'updated_by' => $actor->id,
            ]);

            $this->syncCoveredCycles($configuration, $cycleIds);
            $configuration->load(['academicYear', 'cycle', 'coveredCycles']);
            $this->syncDayConfiguration($configuration, $validated, $actor, $cycleIds);
            $this->academicCalendarService->syncForConfiguration($configuration, $actor);
            $this->academicPeriodService->syncForConfiguration($configuration, $actor);

            $freshConfiguration = $configuration->fresh();

            $this->auditLogService->log(
                module: 'academic',
                action: $configuration->is_locked && ! $oldValues['is_locked'] ? 'academic_configuration.locked' : 'academic_configuration.updated',
                auditable: $configuration,
                oldValues: $oldValues,
                newValues: $freshConfiguration?->toArray(),
                description: "Mise à jour de la configuration académique {$configuration->academicYear->name}.",
                user: $actor,
            );

            event(new AcademicConfigurationUpdated($freshConfiguration));

            return $configuration->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function upsertForAcademicYear(AcademicYear $academicYear, array $validated, User $actor, ?int $schoolId = null): ?AcademicConfiguration
    {
        if (! isset($validated['period_type'], $validated['school_day_type'])) {
            return null;
        }

        return DB::transaction(function () use ($academicYear, $validated, $actor, $schoolId): AcademicConfiguration {
            $school = $schoolId
                ? School::query()->findOrFail($schoolId)
                : $this->resolveSchool($validated, $actor);
            $scopeType = $this->resolveScopeType($validated);
            $cycleIds = $this->resolveScopeCycleIds($validated, $scopeType);
            $cycleId = $this->resolvePrimaryCycleId($scopeType, $cycleIds);

            $this->assertScopeBelongsToAcademicYear($academicYear->id, $scopeType, $cycleIds);

            $configuration = AcademicConfiguration::query()->firstOrNew([
                'school_id' => $school->id,
                'academic_year_id' => $academicYear->id,
                'scope_signature' => $this->buildScopeSignature($scopeType, $cycleIds),
            ]);

            if ($configuration->exists && $configuration->is_locked && ! $actor->isAdministrator()) {
                throw ValidationException::withMessages([
                    'period_type' => __('academic.configuration.validation.locked_update_forbidden'),
                ]);
            }

            $this->assertUniqueScope($school->id, $academicYear->id, $scopeType, $cycleIds, $configuration->exists ? $configuration->id : null);

            $configuration->fill([
                'school_id' => $school->id,
                'academic_year_id' => $academicYear->id,
                'scope_type' => $scopeType->value,
                'cycle_id' => $cycleId,
                'scope_signature' => $this->buildScopeSignature($scopeType, $cycleIds),
                'period_type' => $validated['period_type'],
                'period_count' => $this->resolvedPeriodCount($validated),
                'school_day_type' => $validated['school_day_type'],
                'is_active' => (bool) ($validated['configuration_is_active'] ?? true),
                'is_locked' => (bool) ($validated['configuration_is_locked'] ?? false),
                'created_by' => $configuration->created_by ?: $actor->id,
                'updated_by' => $actor->id,
            ]);
            $configuration->save();

            $this->syncCoveredCycles($configuration, $cycleIds);
            $configuration->load(['academicYear', 'cycle', 'coveredCycles']);
            $this->syncDayConfiguration($configuration, $validated, $actor, $cycleIds);
            $this->academicCalendarService->syncForConfiguration($configuration, $actor);
            $this->academicPeriodService->syncForConfiguration($configuration, $actor);

            $freshConfiguration = $configuration->fresh();

            $this->auditLogService->log(
                module: 'academic',
                action: $configuration->wasRecentlyCreated ? 'academic_configuration.created_from_year' : 'academic_configuration.updated_from_year',
                auditable: $configuration,
                newValues: $freshConfiguration?->toArray(),
                description: "Synchronisation de la configuration académique depuis l'année {$academicYear->name}.",
                user: $actor,
            );

            event(new AcademicConfigurationUpdated($freshConfiguration));

            return $configuration->refresh();
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

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolvedPeriodCount(array $validated): int
    {
        return AcademicPeriodType::from((string) $validated['period_type'])->defaultCount();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncDayConfiguration(AcademicConfiguration $configuration, array $validated, User $actor, array $cycleIds): void
    {
        if ($configuration->scope_type === CalendarScopeType::Global) {
            $this->upsertDayConfiguration($configuration, $validated, $actor, CalendarScopeType::Global, null);

            return;
        }

        foreach ($cycleIds as $cycleId) {
            $this->upsertDayConfiguration($configuration, $validated, $actor, CalendarScopeType::Cycle, $cycleId);
        }
    }

    private function resolveScopeType(array $validated): CalendarScopeType
    {
        return CalendarScopeType::tryFrom((string) ($validated['scope_type'] ?? CalendarScopeType::Global->value))
            ?? CalendarScopeType::Global;
    }

    private function resolveScopeCycleIds(array $validated, CalendarScopeType $scopeType): array
    {
        if ($scopeType !== CalendarScopeType::Cycle) {
            return [];
        }

        $cycleIds = collect($validated['cycle_ids'] ?? []);

        if ($cycleIds->isEmpty() && ! empty($validated['cycle_id'])) {
            $cycleIds = collect([(int) $validated['cycle_id']]);
        }

        return $cycleIds
            ->map(fn ($cycleId) => (int) $cycleId)
            ->filter(fn (int $cycleId) => $cycleId > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function resolvePrimaryCycleId(CalendarScopeType $scopeType, array $cycleIds): ?int
    {
        if ($scopeType !== CalendarScopeType::Cycle || count($cycleIds) !== 1) {
            return null;
        }

        return $cycleIds[0];
    }

    private function buildScopeSignature(CalendarScopeType $scopeType, array $cycleIds): string
    {
        if ($scopeType !== CalendarScopeType::Cycle) {
            return 'global';
        }

        return 'cycle-group:'.implode('-', $cycleIds);
    }

    private function assertScopeBelongsToAcademicYear(int $academicYearId, CalendarScopeType $scopeType, array $cycleIds): void
    {
        if ($scopeType !== CalendarScopeType::Cycle || $cycleIds === []) {
            return;
        }

        $academicYear = AcademicYear::query()
            ->with('cycles:id')
            ->find($academicYearId);

        if (! $academicYear || collect($cycleIds)->diff($academicYear->cycles->pluck('id')->map(fn ($cycleId) => (int) $cycleId))->isNotEmpty()) {
            throw ValidationException::withMessages([
                'cycle_ids' => __('academic.configuration.validation.cycle_not_in_year'),
            ]);
        }
    }

    private function assertUniqueScope(int $schoolId, int $academicYearId, CalendarScopeType $scopeType, array $cycleIds, ?int $ignoreId = null): void
    {
        $existingConfigurations = AcademicConfiguration::query()
            ->with('coveredCycles:id')
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->get();

        if ($scopeType === CalendarScopeType::Global) {
            if ($existingConfigurations->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'scope_type' => __('academic.configuration.validation.global_conflicts_with_existing_scopes'),
                ]);
            }

            return;
        }

        foreach ($existingConfigurations as $existingConfiguration) {
            if ($existingConfiguration->scope_type === CalendarScopeType::Global) {
                throw ValidationException::withMessages([
                    'scope_type' => __('academic.configuration.validation.global_scope_already_exists'),
                ]);
            }

            if (array_intersect($cycleIds, $existingConfiguration->resolvedCoveredCycleIds()) !== []) {
                throw ValidationException::withMessages([
                    'cycle_ids' => __('academic.configuration.validation.cycle_overlap_with_existing'),
                ]);
            }
        }
    }

    private function scopeChanged(AcademicConfiguration $configuration, CalendarScopeType $scopeType, array $cycleIds): bool
    {
        $currentScopeType = $configuration->scope_type instanceof CalendarScopeType
            ? $configuration->scope_type
            : CalendarScopeType::tryFrom((string) $configuration->scope_type) ?? CalendarScopeType::Global;
        $currentCycleIds = $configuration->resolvedCoveredCycleIds();

        return $currentScopeType !== $scopeType
            || $currentCycleIds !== array_values($cycleIds);
    }

    private function hasLinkedResources(AcademicConfiguration $configuration): bool
    {
        return $configuration->periods()->exists()
            || $configuration->calendars()->exists();
    }

    private function syncCoveredCycles(AcademicConfiguration $configuration, array $cycleIds): void
    {
        $configuration->coveredCycles()->sync($configuration->scope_type === CalendarScopeType::Cycle ? $cycleIds : []);
    }

    private function upsertDayConfiguration(
        AcademicConfiguration $configuration,
        array $validated,
        User $actor,
        CalendarScopeType $scopeType,
        ?int $cycleId
    ): void {
        $dayConfiguration = SchoolDayConfiguration::query()->firstOrNew([
            'school_id' => $configuration->school_id,
            'academic_year_id' => $configuration->academic_year_id,
            'scope_type' => $scopeType->value,
            'cycle_id' => $scopeType === CalendarScopeType::Cycle ? $cycleId : null,
        ]);

        if ($dayConfiguration->exists && $dayConfiguration->is_locked && ! $actor->isAdministrator()) {
            throw ValidationException::withMessages([
                'school_day_type' => __('academic.day_config.validation.locked_update_forbidden'),
            ]);
        }

        $dayConfiguration->fill([
            'school_id' => $configuration->school_id,
            'academic_year_id' => $configuration->academic_year_id,
            'scope_type' => $scopeType->value,
            'cycle_id' => $scopeType === CalendarScopeType::Cycle ? $cycleId : null,
            'day_type' => $validated['school_day_type'],
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'morning_start_time' => $validated['morning_start_time'] ?? null,
            'morning_end_time' => $validated['morning_end_time'] ?? null,
            'afternoon_start_time' => $validated['afternoon_start_time'] ?? null,
            'afternoon_end_time' => $validated['afternoon_end_time'] ?? null,
            'break_start_time' => $validated['break_start_time'] ?? null,
            'break_end_time' => $validated['break_end_time'] ?? null,
            'is_active' => (bool) ($validated['configuration_is_active'] ?? $configuration->is_active),
            'is_locked' => (bool) ($validated['configuration_is_locked'] ?? $dayConfiguration->is_locked),
            'created_by' => $dayConfiguration->created_by ?: $actor->id,
            'updated_by' => $actor->id,
        ]);

        $dayConfiguration->save();
    }
}
