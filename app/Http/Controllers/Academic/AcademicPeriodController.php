<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreAcademicPeriodRequest;
use App\Http\Requests\Academic\UpdateAcademicPeriodRequest;
use App\Models\AcademicConfiguration;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Services\Academic\AcademicPeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicPeriodController extends Controller
{
    public function __construct(
        private readonly AcademicPeriodService $academicPeriodService,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AcademicPeriod::class);

        $search = trim((string) $request->string('search'));
        $academicYearId = $request->integer('academic_year_id') ?: null;
        $academicCalendarId = $request->integer('academic_calendar_id') ?: null;
        $configurationId = $request->integer('academic_configuration_id') ?: null;
        $status = trim((string) $request->string('status'));
        $locked = trim((string) $request->string('locked'));

        $baseQuery = AcademicPeriod::query()
            ->with(['academicCalendar.academicYear', 'academicCalendar.school', 'configuration.academicYear', 'configuration.school', 'configuration.cycle', 'configuration.calendar'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->when($academicYearId, fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->when($academicCalendarId, fn ($query) => $query->where('academic_calendar_id', $academicCalendarId))
            ->when($configurationId, fn ($query) => $query->where('academic_configuration_id', $configurationId))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($locked === 'locked', fn ($query) => $query->where('is_locked', true))
            ->when($locked === 'unlocked', fn ($query) => $query->where('is_locked', false));

        $periods = (clone $baseQuery)
            ->orderByDesc('academic_year_id')
            ->orderBy('position')
            ->paginate(12)
            ->withQueryString();

        return view('academic.periods.index', [
            'periods' => $periods,
            'search' => $search,
            'academicYearId' => $academicYearId,
            'academicCalendarId' => $academicCalendarId,
            'configurationId' => $configurationId,
            'status' => $status,
            'locked' => $locked,
            'calendars' => \App\Models\AcademicCalendar::query()->with(['academicYear', 'school', 'cycle', 'level', 'classroom'])->orderByDesc('academic_year_id')->orderBy('name')->get(),
            'configurations' => AcademicConfiguration::query()->with(['academicYear', 'school', 'cycle', 'coveredCycles', 'calendar'])->orderByDesc('academic_year_id')->orderByRaw("CASE scope_type WHEN 'global' THEN 0 WHEN 'cycle' THEN 1 ELSE 2 END")->orderBy('id')->get(),
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(['id', 'name']),
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'active' => (clone $baseQuery)->where('is_active', true)->count(),
                'locked' => (clone $baseQuery)->where('is_locked', true)->count(),
                'open' => (clone $baseQuery)->whereDate('end_date', '>=', now()->toDateString())->count(),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', AcademicPeriod::class);

        return view('academic.periods.form', $this->formData(new AcademicPeriod(), false));
    }

    public function store(StoreAcademicPeriodRequest $request): RedirectResponse
    {
        $this->academicPeriodService->create($request->validated(), $request->user());

        return redirect()
            ->route('academic.periods.index')
            ->with('success', __('academic.periods.messages.created'));
    }

    public function show(AcademicPeriod $period): View
    {
        $this->authorize('view', $period);

        return view('academic.periods.show', [
            'period' => $period->load(['academicCalendar.academicYear', 'academicCalendar.school', 'configuration.academicYear', 'configuration.school', 'configuration.cycle', 'configuration.calendar', 'creator', 'updater']),
        ]);
    }

    public function edit(AcademicPeriod $period): View
    {
        $this->authorize('update', $period);

        return view('academic.periods.form', $this->formData($period->load(['configuration.calendar']), true));
    }

    public function update(UpdateAcademicPeriodRequest $request, AcademicPeriod $period): RedirectResponse
    {
        $this->academicPeriodService->update($period, $request->validated(), $request->user());

        return redirect()
            ->route('academic.periods.index')
            ->with('success', __('academic.periods.messages.updated'));
    }

    public function toggleLock(Request $request, AcademicPeriod $period): RedirectResponse
    {
        $this->authorize('lock', $period);

        $this->academicPeriodService->toggleLock($period, $request->user());

        return back()->with('success', $period->fresh()->is_locked
            ? __('academic.periods.messages.locked')
            : __('academic.periods.messages.unlocked'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(AcademicPeriod $period, bool $isEdit): array
    {
        return [
            'period' => $period,
            'isEdit' => $isEdit,
            'calendars' => \App\Models\AcademicCalendar::query()->with(['academicYear', 'school', 'cycle', 'level', 'classroom'])->where('is_active', true)->orderByDesc('academic_year_id')->orderBy('name')->get(),
            'configurations' => AcademicConfiguration::query()->with(['academicYear', 'school', 'cycle', 'coveredCycles', 'calendar'])->where('is_active', true)->orderByDesc('academic_year_id')->orderByRaw("CASE scope_type WHEN 'global' THEN 0 WHEN 'cycle' THEN 1 ELSE 2 END")->orderBy('id')->get(),
        ];
    }
}
