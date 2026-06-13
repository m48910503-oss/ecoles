<?php

namespace App\Services\Academic;

use App\Enums\Academic\AcademicPeriodType;
use App\Enums\Academic\CalendarScopeType;
use App\Models\AcademicCalendar;
use App\Models\AcademicClassroom;
use App\Models\AcademicConfiguration;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Core\AuditLogService;
use App\Services\Core\SchoolContext;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicCalendarService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function create(array $validated, User $actor): AcademicCalendar
    {
        return DB::transaction(function () use ($validated, $actor): AcademicCalendar {
            $school = $this->resolveSchool($validated, $actor);
            $classroom = $this->resolveClassroom($validated, $school->id);
            $periodType = $this->resolveRequestedPeriodType($validated);
            $periodCount = $periodType?->defaultCount();

            $calendar = AcademicCalendar::query()->create([
                'school_id' => $school->id,
                'academic_year_id' => $validated['academic_year_id'],
                'academic_configuration_id' => null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'scope_type' => $validated['scope_type'],
                'cycle_id' => $validated['scope_type'] === CalendarScopeType::Cycle->value ? ($validated['cycle_id'] ?? null) : null,
                'level_id' => $validated['scope_type'] === CalendarScopeType::Level->value ? ($validated['level_id'] ?? null) : null,
                'class_id' => $classroom?->id,
                'period_type' => $periodType,
                'period_count' => $periodCount,
                'is_active' => (bool) $validated['is_active'],
                'validated_at' => null,
                'validated_by' => null,
                'created_by' => $actor->id,
            ]);

            $calendar->coveredCycles()->sync(
                $validated['scope_type'] === CalendarScopeType::Cycle->value && ! empty($validated['cycle_id'])
                    ? [(int) $validated['cycle_id']]
                    : []
            );

            $this->auditLogService->log(
                module: 'academic',
                action: 'academic_calendar.created',
                auditable: $calendar,
                newValues: $calendar->fresh()->toArray(),
                description: "Création du calendrier {$calendar->name}.",
                user: $actor,
            );

            return $calendar->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function update(AcademicCalendar $calendar, array $validated, User $actor): AcademicCalendar
    {
        return DB::transaction(function () use ($calendar, $validated, $actor): AcademicCalendar {
            $oldValues = $calendar->toArray();
            $school = $this->resolveSchool($validated, $actor);
            $classroom = $this->resolveClassroom($validated, $school->id);
            $requestedPeriodType = $this->resolveRequestedPeriodType($validated);

            if ($calendar->school_id !== $school->id && ($calendar->events()->exists() || $calendar->periods()->exists())) {
                throw ValidationException::withMessages([
                    'school_id' => __('academic.calendars.validation.school_change_blocked'),
                ]);
            }

            if ($requestedPeriodType && $calendar->periods()->exists() && $calendar->period_type && $requestedPeriodType !== $calendar->period_type) {
                throw ValidationException::withMessages([
                    'period_type' => __('academic.configuration.validation.period_type_change_blocked'),
                ]);
            }

            $calendar->update([
                'school_id' => $school->id,
                'academic_year_id' => $validated['academic_year_id'],
                'academic_configuration_id' => $calendar->academic_configuration_id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'scope_type' => $validated['scope_type'],
                'cycle_id' => $validated['scope_type'] === CalendarScopeType::Cycle->value ? ($validated['cycle_id'] ?? null) : null,
                'level_id' => $validated['scope_type'] === CalendarScopeType::Level->value ? ($validated['level_id'] ?? null) : null,
                'class_id' => $classroom?->id,
                'period_type' => $requestedPeriodType ?? $calendar->period_type,
                'period_count' => $requestedPeriodType ? $requestedPeriodType->defaultCount() : $calendar->period_count,
                'is_active' => (bool) $validated['is_active'],
                'validated_at' => null,
                'validated_by' => null,
            ]);

            $calendar->coveredCycles()->sync(
                $validated['scope_type'] === CalendarScopeType::Cycle->value && ! empty($validated['cycle_id'])
                    ? [(int) $validated['cycle_id']]
                    : []
            );

            $this->auditLogService->log(
                module: 'academic',
                action: 'academic_calendar.updated',
                auditable: $calendar,
                oldValues: $oldValues,
                newValues: $calendar->fresh()->toArray(),
                description: "Mise à jour du calendrier {$calendar->name}.",
                user: $actor,
            );

            return $calendar->refresh();
        });
    }

    public function validate(AcademicCalendar $calendar, User $actor): AcademicCalendar
    {
        return DB::transaction(function () use ($calendar, $actor): AcademicCalendar {
            $oldValues = $calendar->toArray();
            $calendar->loadMissing('academicYear');
            $configuration = $this->resolveConfigurationForCalendar($calendar);
            $periods = $calendar->periods()->orderBy('position')->get();
            $expectedCount = $this->resolveExpectedPeriodCount($calendar, $configuration);

            if ($periods->isEmpty()) {
                throw ValidationException::withMessages([
                    'calendar' => __('academic.calendars.validation.periods_required'),
                ]);
            }

            if ($periods->count() !== $expectedCount) {
                throw ValidationException::withMessages([
                    'calendar' => __('academic.calendars.periods.validation.count_mismatch'),
                ]);
            }

            $this->validatePersistedPeriods($periods, $calendar->academicYear);

            $calendar->update([
                'validated_at' => now(),
                'validated_by' => $actor->id,
            ]);

            $this->auditLogService->log(
                module: 'academic',
                action: 'academic_calendar.validated',
                auditable: $calendar,
                oldValues: $oldValues,
                newValues: $calendar->fresh()->toArray(),
                description: "Validation du calendrier {$calendar->name}.",
                user: $actor,
            );

            return $calendar->refresh();
        });
    }

    public function toggleActive(AcademicCalendar $calendar, User $actor): AcademicCalendar
    {
        return DB::transaction(function () use ($calendar, $actor): AcademicCalendar {
            $oldValues = $calendar->toArray();

            $calendar->update([
                'is_active' => ! $calendar->is_active,
            ]);

            $this->auditLogService->log(
                module: 'academic',
                action: $calendar->is_active ? 'academic_calendar.enabled' : 'academic_calendar.disabled',
                auditable: $calendar,
                oldValues: $oldValues,
                newValues: $calendar->fresh()->toArray(),
                description: ($calendar->is_active ? 'Activation' : 'Désactivation')." du calendrier {$calendar->name}.",
                user: $actor,
            );

            return $calendar->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return Collection<int, AcademicPeriod>
     */
    public function generatePeriodsForYear(AcademicYear $academicYear, array $validated, User $actor): Collection
    {
        return DB::transaction(function () use ($academicYear, $validated, $actor): Collection {
            $calendar = $this->resolveTargetCalendarForPeriods($academicYear, $validated, $actor);
            $this->syncPeriodsForCalendar($calendar, $actor);

            return $calendar->periods()->orderBy('position')->get();
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return Collection<int, AcademicPeriod>
     */
    public function updateGeneratedPeriods(AcademicYear $academicYear, array $validated, User $actor): Collection
    {
        return DB::transaction(function () use ($academicYear, $validated, $actor): Collection {
            $calendar = $this->resolveTargetCalendarForPeriods($academicYear, $validated, $actor);
            $configuration = $this->resolveConfigurationForCalendar($calendar);
            $periodType = $this->resolveCalendarPeriodType($calendar, $configuration);
            $expectedCount = $this->resolveExpectedPeriodCount($calendar, $configuration);
            $payloads = collect($validated['periods'] ?? [])->values();

            if ($payloads->count() !== $expectedCount) {
                throw ValidationException::withMessages([
                    'periods' => __('academic.calendars.periods.validation.count_mismatch'),
                ]);
            }

            $this->validateGeneratedPeriods($payloads, $academicYear);

            $periods = $calendar->periods()->orderBy('position')->get();

            $periods->each(function (AcademicPeriod $period) use ($payloads, $periodType, $actor): void {
                $payload = (array) $payloads->get($period->position - 1, []);
                $name = $periodType->defaultLabelFor($period->position);

                if ($period->is_locked && ! $actor->isAdministrator()) {
                    throw ValidationException::withMessages([
                        "periods.{$period->position}.name" => __('academic.periods.validation.period_locked'),
                    ]);
                }

                $period->update([
                    'name' => $name,
                    'start_date' => $payload['start_date'],
                    'end_date' => $payload['end_date'],
                    'updated_by' => $actor->id,
                ]);
            });

            $calendar->forceFill([
                'period_type' => $calendar->period_type ?? $periodType,
                'period_count' => $calendar->period_count ?? $expectedCount,
            ])->save();

            $this->validate($calendar, $actor);

            return $calendar->periods()->orderBy('position')->get();
        });
    }

    public function syncForConfiguration(AcademicConfiguration $configuration, User $actor): AcademicCalendar
    {
        return DB::transaction(function () use ($configuration, $actor): AcademicCalendar {
            return $this->resolveCalendarForConfiguration($configuration, $actor);
        });
    }

    public function syncGlobalSchoolCalendar(AcademicConfiguration $configuration, User $actor): AcademicCalendar
    {
        return $this->syncForConfiguration($configuration, $actor);
    }

    /**
     * Synchronise les périodes d'un calendrier précis.
     *
     * @return Collection<int, AcademicPeriod>
     */
    public function syncPeriodsForCalendar(AcademicCalendar $calendar, User $actor, ?AcademicConfiguration $configuration = null): Collection
    {
        return DB::transaction(function () use ($calendar, $actor, $configuration): Collection {
            $calendar->loadMissing('academicYear');
            $configuration ??= $this->resolveConfigurationForCalendar($calendar);
            $periodType = $this->resolveCalendarPeriodType($calendar, $configuration);
            $expectedCount = $this->resolveExpectedPeriodCount($calendar, $configuration);
            $defaultRanges = $this->defaultPeriodRanges($calendar->academicYear, $expectedCount);

            if ($calendar->period_type === null || $calendar->period_count === null) {
                $calendar->forceFill([
                    'period_type' => $periodType,
                    'period_count' => $expectedCount,
                ])->save();
            }

            $existingPeriods = AcademicPeriod::query()
                ->where('academic_calendar_id', $calendar->id)
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

            $extraPeriods = AcademicPeriod::query()
                ->where('academic_calendar_id', $calendar->id)
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
    private function resolveClassroom(array $validated, int $schoolId): ?AcademicClassroom
    {
        if (($validated['scope_type'] ?? null) !== CalendarScopeType::ClassRoom->value) {
            return null;
        }

        $classroom = AcademicClassroom::query()->findOrFail($validated['class_id']);

        if ($classroom->school_id !== $schoolId) {
            throw ValidationException::withMessages([
                'class_id' => __('academic.calendars.validation.class_school_mismatch'),
            ]);
        }

        if ($classroom->academic_year_id !== (int) $validated['academic_year_id']) {
            throw ValidationException::withMessages([
                'class_id' => __('academic.calendars.validation.class_year_mismatch'),
            ]);
        }

        return $classroom;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveRequestedPeriodType(array $validated): ?AcademicPeriodType
    {
        $periodType = $validated['period_type'] ?? null;

        if (! filled($periodType)) {
            return null;
        }

        return AcademicPeriodType::from((string) $periodType);
    }

    private function resolveConfiguration(AcademicYear $academicYear, int $schoolId): AcademicConfiguration
    {
        $configuration = AcademicConfiguration::query()
            ->with(['academicYear', 'cycle'])
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYear->id)
            ->orderByDesc('is_active')
            ->orderByRaw("CASE scope_type WHEN 'global' THEN 0 WHEN 'cycle' THEN 1 ELSE 2 END")
            ->orderBy('id')
            ->first();

        if (! $configuration) {
            throw ValidationException::withMessages([
                'academic_year_id' => __('academic.calendars.periods.validation.configuration_required'),
            ]);
        }

        return $configuration;
    }

    private function resolveConfigurationForCalendar(AcademicCalendar $calendar): ?AcademicConfiguration
    {
        if ($calendar->academic_configuration_id) {
            return AcademicConfiguration::query()
                ->with(['academicYear', 'cycle', 'coveredCycles'])
                ->find($calendar->academic_configuration_id);
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

        return null;
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

    private function resolveTargetCalendarForPeriods(AcademicYear $academicYear, array $validated, User $actor): AcademicCalendar
    {
        $school = $this->resolveSchool($validated, $actor);

        if (! empty($validated['academic_calendar_id'])) {
            $calendar = AcademicCalendar::query()
                ->with('academicYear')
                ->findOrFail((int) $validated['academic_calendar_id']);

            if ($calendar->school_id !== $school->id) {
                throw ValidationException::withMessages([
                    'academic_calendar_id' => __('academic.calendars.validation.calendar_school_mismatch'),
                ]);
            }

            if ($calendar->academic_year_id !== $academicYear->id) {
                throw ValidationException::withMessages([
                    'academic_calendar_id' => __('academic.calendars.validation.calendar_year_mismatch'),
                ]);
            }

            return $calendar;
        }

        $configuration = $this->resolveConfiguration($academicYear, $school->id);

        return $this->resolveCalendarForConfiguration($configuration, $actor);
    }

    private function resolveCalendarPeriodType(AcademicCalendar $calendar, ?AcademicConfiguration $configuration = null): AcademicPeriodType
    {
        if ($calendar->period_type instanceof AcademicPeriodType) {
            return $calendar->period_type;
        }

        if ($configuration?->period_type instanceof AcademicPeriodType) {
            return $configuration->period_type;
        }

        return AcademicPeriodType::Trimester;
    }

    private function resolveExpectedPeriodCount(AcademicCalendar $calendar, ?AcademicConfiguration $configuration = null): int
    {
        if ($calendar->period_count !== null) {
            return $calendar->period_count;
        }

        if ($configuration?->period_count !== null) {
            return $configuration->period_count;
        }

        return $this->resolveCalendarPeriodType($calendar, $configuration)->defaultCount();
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

    /**
     * @param  Collection<int, array<string, mixed>>  $payloads
     */
    private function validateGeneratedPeriods(Collection $payloads, AcademicYear $academicYear): void
    {
        $ranges = [];

        foreach ($payloads as $index => $payload) {
            $position = $index + 1;
            $startDate = (string) ($payload['start_date'] ?? '');
            $endDate = (string) ($payload['end_date'] ?? '');

            if ($startDate === '' || $endDate === '') {
                throw ValidationException::withMessages([
                    "periods.{$index}.start_date" => __('academic.configuration.validation.period_dates_required'),
                ]);
            }

            if ($endDate < $startDate) {
                throw ValidationException::withMessages([
                    "periods.{$index}.end_date" => __('academic.configuration.validation.period_end_after_start'),
                ]);
            }

            if (
                $academicYear->start_date
                && $academicYear->end_date
                && ($startDate < $academicYear->start_date->toDateString() || $endDate > $academicYear->end_date->toDateString())
            ) {
                throw ValidationException::withMessages([
                    "periods.{$index}.start_date" => __('academic.periods.validation.outside_academic_year'),
                ]);
            }

            $ranges[] = [
                'index' => $index,
                'position' => $position,
                'start' => Carbon::parse($startDate)->startOfDay(),
                'end' => Carbon::parse($endDate)->endOfDay(),
            ];
        }

        foreach ($ranges as $range) {
            foreach ($ranges as $otherRange) {
                if ($range['index'] >= $otherRange['index']) {
                    continue;
                }

                if ($range['start']->lte($otherRange['end']) && $range['end']->gte($otherRange['start'])) {
                    throw ValidationException::withMessages([
                        "periods.{$otherRange['index']}.start_date" => __('academic.periods.validation.overlap'),
                    ]);
                }
            }
        }
    }

    /**
     * @param  Collection<int, AcademicPeriod>  $periods
     */
    private function validatePersistedPeriods(Collection $periods, AcademicYear $academicYear): void
    {
        $ranges = [];

        foreach ($periods->values() as $index => $period) {
            $startDate = (string) optional($period->start_date)->toDateString();
            $endDate = (string) optional($period->end_date)->toDateString();

            if ($startDate === '' || $endDate === '') {
                throw ValidationException::withMessages([
                    "periods.{$index}.start_date" => __('academic.configuration.validation.period_dates_required'),
                ]);
            }

            if ($endDate < $startDate) {
                throw ValidationException::withMessages([
                    "periods.{$index}.end_date" => __('academic.configuration.validation.period_end_after_start'),
                ]);
            }

            if (
                $academicYear->start_date
                && $academicYear->end_date
                && ($startDate < $academicYear->start_date->toDateString() || $endDate > $academicYear->end_date->toDateString())
            ) {
                throw ValidationException::withMessages([
                    "periods.{$index}.start_date" => __('academic.periods.validation.outside_academic_year'),
                ]);
            }

            $ranges[] = [
                'index' => $index,
                'start' => Carbon::parse($startDate)->startOfDay(),
                'end' => Carbon::parse($endDate)->endOfDay(),
            ];
        }

        foreach ($ranges as $range) {
            foreach ($ranges as $otherRange) {
                if ($range['index'] >= $otherRange['index']) {
                    continue;
                }

                if ($range['start']->lte($otherRange['end']) && $range['end']->gte($otherRange['start'])) {
                    throw ValidationException::withMessages([
                        "periods.{$otherRange['index']}.start_date" => __('academic.periods.validation.overlap'),
                    ]);
                }
            }
        }
    }
}
