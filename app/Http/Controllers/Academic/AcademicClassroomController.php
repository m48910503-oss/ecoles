<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreAcademicClassroomRequest;
use App\Http\Requests\Academic\UpdateAcademicClassroomRequest;
use App\Models\AcademicCycle;
use App\Models\AcademicClassroom;
use App\Models\AcademicLevel;
use App\Models\AcademicSeries;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SystemSetting;
use App\Services\Academic\AcademicClassroomService;
use App\Services\Core\SchoolContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicClassroomController extends Controller
{
    public function __construct(
        private readonly AcademicClassroomService $academicClassroomService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AcademicClassroom::class);

        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status'));
        $academicYearId = $request->integer('academic_year_id') ?: null;
        $levelId = $request->integer('level_id') ?: null;
        $seriesId = $request->integer('series_id') ?: null;
        $schoolOptions = School::query()->active()->orderBy('name')->get(['id', 'name']);
        $canChooseSchool = $this->canChooseSchool($request, $schoolOptions->count());
        $schoolId = $canChooseSchool
            ? ($request->integer('school_id') ?: null)
            : $this->schoolContext->current()->id;

        $baseQuery = AcademicClassroom::query()
            ->with(['school', 'academicYear', 'level.cycle', 'series'])
            ->withCount('calendars')
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($academicYearId !== null, fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->when($levelId !== null, fn ($query) => $query->where('level_id', $levelId))
            ->when($seriesId !== null, fn ($query) => $query->where('series_id', $seriesId));

        return view('academic.classes.index', [
            'classrooms' => (clone $baseQuery)
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->paginate(12)
                ->withQueryString(),
            'search' => $search,
            'status' => $status,
            'academicYearId' => $academicYearId,
            'levelId' => $levelId,
            'seriesId' => $seriesId,
            'schoolId' => $schoolId,
            'canChooseSchool' => $canChooseSchool,
            'schools' => $schoolOptions,
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(['id', 'name']),
            'levels' => AcademicLevel::query()->with(['cycle', 'series'])->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'series' => AcademicSeries::query()->with('cycle')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'cycle_id', 'name', 'short_name', 'code']),
            'seriesOptions' => AcademicSeries::query()->with('cycle')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'cycle_id', 'name', 'short_name', 'code']),
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'active' => (clone $baseQuery)->where('is_active', true)->count(),
                'with_series' => (clone $baseQuery)->whereNotNull('series_id')->count(),
                'referenced' => (clone $baseQuery)->has('calendars')->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', AcademicClassroom::class);

        return view('academic.classes.form', $this->formData($request, new AcademicClassroom(), false));
    }

    public function store(StoreAcademicClassroomRequest $request): RedirectResponse
    {
        $classroom = $this->academicClassroomService->create($request->validated(), $request->user());

        if ($request->boolean('return_to_year_cycle')) {
            $classroom->loadMissing('level');

            return redirect()
                ->route('core.academic-years.cycles.show', [
                    'academic_year' => $classroom->academic_year_id,
                    'cycle' => $classroom->level->cycle_id,
                ])
                ->with('success', __('academic.classes.messages.created'));
        }

        return redirect()
            ->route('academic.classes.index')
            ->with('success', __('academic.classes.messages.created'));
    }

    public function show(AcademicClassroom $classroom): View
    {
        $this->authorize('view', $classroom);

        return view('academic.classes.show', [
            'classroom' => $classroom->load(['school', 'academicYear', 'level.cycle', 'series', 'creator', 'updater', 'calendars']),
        ]);
    }

    public function edit(Request $request, AcademicClassroom $classroom): View
    {
        $this->authorize('update', $classroom);

        return view('academic.classes.form', $this->formData($request, $classroom->load(['level.cycle', 'series']), true));
    }

    public function update(UpdateAcademicClassroomRequest $request, AcademicClassroom $classroom): RedirectResponse
    {
        $classroom = $this->academicClassroomService->update($classroom, $request->validated(), $request->user());

        if ($request->boolean('return_to_year_cycle')) {
            $classroom->loadMissing('level');

            return redirect()
                ->route('core.academic-years.cycles.show', [
                    'academic_year' => $classroom->academic_year_id,
                    'cycle' => $classroom->level->cycle_id,
                ])
                ->with('success', __('academic.classes.messages.updated'));
        }

        return redirect()
            ->route('academic.classes.index')
            ->with('success', __('academic.classes.messages.updated'));
    }

    public function destroy(Request $request, AcademicClassroom $classroom): RedirectResponse
    {
        $this->authorize('delete', $classroom);

        $this->academicClassroomService->delete($classroom, $request->user());

        return redirect()
            ->route('academic.classes.index')
            ->with('success', __('academic.classes.messages.deleted'));
    }

    public function toggleActive(Request $request, AcademicClassroom $classroom): RedirectResponse
    {
        $this->authorize('disable', $classroom);

        $this->academicClassroomService->toggleActive($classroom, $request->user());

        return back()->with('success', $classroom->fresh()->is_active
            ? __('academic.classes.messages.enabled')
            : __('academic.classes.messages.disabled'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request, AcademicClassroom $classroom, bool $isEdit): array
    {
        $schoolOptions = School::query()->active()->orderBy('name')->get(['id', 'name']);
        $requestedAcademicYearId = $request->integer('academic_year_id') ?: null;
        $namingAcademicYearId = $classroom->exists
            ? $classroom->academic_year_id
            : ($requestedAcademicYearId ?: (int) (AcademicYear::query()->active()->value('id') ?: 0));
        $selectedAcademicYear = $namingAcademicYearId
            ? AcademicYear::query()->with(['cycles:id', 'levels:id', 'series:id'])->find($namingAcademicYearId)
            : null;
        $requestedCycleId = $request->integer('cycle_id')
            ?: ($classroom->level?->cycle_id ? (int) $classroom->level->cycle_id : null);
        $selectedCycle = $requestedCycleId
            ? AcademicCycle::query()->find($requestedCycleId)
            : null;
        $returnToYearCycle = $request->boolean('return_to_year_cycle');

        if ($returnToYearCycle && $selectedAcademicYear && $selectedCycle) {
            abort_unless($selectedAcademicYear->cycles->contains('id', $selectedCycle->id), 404);
        }

        $availableCycleIds = $selectedAcademicYear
            ? $selectedAcademicYear->cycles->pluck('id')->map(fn ($cycleId) => (int) $cycleId)->all()
            : [];
        $availableLevelIds = $selectedAcademicYear
            ? $selectedAcademicYear->levels->pluck('id')->map(fn ($levelId) => (int) $levelId)->all()
            : [];
        $availableSeriesIds = $selectedAcademicYear
            ? $selectedAcademicYear->series->pluck('id')->map(fn ($seriesId) => (int) $seriesId)->all()
            : [];
        $backRoute = $returnToYearCycle && $selectedAcademicYear && $selectedCycle
            ? route('core.academic-years.cycles.show', ['academic_year' => $selectedAcademicYear, 'cycle' => $selectedCycle])
            : route('academic.classes.index');

        return [
            'classroom' => $classroom,
            'isEdit' => $isEdit,
            'namingAcademicYearId' => $namingAcademicYearId,
            'selectedAcademicYear' => $selectedAcademicYear,
            'selectedCycle' => $selectedCycle,
            'returnToYearCycle' => $returnToYearCycle,
            'backRoute' => $backRoute,
            'cycles' => AcademicCycle::query()
                ->where('is_active', true)
                ->when($selectedAcademicYear, fn ($query) => $query->whereIn('id', $availableCycleIds))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'category']),
            'levels' => AcademicLevel::query()
                ->with(['cycle', 'series'])
                ->where('is_active', true)
                ->when($selectedAcademicYear, fn ($query) => $query->whereIn('id', $availableLevelIds))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'series' => AcademicSeries::query()
                ->with('cycle')
                ->where('is_active', true)
                ->when($selectedAcademicYear, fn ($query) => $query->whereIn('id', $availableSeriesIds))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'cycle_id', 'name', 'short_name', 'code']),
            'seriesOptions' => AcademicSeries::query()
                ->with('cycle')
                ->where('is_active', true)
                ->when($selectedAcademicYear, fn ($query) => $query->whereIn('id', $availableSeriesIds))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'cycle_id', 'name', 'short_name', 'code']),
            'classrooms' => $namingAcademicYearId
                ? AcademicClassroom::query()
                    ->where('academic_year_id', $namingAcademicYearId)
                    ->get(['id', 'school_id', 'academic_year_id', 'level_id', 'series_id', 'name'])
                : collect(),
            'schools' => $schoolOptions,
            'canChooseSchool' => $this->canChooseSchool($request, $schoolOptions->count()),
            'currentSchoolId' => $classroom->school_id ?: $this->schoolContext->current()->id,
        ];
    }

    private function canChooseSchool(Request $request, int $schoolCount): bool
    {
        return $schoolCount > 1
            && $this->schoolContext->isMultiSchoolEnabled()
            && ($request->user()?->can('manageMultiSchool', SystemSetting::class) ?? false);
    }
}
