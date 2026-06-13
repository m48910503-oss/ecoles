<?php

namespace App\Services\Academic;

use App\Enums\Academic\AcademicPeriodType;
use App\Enums\Academic\CalendarScopeType;
use App\Events\AcademicPeriodsLocked;
use App\Models\AcademicCalendar;
use App\Models\AcademicConfiguration;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\User;
use App\Services\Core\AuditLogService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicPeriodService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, User $actor): AcademicPeriod
    {
        return DB::transaction(function () use ($validated, $actor): AcademicPeriod {
            [$calendar, $configuration] = $this->resolveContext($validated, $actor);
            $position = (int) $validated['position'];
            $name = (string) $validated['name'];
            $startDate = (string) $validated['start_date'];
            $endDate = (string) $validated['end_date'];

            $this->guardConfigurationMutability($configuration, $actor);
            $this->assertPositionCapacity($calendar, null, $position, $configuration);
            $this->assertWithinAcademicYear($calendar, $startDate, $endDate);
            $this->assertNoOverlap($calendar, $startDate, $endDate);

            if ((bool) $validated['is_active']) {
                AcademicPeriod::query()
                    ->where('academic_calendar_id', $calendar->id)
                    ->update(['is_active' => false]);
            }

            $period = AcademicPeriod::query()->create([
                'school_id' => $calendar->school_id,
                'academic_calendar_id' => $calendar->id,
                'academic_configuration_id' => $configuration?->id,
                'academic_year_id' => $calendar->academic_year_id,
                'name' => $name,
                'position' => $position,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_active' => (bool) $validated['is_active'],
                'is_locked' => false,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->auditLogService->log(
                module: 'academic',
                action: 'academic_period.created',
                auditable: $period,
                newValues: $period->fresh()->toArray(),
                description: "Création de la période {$period->name}.",
                user: $actor,
            );

            return $period->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(AcademicPeriod $period, array $validated, User $actor): AcademicPeriod
    {
        return DB::transaction(function () use ($period, $validated, $actor): AcademicPeriod {
            [$calendar, $configuration] = $this->resolveContext($validated, $actor, $period);
            $position = (int) $validated['position'];
            $name = (string) $validated['name'];
            $startDate = (string) $validated['start_date'];
            $endDate = (string) $validated['end_date'];
            $oldValues = $period->toArray();

            $this->guardPeriodMutability($period, $actor);
            $this->guardConfigurationMutability($configuration, $actor);
            $this->assertPositionCapacity($calendar, $period, $position, $configuration);
            $this->assertWithinAcademicYear($calendar, $startDate, $endDate);
            $this->assertNoOverlap($calendar, $startDate, $endDate, $period);

            if ((bool) $validated['is_active']) {
                AcademicPeriod::query()
                    ->where('academic_calendar_id', $calendar->id)
                    ->whereKeyNot($period->id)
                    ->update(['is_active' => false]);
            }

            $period->update([
                'school_id' => $calendar->school_id,
                'academic_calendar_id' => $calendar->id,
                'academic_configuration_id' => $configuration?->id,
                'academic_year_id' => $calendar->academic_year_id,
                'name' => $name,
                'position' => $position,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_active' => (bool) $validated['is_active'],
                'updated_by' => $actor->id,
            ]);

            $this->auditLogService->log(
                module: 'academic',
                action: 'academic_period.updated',
                auditable: $period,
                oldValues: $oldValues,
                newValues: $period->fresh()->toArray(),
                description: "Mise à jour de la période {$period->name}.",
                user: $actor,
            );

            return $period->refresh();
        });
    }

    public function toggleLock(AcademicPeriod $period, User $actor): AcademicPeriod
    {
        return DB::transaction(function () use ($period, $actor): AcademicPeriod {
            $calendar = $period->academicCalendar ?: null;
            $configuration = $period->configuration
                ?: ($calendar ? $this->resolveConfigurationForCalendar($calendar) : null);
            $this->guardConfigurationMutability($configuration, $actor, allowLockedConfiguration: true);
            $oldValues = $period->toArray();

            $period->update([
                'is_locked' => ! $period->is_locked,
                'updated_by' => $actor->id,
            ]);

            $freshPeriod = $period->fresh();

            $this->auditLogService->log(
                module: 'academic',
                action: $freshPeriod?->is_locked ? 'academic_period.locked' : 'academic_period.unlocked',
                auditable: $period,
                oldValues: $oldValues,
                newValues: $freshPeriod?->toArray(),
                description: ($freshPeriod?->is_locked ? 'Verrouillage' : 'Déverrouillage')." de la période {$period->name}.",
                user: $actor,
            );

            if ($freshPeriod?->is_locked) {
                event(new AcademicPeriodsLocked($freshPeriod));
            }

            return $period->refresh();
        });
    }

    /**
     * Synchronise les périodes liées à une configuration académique.
     *
     * @return Collection<int, AcademicPeriod>
     */
    public function syncForConfiguration(AcademicConfiguration $configuration, User $actor): Collection
    {
        return DB::transaction(function () use ($configuration, $actor): Collection {
            $configuration->loadMissing('academicYear');
            $calendar = $this->resolveCalendarForConfiguration($configuration, $actor);

            return $this->syncForCalendar($calendar, $actor, $configuration);
        });
    }

    /**
     * Synchronise les périodes d'un calendrier précis.
     *
     * @return Collection<int, AcademicPeriod>
     */
    public function syncForCalendar(AcademicCalendar $calendar, User $actor, ?AcademicConfiguration $configuration = null): Collection
    {
        return DB::transaction(function () use ($calendar, $actor, $configuration): Collection {
            $calendar->loadMissing('academicYear');
            $configuration ??= $this->resolveConfigurationForCalendar($calendar);
            $expectedCount = $this->resolveExpectedCount($calendar, $configuration);
            $periodType = $this->resolvePeriodType($calendar, $configuration);
            $defaultRanges = $this->defaultPeriodRanges($calendar->academicYear, $expectedCount);
            $existingPeriodsQuery = $this->periodsQueryForCalendar($calendar, $configuration);

            $existingPeriods = $existingPeriodsQuery
                ->orderBy('position')
                ->get()
                ->keyBy('position');

            foreach (range(1, $expectedCount) as $position) {
                $period = $existingPeriods->get($position);
                $range = $defaultRanges[$position] ?? null;
                $payload = [
                    'school_id' => $calendar->school_id,
                    'academic_calendar_id' => $calendar->id,
                    'academic_configuration_id' => $configuration?->id,
                    'academic_year_id' => $calendar->academic_year_id,
                    'name' => $periodType->defaultLabelFor($position),
                    'position' => $position,
                    'start_date' => optional($period?->start_date)->toDateString() ?? $range['start_date'] ?? null,
                    'end_date' => optional($period?->end_date)->toDateString() ?? $range['end_date'] ?? null,
                    'is_active' => (bool) ($period?->is_active ?? ($position === 1)),
                    'is_locked' => (bool) ($period?->is_locked ?? false),
                    'created_by' => $period?->created_by ?: $actor->id,
                    'updated_by' => $actor->id,
                ];

                if ($period) {
                    $period->update($payload);
                } else {
                    AcademicPeriod::query()->create($payload);
                }
            }

            $extraPeriodsQuery = $this->periodsQueryForCalendar($calendar, $configuration);

            $extraPeriods = $extraPeriodsQuery
                ->where('position', '>', $expectedCount)
                ->get();

            foreach ($extraPeriods as $extraPeriod) {
                if ($extraPeriod->is_locked && ! $actor->isAdministrator()) {
                    throw ValidationException::withMessages([
                        'academic_year_id' => __('academic.periods.validation.period_locked'),
                    ]);
                }

                $extraPeriod->delete();
            }

            $freshPeriods = $calendar->periods()->orderBy('position')->get();

            $this->auditLogService->log(
                module: 'academic',
                action: 'academic_periods.synced',
                auditable: $calendar,
                newValues: ['academic_year_id' => $calendar->academic_year_id, 'synced_count' => $freshPeriods->count()],
                description: "Synchronisation des périodes pour {$calendar->name}.",
                user: $actor,
            );

            return $freshPeriods;
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{0: AcademicCalendar, 1: AcademicConfiguration|null}
     */
    private function resolveContext(array $validated, User $actor, ?AcademicPeriod $period = null): array
    {
        $calendarId = (int) ($validated['academic_calendar_id'] ?? 0);
        $configurationId = (int) ($validated['academic_configuration_id'] ?? 0);

        if ($calendarId > 0) {
            $calendar = AcademicCalendar::query()->with('academicYear')->findOrFail($calendarId);
            $configuration = $this->resolveConfigurationForCalendar($calendar);

            return [$calendar, $configuration];
        }

        if ($configurationId > 0) {
            $configuration = AcademicConfiguration::query()->with('academicYear')->findOrFail($configurationId);
            $calendar = $this->resolveCalendarForConfiguration($configuration, $period?->creator ?? $actor);

            return [$calendar, $configuration];
        }

        if ($period?->academic_calendar_id) {
            $calendar = $period->academicCalendar()->with('academicYear')->first() ?? $this->resolveCalendarFromPeriod($period);
            $configuration = $period->configuration ?: $this->resolveConfigurationForCalendar($calendar);

            return [$calendar, $configuration];
        }

        if ($period?->configuration) {
            $configuration = $period->configuration()->with('academicYear')->first() ?? $period->configuration;
            $calendar = $this->resolveCalendarForConfiguration($configuration, $period->creator ?? $actor);

            return [$calendar, $configuration];
        }

        throw ValidationException::withMessages([
            'academic_calendar_id' => __('academic.periods.validation.calendar_required'),
        ]);
    }

    private function resolveCalendarFromPeriod(AcademicPeriod $period): AcademicCalendar
    {
        return AcademicCalendar::query()->with('academicYear')->findOrFail((int) $period->academic_calendar_id);
    }

    private function resolveCalendarForConfiguration(AcademicConfiguration $configuration, ?User $actor = null): AcademicCalendar
    {
        $configuration->loadMissing('academicYear', 'cycle', 'coveredCycles');
        $scopeType = $configuration->scope_type instanceof CalendarScopeType
            ? $configuration->scope_type
            : CalendarScopeType::tryFrom((string) $configuration->scope_type) ?? CalendarScopeType::Global;
        $cycleId = $scopeType === CalendarScopeType::Cycle ? $configuration->singleCoveredCycleId() : null;
        $calendarName = $this->buildCalendarName($configuration);

        $calendar = AcademicCalendar::query()
            ->where('academic_configuration_id', $configuration->id)
            ->first();

        if (! $calendar) {
            $calendar = AcademicCalendar::query()
                ->where('school_id', $configuration->school_id)
                ->where('academic_year_id', $configuration->academic_year_id)
                ->where('scope_type', $scopeType->value)
                ->when(
                    $scopeType === CalendarScopeType::Cycle,
                    fn ($query) => $cycleId !== null ? $query->where('cycle_id', $cycleId) : $query->whereRaw('1 = 0'),
                    fn ($query) => $query->whereNull('cycle_id'),
                )
                ->first();
        }

        $calendar ??= new AcademicCalendar();

        $calendar->fill([
            'school_id' => $configuration->school_id,
            'academic_year_id' => $configuration->academic_year_id,
            'academic_configuration_id' => $configuration->id,
            'name' => $calendarName,
            'description' => $calendar->description,
            'scope_type' => $scopeType->value,
            'cycle_id' => $cycleId,
            'level_id' => null,
            'class_id' => null,
            'period_type' => $configuration->period_type,
            'period_count' => $configuration->period_count,
            'is_active' => $calendar->exists ? $calendar->is_active : true,
            'validated_at' => null,
            'validated_by' => null,
            'created_by' => $calendar->created_by ?: $actor?->id,
        ]);

        $calendar->save();
        $calendar->coveredCycles()->sync($scopeType === CalendarScopeType::Cycle ? $configuration->resolvedCoveredCycleIds() : []);

        return $calendar->refresh();
    }

    private function resolveConfigurationForCalendar(AcademicCalendar $calendar): AcademicConfiguration
    {
        if ($calendar->academic_configuration_id) {
            return AcademicConfiguration::query()
                ->with(['academicYear', 'cycle', 'coveredCycles'])
                ->findOrFail($calendar->academic_configuration_id);
        }

        $scopeType = $calendar->scope_type instanceof CalendarScopeType
            ? $calendar->scope_type
            : CalendarScopeType::tryFrom((string) $calendar->scope_type) ?? CalendarScopeType::Global;
        $coveredCycleIds = $calendar->resolvedCoveredCycleIds();

        $query = AcademicConfiguration::query()
            ->with(['academicYear', 'cycle', 'coveredCycles'])
            ->where('school_id', $calendar->school_id)
            ->where('academic_year_id', $calendar->academic_year_id);

        $exactMatch = $scopeType === CalendarScopeType::Cycle && count($coveredCycleIds) > 1
            ? (clone $query)
                ->where('scope_signature', 'cycle-group:'.implode('-', $coveredCycleIds))
                ->first()
            : (clone $query)
                ->where('scope_type', $scopeType->value)
                ->when(
                    $scopeType === CalendarScopeType::Cycle,
                    fn ($builder) => $builder->where('cycle_id', $calendar->cycle_id),
                    fn ($builder) => $builder->whereNull('cycle_id'),
                )
                ->first();

        if ($exactMatch) {
            return $exactMatch;
        }

        if ($scopeType !== CalendarScopeType::Global) {
            $globalMatch = (clone $query)
                ->where('scope_type', CalendarScopeType::Global->value)
                ->whereNull('cycle_id')
                ->first();

            if ($globalMatch) {
                return $globalMatch;
            }
        }

        throw ValidationException::withMessages([
            'academic_calendar_id' => __('academic.calendars.periods.validation.configuration_required'),
        ]);
    }

    private function periodsQueryForCalendar(AcademicCalendar $calendar, ?AcademicConfiguration $configuration = null)
    {
        return AcademicPeriod::query()->where(function ($query) use ($calendar, $configuration): void {
            $query->where('academic_calendar_id', $calendar->id);

            if ($configuration?->id) {
                $query->orWhere(function ($nestedQuery) use ($configuration): void {
                    $nestedQuery
                        ->whereNull('academic_calendar_id')
                        ->where('academic_configuration_id', $configuration->id);
                });
            }
        });
    }

    private function buildCalendarName(AcademicConfiguration $configuration): string
    {
        $configuration->loadMissing('academicYear', 'cycle', 'coveredCycles');

        $scopeType = $configuration->scope_type instanceof CalendarScopeType
            ? $configuration->scope_type
            : CalendarScopeType::tryFrom((string) $configuration->scope_type) ?? CalendarScopeType::Global;

        if ($scopeType === CalendarScopeType::Cycle) {
            $reference = $configuration->scopeReferenceLabel();

            if ($reference) {
                return $configuration->academicYear->name.' - '.$reference;
            }
        }

        return $configuration->academicYear->name;
    }

    private function resolvePeriodType(AcademicCalendar $calendar, ?AcademicConfiguration $configuration = null): AcademicPeriodType
    {
        if ($calendar->period_type instanceof AcademicPeriodType) {
            return $calendar->period_type;
        }

        if ($configuration?->period_type instanceof AcademicPeriodType) {
            return $configuration->period_type;
        }

        return AcademicPeriodType::Trimester;
    }

    private function resolveExpectedCount(AcademicCalendar $calendar, ?AcademicConfiguration $configuration = null): int
    {
        if ($calendar->period_count !== null) {
            return $calendar->period_count;
        }

        if ($configuration?->period_count !== null) {
            return $configuration->period_count;
        }

        return $this->resolvePeriodType($calendar, $configuration)->defaultCount();
    }

    /**
     * @return array<int, array{start_date: string, end_date: string}>
     */
    private function defaultPeriodRanges(AcademicYear $academicYear, int $periodCount): array
    {
        if (! $academicYear->start_date || ! $academicYear->end_date || $periodCount < 1) {
            return [];
        }

        $cursor = $academicYear->start_date->copy()->startOfDay();
        $yearEnd = $academicYear->end_date->copy()->endOfDay();
        $totalDays = $cursor->diffInDays($yearEnd) + 1;
        $baseLength = intdiv($totalDays, $periodCount);
        $remainder = $totalDays % $periodCount;
        $ranges = [];

        foreach (range(1, $periodCount) as $position) {
            $length = $baseLength + ($position <= $remainder ? 1 : 0);
            $end = $cursor->copy()->addDays(max($length - 1, 0));

            $ranges[$position] = [
                'start_date' => $cursor->toDateString(),
                'end_date' => $end->toDateString(),
            ];

            $cursor = $end->copy()->addDay();
        }

        return $ranges;
    }

    private function guardConfigurationMutability(?AcademicConfiguration $configuration, User $actor, bool $allowLockedConfiguration = false): void
    {
        if (! $configuration) {
            return;
        }

        if ($configuration->is_locked && ! $allowLockedConfiguration && ! $actor->isAdministrator()) {
            throw ValidationException::withMessages([
                'academic_configuration_id' => __('academic.periods.validation.configuration_locked'),
            ]);
        }
    }

    private function guardPeriodMutability(AcademicPeriod $period, User $actor): void
    {
        if ($period->is_locked && ! $actor->isAdministrator()) {
            throw ValidationException::withMessages([
                'period' => __('academic.periods.validation.period_locked'),
            ]);
        }
    }

    private function assertPositionCapacity(AcademicCalendar $calendar, ?AcademicPeriod $period, int $position, ?AcademicConfiguration $configuration = null): void
    {
        $expectedCount = $this->resolveExpectedCount($calendar, $configuration);

        if ($position > $expectedCount) {
            throw ValidationException::withMessages([
                'position' => __('academic.periods.validation.position_out_of_range'),
            ]);
        }

        $conflictQuery = AcademicPeriod::query()
            ->where('academic_calendar_id', $calendar->id)
            ->where('position', $position);

        if ($period) {
            $conflictQuery->whereKeyNot($period->id);
        }

        if ($conflictQuery->exists()) {
            throw ValidationException::withMessages([
                'position' => __('academic.periods.validation.position_already_used'),
            ]);
        }
    }

    private function assertWithinAcademicYear(AcademicCalendar $calendar, string $startDate, string $endDate): void
    {
        $calendar->loadMissing('academicYear');
        $yearStart = $calendar->academicYear->start_date?->toDateString();
        $yearEnd = $calendar->academicYear->end_date?->toDateString();

        if (! $yearStart || ! $yearEnd) {
            return;
        }

        if ($startDate < $yearStart || $endDate > $yearEnd) {
            throw ValidationException::withMessages([
                'start_date' => __('academic.periods.validation.outside_academic_year'),
            ]);
        }
    }

    private function assertNoOverlap(AcademicCalendar $calendar, string $startDate, string $endDate, ?AcademicPeriod $period = null): void
    {
        $overlapQuery = AcademicPeriod::query()
            ->where('academic_calendar_id', $calendar->id)
            ->where(function ($query) use ($startDate, $endDate): void {
                $query
                    ->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($nestedQuery) use ($startDate, $endDate): void {
                        $nestedQuery
                            ->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            });

        if ($period) {
            $overlapQuery->whereKeyNot($period->id);
        }

        if ($overlapQuery->exists()) {
            throw ValidationException::withMessages([
                'start_date' => __('academic.periods.validation.overlap'),
            ]);
        }
    }
}
