<?php

namespace App\Http\Controllers\Academic;

use App\Enums\Academic\AcademicCycleCategory;
use App\Enums\Academic\CalendarEventStatus;
use App\Enums\Academic\CalendarEventType;
use App\Enums\Academic\CalendarEventVisibility;
use App\Enums\Academic\CalendarTargetType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\CancelCalendarEventRequest;
use App\Http\Requests\Academic\PublishCalendarEventRequest;
use App\Http\Requests\Academic\StoreCalendarEventRequest;
use App\Http\Requests\Academic\UpdateCalendarEventRequest;
use App\Models\AcademicCalendar;
use App\Models\AcademicClassroom;
use App\Models\AcademicCycle;
use App\Models\AcademicLevel;
use App\Models\AcademicYear;
use App\Models\CalendarEvent;
use App\Models\School;
use App\Models\SystemSetting;
use App\Services\Academic\CalendarEventService;
use App\Services\Core\SchoolContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarEventController extends Controller
{
    public function __construct(
        private readonly CalendarEventService $calendarEventService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', CalendarEvent::class);

        $search = trim((string) $request->string('search'));
        $academicYearId = $request->integer('academic_year_id') ?: null;
        $calendarId = $request->integer('academic_calendar_id') ?: null;
        $eventType = trim((string) $request->string('event_type'));
        $status = trim((string) $request->string('status'));
        $targetType = trim((string) ($request->string('target_type')->toString() ?: $request->string('scope_type')->toString()));
        $visibility = trim((string) $request->string('visibility'));
        $dateFrom = trim((string) $request->string('date_from'));
        $dateTo = trim((string) $request->string('date_to'));
        $viewMode = trim((string) $request->string('view_mode')) ?: 'list';
        $schoolOptions = School::query()->active()->orderBy('name')->get(['id', 'name']);
        $canChooseSchool = $this->canChooseSchool($request, $schoolOptions->count());
        $schoolId = $canChooseSchool
            ? ($request->integer('school_id') ?: null)
            : $this->schoolContext->current()->id;

        $baseQuery = CalendarEvent::query()
            ->with(['calendar.academicYear', 'calendar.cycle', 'calendar.classroom', 'period', 'targetCycle', 'targetLevel', 'targetClassroom', 'creator', 'publisher', 'school'])
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->when($academicYearId !== null, fn ($query) => $query->whereHas('calendar', fn ($calendarQuery) => $calendarQuery->where('academic_year_id', $academicYearId)))
            ->when($calendarId !== null, fn ($query) => $query->where('academic_calendar_id', $calendarId))
            ->when($eventType !== '', fn ($query) => $query->where('event_type', $eventType))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($targetType !== '', fn ($query) => $query->where('target_type', $targetType))
            ->when($visibility !== '', fn ($query) => $query->where('visibility', $visibility))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('start_datetime', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('end_datetime', '<=', $dateTo));

        $this->calendarEventService->applyVisibilityScope($baseQuery, $request->user());

        $events = (clone $baseQuery)
            ->orderBy('start_datetime')
            ->paginate(12)
            ->withQueryString();

        $monthGroups = $viewMode === 'month'
            ? $events->getCollection()->groupBy(fn (CalendarEvent $event) => $event->start_datetime?->translatedFormat('F Y'))
            : collect();

        return view('academic.events.index', [
            'events' => $events,
            'monthGroups' => $monthGroups,
            'search' => $search,
            'academicYearId' => $academicYearId,
            'calendarId' => $calendarId,
            'eventType' => $eventType,
            'status' => $status,
            'targetType' => $targetType,
            'visibility' => $visibility,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'viewMode' => $viewMode,
            'schoolId' => $schoolId,
            'canChooseSchool' => $canChooseSchool,
            'schools' => $schoolOptions,
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(['id', 'name']),
            'calendars' => AcademicCalendar::query()->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))->orderBy('name')->get(['id', 'name']),
            'eventTypes' => CalendarEventType::cases(),
            'statusOptions' => CalendarEventStatus::cases(),
            'targetOptions' => CalendarTargetType::cases(),
            'visibilityOptions' => CalendarEventVisibility::cases(),
            'stats' => [
                'upcoming' => (clone $baseQuery)->where('status', CalendarEventStatus::Published->value)->where('start_datetime', '>=', now())->count(),
                'drafts' => (clone $baseQuery)->where('status', CalendarEventStatus::Draft->value)->count(),
                'published' => (clone $baseQuery)->where('status', CalendarEventStatus::Published->value)->count(),
                'internal' => (clone $baseQuery)->where('visibility', CalendarEventVisibility::Internal->value)->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', CalendarEvent::class);

        return view('academic.events.form', $this->formData($request, new CalendarEvent(), false));
    }

    public function store(StoreCalendarEventRequest $request): RedirectResponse
    {
        $event = $this->calendarEventService->create($request->validated(), $request->user());
        $event->loadMissing('calendar');
        $group = $this->resolveManagementGroup($request->string('group')->toString());

        if ($group !== null) {
            return redirect()
                ->route('academic.events.index', [
                    'academic_year_id' => $event->calendar->academic_year_id,
                    'group' => $group,
                ])
                ->with('success', __('academic.events.messages.created'));
        }

        return redirect()
            ->route('academic.events.index')
            ->with('success', __('academic.events.messages.created'));
    }

    public function show(CalendarEvent $event): View
    {
        $event->load(['calendar.academicYear', 'calendar.cycle', 'calendar.classroom', 'period', 'targetCycle', 'targetLevel', 'targetClassroom', 'school', 'creator', 'validator', 'publisher', 'canceller', 'archiver']);
        $this->authorize('view', $event);

        return view('academic.events.show', [
            'event' => $event,
        ]);
    }

    public function edit(Request $request, CalendarEvent $event): View
    {
        $this->authorize('update', $event);

        return view('academic.events.form', $this->formData($request, $event, true));
    }

    public function update(UpdateCalendarEventRequest $request, CalendarEvent $event): RedirectResponse
    {
        $this->calendarEventService->update($event, $request->validated(), $request->user());

        return redirect()
            ->route('academic.events.index')
            ->with('success', __('academic.events.messages.updated'));
    }

    public function publish(PublishCalendarEventRequest $request, CalendarEvent $event): RedirectResponse
    {
        $this->calendarEventService->publish($event, $request->user());

        return back()->with('success', __('academic.events.messages.published'));
    }

    public function cancel(CancelCalendarEventRequest $request, CalendarEvent $event): RedirectResponse
    {
        $this->calendarEventService->cancel($event, (string) $request->validated('cancellation_reason'), $request->user());

        return back()->with('success', __('academic.events.messages.cancelled'));
    }

    public function archive(Request $request, CalendarEvent $event): RedirectResponse
    {
        $this->authorize('archive', $event);

        $this->calendarEventService->archive($event, $request->user());

        return back()->with('success', __('academic.events.messages.archived'));
    }

    public function destroy(Request $request, CalendarEvent $event): RedirectResponse
    {
        $this->authorize('delete', $event);

        $this->calendarEventService->delete($event, $request->user());

        return redirect()
            ->route('academic.events.index')
            ->with('success', __('academic.events.messages.deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request, CalendarEvent $event, bool $isEdit): array
    {
        $schoolOptions = School::query()->active()->orderBy('name')->get(['id', 'name']);
        $canChooseSchool = $this->canChooseSchool($request, $schoolOptions->count());
        $group = $isEdit ? null : $this->resolveManagementGroup($request->string('group')->toString());
        $requestedCalendar = ! $isEdit && $request->filled('academic_calendar_id')
            ? AcademicCalendar::query()
                ->with(['academicYear', 'cycle', 'coveredCycles', 'level', 'classroom.level', 'periods' => fn ($query) => $query->orderBy('position')])
                ->find($request->integer('academic_calendar_id'))
            : null;
        $schoolId = $event->school_id
            ?: $requestedCalendar?->school_id
            ?: ($canChooseSchool ? null : $this->schoolContext->current()->id);
        $academicYearId = $isEdit
            ? null
            : ($request->integer('academic_year_id') ?: $requestedCalendar?->academic_year_id ?: null);

        $calendars = AcademicCalendar::query()
            ->with(['academicYear', 'cycle', 'coveredCycles', 'level', 'classroom.level', 'periods' => fn ($query) => $query->orderBy('position')])
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->when($academicYearId !== null, fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->when($group !== null, fn ($query) => $this->applyManagementGroupFilter($query, $group))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if (! $isEdit && $requestedCalendar) {
            $event->academic_calendar_id = $requestedCalendar->id;
        }

        if (! $isEdit && $event->academic_calendar_id === null && $calendars->count() === 1) {
            $event->academic_calendar_id = $calendars->first()?->id;
        }

        if (! $isEdit && $request->filled('target_type')) {
            $event->target_type = CalendarTargetType::tryFrom($request->string('target_type')->toString())?->value
                ?? $request->string('target_type')->toString();
        }

        if (! $isEdit && $request->filled('target_id')) {
            $event->target_id = $request->integer('target_id');
        }

        $event->loadMissing(['calendar.academicYear', 'calendar.cycle', 'calendar.level', 'calendar.classroom.level', 'period', 'targetCycle', 'targetLevel', 'targetClassroom.level']);

        $initialTargetCycleId = $request->old('target_cycle_id')
            ?? ($request->filled('target_cycle_id')
                ? $request->integer('target_cycle_id')
                : ($event->targetCycle?->id ?? $event->targetLevel?->cycle_id ?? $event->targetClassroom?->level?->cycle_id));

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

        $calendarCycleMap = $calendars
            ->mapWithKeys(fn (AcademicCalendar $calendar) => [
                (string) $calendar->id => collect($calendar->resolvedCoveredCycleIds())
                    ->map(fn (int $cycleId) => (string) $cycleId)
                    ->values()
                    ->all(),
            ]);

        return [
            'eventModel' => $event->loadMissing('calendar'),
            'isEdit' => $isEdit,
            'calendars' => $calendars,
            'cycles' => AcademicCycle::query()
                ->where('is_active', true)
                ->when($group !== null, fn ($query) => $query->whereIn('category', AcademicCycleCategory::managementGroupCategories($group)))
                ->orderBy('sort_order')
                ->get(['id', 'name']),
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
            'canChooseSchool' => $canChooseSchool,
            'currentSchoolId' => $schoolId,
            'selectedGroup' => $group,
            'selectedGroupLabel' => $group !== null ? __('academic.shared.cycle_groups.'.$group) : null,
            'selectedAcademicYearId' => $academicYearId,
            'selectedCalendar' => $calendars->firstWhere('id', $event->academic_calendar_id),
            'initialTargetCycleId' => $initialTargetCycleId,
            'yearCycleMap' => $yearCycleMap,
            'yearLevelMap' => $yearLevelMap,
            'calendarCycleMap' => $calendarCycleMap,
            'periodsByCalendar' => $calendars->mapWithKeys(fn (AcademicCalendar $calendar) => [
                (string) $calendar->id => $calendar->periods
                    ->map(fn ($period) => [
                        'id' => $period->id,
                        'name' => $period->name,
                        'position' => $period->position,
                        'start_date' => optional($period->start_date)->toDateString(),
                        'end_date' => optional($period->end_date)->toDateString(),
                    ])
                    ->values()
                    ->all(),
            ]),
            'eventTypes' => CalendarEventType::cases(),
            'locationRequiredTypes' => CalendarEventType::requiringLocationValues(),
            'statusOptions' => [
                CalendarEventStatus::PendingValidation,
                CalendarEventStatus::Validated,
            ],
            'targetOptions' => CalendarTargetType::cases(),
            'visibilityOptions' => CalendarEventVisibility::cases(),
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

    private function canChooseSchool(Request $request, int $schoolCount): bool
    {
        return $schoolCount > 1
            && $this->schoolContext->isMultiSchoolEnabled()
            && ($request->user()?->can('manageMultiSchool', SystemSetting::class) ?? false);
    }
}
