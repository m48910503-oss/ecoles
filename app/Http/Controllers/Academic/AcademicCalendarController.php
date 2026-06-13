<?php

namespace App\Http\Controllers\Academic;

use App\Enums\Academic\AcademicCycleCategory;
use App\Enums\Academic\AcademicPeriodType;
use App\Enums\Academic\CalendarScopeType;
use App\Enums\Academic\CalendarEventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\GenerateAcademicCalendarPeriodsRequest;
use App\Http\Requests\Academic\StoreAcademicCalendarRequest;
use App\Http\Requests\Academic\UpdateAcademicCalendarPeriodsRequest;
use App\Http\Requests\Academic\UpdateAcademicCalendarRequest;
use App\Models\AcademicCalendar;
use App\Models\AcademicClassroom;
use App\Models\AcademicConfiguration;
use App\Models\AcademicCycle;
use App\Models\AcademicLevel;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\CalendarEvent;
use App\Models\School;
use App\Models\SystemSetting;
use App\Services\Academic\AcademicCalendarService;
use App\Services\Core\SchoolContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicCalendarController extends Controller
{
    public function __construct(
        private readonly AcademicCalendarService $academicCalendarService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AcademicCalendar::class);

        $search = trim((string) $request->string('search'));
        $academicYearId = $request->integer('academic_year_id') ?: null;
        $scopeType = trim((string) $request->string('scope_type'));
        $status = trim((string) $request->string('status'));
        $schoolOptions = School::query()->active()->orderBy('name')->get(['id', 'name']);
        $canChooseSchool = $this->canChooseSchool($request, $schoolOptions->count());
        $schoolId = $canChooseSchool
            ? ($request->integer('school_id') ?: null)
            : $this->schoolContext->current()->id;

        $baseQuery = AcademicCalendar::query()
            ->with(['academicYear', 'school', 'cycle', 'coveredCycles', 'level', 'classroom', 'validator'])
            ->withCount('events')
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($academicYearId !== null, fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->when($scopeType !== '', fn ($query) => $query->where('scope_type', $scopeType))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false));

        $calendars = (clone $baseQuery)
            ->orderByDesc('validated_at')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $upcomingEvents = CalendarEvent::query()
            ->with(['calendar', 'calendar.academicYear'])
            ->where('start_datetime', '>=', now())
            ->where('status', CalendarEventStatus::Published->value)
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->when($academicYearId !== null, fn ($query) => $query->whereHas('calendar', fn ($calendarQuery) => $calendarQuery->where('academic_year_id', $academicYearId)))
            ->orderBy('start_datetime')
            ->limit(5)
            ->get();

        $eventStatsQuery = CalendarEvent::query()
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->when($academicYearId !== null, fn ($query) => $query->whereHas('calendar', fn ($calendarQuery) => $calendarQuery->where('academic_year_id', $academicYearId)));

        return view('academic.calendars.index', [
            'calendars' => $calendars,
            'search' => $search,
            'academicYearId' => $academicYearId,
            'scopeType' => $scopeType,
            'status' => $status,
            'schoolId' => $schoolId,
            'scopeOptions' => CalendarScopeType::cases(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(['id', 'name']),
            'schools' => $schoolOptions,
            'canChooseSchool' => $canChooseSchool,
            'stats' => [
                'active_calendars' => (clone $baseQuery)->where('is_active', true)->count(),
                'upcoming_events' => (clone $eventStatsQuery)->where('status', CalendarEventStatus::Published->value)->where('start_datetime', '>=', now())->count(),
                'draft_events' => (clone $eventStatsQuery)->where('status', CalendarEventStatus::Draft->value)->count(),
                'published_events' => (clone $eventStatsQuery)->where('status', CalendarEventStatus::Published->value)->count(),
            ],
            'upcomingEvents' => $upcomingEvents,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', AcademicCalendar::class);

        return view('academic.calendars.form', $this->formData($request, new AcademicCalendar(), false));
    }

    public function store(StoreAcademicCalendarRequest $request): RedirectResponse
    {
        $calendar = $this->academicCalendarService->create($request->validated(), $request->user());
        $group = $this->resolveManagementGroup($request->string('group')->toString());

        if ($request->boolean('from_periods')) {
            return redirect()
                ->route('academic.calendars.periods.index', array_filter([
                    'academic_year_id' => $request->integer('academic_year_id') ?: null,
                    'academic_calendar_id' => $calendar->id,
                    'school_id' => $request->integer('school_id') ?: null,
                    'group' => $group,
                ]))
                ->with('success', __('academic.calendars.messages.created'));
        }

        if ($group !== null) {
            return redirect()
                ->route('core.academic-years.sectors.show', [
                    'academic_year' => $calendar->academic_year_id,
                    'group' => $group,
                ])
                ->with('success', __('academic.calendars.messages.created'));
        }

        return redirect()
            ->route('academic.calendars.index')
            ->with('success', __('academic.calendars.messages.created'));
    }

    public function show(AcademicCalendar $calendar): View
    {
        $this->authorize('view', $calendar);

        $calendar->load(['academicYear', 'school', 'cycle', 'coveredCycles', 'level', 'classroom', 'creator', 'validator']);
        $periods = $calendar->periods()->orderBy('position')->get();
        $events = $calendar->events()
            ->with(['creator', 'publisher'])
            ->latest('start_datetime')
            ->paginate(10);

        return view('academic.calendars.show', [
            'calendar' => $calendar,
            'periods' => $periods,
            'events' => $events,
        ]);
    }

    public function edit(Request $request, AcademicCalendar $calendar): View
    {
        $this->authorize('update', $calendar);

        return view('academic.calendars.form', $this->formData($request, $calendar->load(['cycle', 'level', 'classroom']), true));
    }

    public function update(UpdateAcademicCalendarRequest $request, AcademicCalendar $calendar): RedirectResponse
    {
        $this->academicCalendarService->update($calendar, $request->validated(), $request->user());

        return redirect()
            ->route('academic.calendars.index')
            ->with('success', __('academic.calendars.messages.updated'));
    }

    public function validateCalendar(Request $request, AcademicCalendar $calendar): RedirectResponse
    {
        $this->authorize('update', $calendar);

        $this->academicCalendarService->validate($calendar, $request->user());

        if ($request->boolean('from_periods')) {
            return redirect()
                ->route('academic.calendars.periods.index', array_filter([
                    'academic_year_id' => $calendar->academic_year_id,
                    'academic_calendar_id' => $calendar->id,
                    'school_id' => $calendar->school_id,
                ]))
                ->with('success', __('academic.calendars.messages.validated'));
        }

        return back()->with('success', __('academic.calendars.messages.validated'));
    }

    public function toggleActive(Request $request, AcademicCalendar $calendar): RedirectResponse
    {
        $this->authorize('disable', $calendar);

        $this->academicCalendarService->toggleActive($calendar, $request->user());

        return back()->with('success', $calendar->fresh()->is_active
            ? __('academic.calendars.messages.enabled')
            : __('academic.calendars.messages.disabled'));
    }

    public function managePeriods(Request $request): View
    {
        $this->authorize('viewAny', AcademicCalendar::class);

        $schoolOptions = School::query()->active()->orderBy('name')->get(['id', 'name']);
        $group = $this->resolveManagementGroup($request->string('group')->toString());
        $canChooseSchool = $this->canChooseSchool($request, $schoolOptions->count());
        $schoolId = $canChooseSchool
            ? ($request->integer('school_id') ?: $this->schoolContext->current()->id)
            : $this->schoolContext->current()->id;

        $academicYears = AcademicYear::query()->orderByDesc('start_date')->get(['id', 'name']);
        $selectedAcademicYearId = $request->integer('academic_year_id')
            ?: AcademicYear::query()->active()->value('id')
            ?: $academicYears->first()?->id;

        $selectedAcademicYear = $selectedAcademicYearId
            ? AcademicYear::query()->find($selectedAcademicYearId)
            : null;

        $calendars = $selectedAcademicYear
            ? AcademicCalendar::query()
                ->with(['cycle', 'coveredCycles', 'level', 'classroom', 'creator', 'validator'])
                ->withCount('periods')
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $selectedAcademicYear->id)
                ->when($group !== null, fn ($query) => $this->applyManagementGroupFilter($query, $group))
                ->orderByRaw("CASE scope_type WHEN 'global' THEN 0 WHEN 'cycle' THEN 1 WHEN 'level' THEN 2 WHEN 'class' THEN 3 ELSE 4 END")
                ->orderBy('name')
                ->get()
            : collect();

        $selectedCalendarId = $request->integer('academic_calendar_id') ?: null;

        if ($selectedCalendarId === null) {
            $selectedCalendarId = $group !== null
                ? $calendars->first()?->id
                : ($calendars->first(fn (AcademicCalendar $calendar): bool => $calendar->scope_type === CalendarScopeType::Global)?->id
                    ?: $calendars->first()?->id);
        }
        $selectedCalendar = $selectedCalendarId
            ? $calendars->firstWhere('id', $selectedCalendarId)
            : null;

        $configuration = $selectedAcademicYear
            ? $this->resolveConfigurationForPeriods($schoolId, $selectedAcademicYear->id, $selectedCalendar)
            : null;

        $periods = $selectedCalendar
            ? $selectedCalendar->periods()->orderBy('position')->get()
            : collect();

        return view('academic.calendars.periods', [
            'academicYears' => $academicYears,
            'selectedAcademicYear' => $selectedAcademicYear,
            'selectedAcademicYearId' => $selectedAcademicYearId,
            'configuration' => $configuration,
            'calendars' => $calendars,
            'selectedCalendar' => $selectedCalendar,
            'calendar' => $selectedCalendar,
            'periods' => $periods,
            'group' => $group,
            'schools' => $schoolOptions,
            'schoolId' => $schoolId,
            'canChooseSchool' => $canChooseSchool,
            'periodTypes' => AcademicPeriodType::cases(),
        ]);
    }

    public function generatePeriods(GenerateAcademicCalendarPeriodsRequest $request): RedirectResponse
    {
        $academicYear = AcademicYear::query()->findOrFail($request->integer('academic_year_id'));
        $group = $this->resolveManagementGroup($request->string('group')->toString());

        $this->academicCalendarService->generatePeriodsForYear($academicYear, $request->validated(), $request->user());

        return redirect()
            ->route('academic.calendars.periods.index', array_filter([
                'academic_year_id' => $academicYear->id,
                'academic_calendar_id' => $request->integer('academic_calendar_id') ?: null,
                'school_id' => $request->integer('school_id') ?: null,
                'group' => $group,
            ]))
            ->with('success', __('academic.calendars.periods.messages.generated'));
    }

    public function updatePeriods(UpdateAcademicCalendarPeriodsRequest $request, AcademicYear $academicYear): RedirectResponse
    {
        $group = $this->resolveManagementGroup($request->string('group')->toString());
        $this->academicCalendarService->updateGeneratedPeriods($academicYear, $request->validated(), $request->user());

        $schoolId = $request->integer('school_id') ?: $this->schoolContext->current()->id;

        return redirect()
            ->route('academic.calendars.periods.index', array_filter([
                'academic_year_id' => $academicYear->id,
                'academic_calendar_id' => $request->integer('academic_calendar_id') ?: null,
                'school_id' => $schoolId ?: null,
                'group' => $group,
            ]))
            ->with('success', __('academic.calendars.periods.messages.updated'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request, AcademicCalendar $calendar, bool $isEdit): array
    {
        $schoolOptions = School::query()->active()->orderBy('name')->get(['id', 'name']);
        $group = $isEdit ? null : $this->resolveManagementGroup($request->string('group')->toString());
        $initialScopeType = $isEdit
            ? ($calendar->scope_type?->value ?? CalendarScopeType::Global->value)
            : ($request->string('scope_type')->toString() ?: ($group !== null ? CalendarScopeType::Cycle->value : CalendarScopeType::Global->value));
        $academicYears = AcademicYear::query()->orderByDesc('start_date')->get(['id', 'name']);

        if (! $isEdit && $request->filled('academic_year_id')) {
            $calendar->academic_year_id = $request->integer('academic_year_id');
        }

        $cycles = AcademicCycle::query()
            ->where('is_active', true)
            ->when($group !== null, fn ($query) => $query->whereIn('category', AcademicCycleCategory::managementGroupCategories($group)))
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        if (! $isEdit && $request->filled('cycle_id')) {
            $requestedCycleId = $request->integer('cycle_id');

            if ($cycles->contains('id', $requestedCycleId)) {
                $calendar->cycle_id = $requestedCycleId;
            }
        }

        if (! $isEdit && $calendar->cycle_id === null && $cycles->count() === 1) {
            $calendar->cycle_id = $cycles->first()?->id;
        }

        if (
            ! $isEdit
            && $initialScopeType === CalendarScopeType::Cycle->value
            && blank($calendar->name)
            && $calendar->academic_year_id !== null
            && $calendar->cycle_id !== null
        ) {
            $selectedAcademicYear = $academicYears->firstWhere('id', $calendar->academic_year_id);
            $selectedCycle = $cycles->firstWhere('id', $calendar->cycle_id);

            if ($selectedAcademicYear && $selectedCycle) {
                $calendar->name = $selectedAcademicYear->name.' - '.$selectedCycle->name;
            }
        }

        $yearCycleMap = AcademicYear::query()
            ->with('cycles:id')
            ->get(['id'])
            ->mapWithKeys(fn (AcademicYear $academicYear) => [
                (string) $academicYear->id => $academicYear->cycles
                    ->pluck('id')
                    ->map(fn ($cycleId) => (string) $cycleId)
                    ->values()
                    ->all(),
            ]);

        $yearLevelMap = AcademicYear::query()
            ->with('levels:id')
            ->get(['id'])
            ->mapWithKeys(fn (AcademicYear $academicYear) => [
                (string) $academicYear->id => $academicYear->levels
                    ->pluck('id')
                    ->map(fn ($levelId) => (string) $levelId)
                    ->values()
                    ->all(),
            ]);

        return [
            'calendar' => $calendar,
            'isEdit' => $isEdit,
            'scopeOptions' => CalendarScopeType::cases(),
            'periodTypes' => AcademicPeriodType::cases(),
            'academicYears' => $academicYears,
            'cycles' => $cycles,
            'levels' => AcademicLevel::query()
                ->with('cycle')
                ->where('is_active', true)
                ->when($group !== null, fn ($query) => $query->whereHas('cycle', fn ($cycleQuery) => $cycleQuery->whereIn('category', AcademicCycleCategory::managementGroupCategories($group))))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'cycle_id', 'name', 'short_name']),
            'classrooms' => AcademicClassroom::query()
                ->with(['level', 'series'])
                ->where('is_active', true)
                ->when($group !== null, fn ($query) => $query->whereHas('level.cycle', fn ($cycleQuery) => $cycleQuery->whereIn('category', AcademicCycleCategory::managementGroupCategories($group))))
                ->orderBy('name')
                ->get(['id', 'school_id', 'academic_year_id', 'level_id', 'series_id', 'name', 'code']),
            'schools' => $schoolOptions,
            'canChooseSchool' => $this->canChooseSchool($request, $schoolOptions->count()),
            'currentSchoolId' => $calendar->school_id ?: $this->schoolContext->current()->id,
            'selectedGroup' => $group,
            'selectedGroupLabel' => $group !== null ? __('academic.shared.cycle_groups.'.$group) : null,
            'initialScopeType' => $initialScopeType,
            'yearCycleMap' => $yearCycleMap,
            'yearLevelMap' => $yearLevelMap,
            'fromPeriods' => ! $isEdit && $request->boolean('from_periods'),
        ];
    }

    private function resolveManagementGroup(string $group): ?string
    {
        $group = trim($group);

        return array_key_exists($group, AcademicCycleCategory::managementGroups()) ? $group : null;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<AcademicCalendar>  $query
     */
    private function applyManagementGroupFilter($query, string $group): void
    {
        $categories = AcademicCycleCategory::managementGroupCategories($group);

        $query->where(function ($calendarQuery) use ($categories): void {
            $calendarQuery
                ->whereHas('coveredCycles', fn ($cycleQuery) => $cycleQuery->whereIn('category', $categories))
                ->orWhereHas('cycle', fn ($cycleQuery) => $cycleQuery->whereIn('category', $categories));
        });
    }

    private function resolveConfigurationForPeriods(int $schoolId, int $academicYearId, ?AcademicCalendar $selectedCalendar): ?AcademicConfiguration
    {
        $query = AcademicConfiguration::query()
            ->with(['academicYear', 'cycle', 'coveredCycles'])
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId);

        if ($selectedCalendar) {
            if ($selectedCalendar->academic_configuration_id) {
                return (clone $query)->whereKey($selectedCalendar->academic_configuration_id)->first();
            }

            $scopeType = $selectedCalendar->scope_type instanceof CalendarScopeType
                ? $selectedCalendar->scope_type
                : CalendarScopeType::tryFrom((string) $selectedCalendar->scope_type) ?? CalendarScopeType::Global;
            $coveredCycleIds = $selectedCalendar->resolvedCoveredCycleIds();

            $exactMatch = $scopeType === CalendarScopeType::Cycle && count($coveredCycleIds) > 1
                ? (clone $query)
                    ->where('scope_signature', 'cycle-group:'.implode('-', $coveredCycleIds))
                    ->first()
                : (clone $query)
                    ->where('scope_type', $scopeType->value)
                    ->when(
                        $scopeType === CalendarScopeType::Cycle,
                        fn ($builder) => $builder->where('cycle_id', $selectedCalendar->cycle_id),
                        fn ($builder) => $builder->whereNull('cycle_id'),
                    )
                    ->first();

            if ($exactMatch) {
                return $exactMatch;
            }
        }

        return (clone $query)
            ->where('is_active', true)
            ->orderByRaw("CASE scope_type WHEN 'global' THEN 0 WHEN 'cycle' THEN 1 ELSE 2 END")
            ->orderBy('id')
            ->first()
            ?? (clone $query)
                ->orderByRaw("CASE scope_type WHEN 'global' THEN 0 WHEN 'cycle' THEN 1 ELSE 2 END")
                ->orderBy('id')
                ->first();
    }

    private function canChooseSchool(Request $request, int $schoolCount): bool
    {
        return $schoolCount > 1
            && $this->schoolContext->isMultiSchoolEnabled()
            && ($request->user()?->can('manageMultiSchool', SystemSetting::class) ?? false);
    }
}
