<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreAcademicCoefficientRequest;
use App\Http\Requests\Academic\UpdateAcademicCoefficientRequest;
use App\Models\AcademicCoefficient;
use App\Models\AcademicLevel;
use App\Models\AcademicSeries;
use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SystemSetting;
use App\Services\Academic\AcademicCoefficientService;
use App\Services\Core\SchoolContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicCoefficientController extends Controller
{
    public function __construct(
        private readonly AcademicCoefficientService $academicCoefficientService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AcademicCoefficient::class);

        $status = trim((string) $request->string('status'));
        $academicYearId = $request->integer('academic_year_id') ?: null;
        $levelId = $request->integer('level_id') ?: null;
        $seriesId = $request->integer('series_id') ?: null;
        $subjectId = $request->integer('academic_subject_id') ?: null;
        $schoolOptions = School::query()->active()->orderBy('name')->get(['id', 'name']);
        $canChooseSchool = $this->canChooseSchool($request, $schoolOptions->count());
        $schoolId = $canChooseSchool
            ? ($request->integer('school_id') ?: null)
            : $this->schoolContext->current()->id;

        $baseQuery = AcademicCoefficient::query()
            ->with(['school', 'academicYear', 'level.cycle', 'series', 'subject'])
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($academicYearId !== null, fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->when($levelId !== null, fn ($query) => $query->where('level_id', $levelId))
            ->when($seriesId !== null, fn ($query) => $query->where('series_id', $seriesId))
            ->when($subjectId !== null, fn ($query) => $query->where('academic_subject_id', $subjectId));

        return view('academic.coefficients.index', [
            'coefficients' => (clone $baseQuery)
                ->orderByDesc('is_active')
                ->orderBy('academic_year_id')
                ->paginate(12)
                ->withQueryString(),
            'status' => $status,
            'academicYearId' => $academicYearId,
            'levelId' => $levelId,
            'seriesId' => $seriesId,
            'subjectId' => $subjectId,
            'schoolId' => $schoolId,
            'canChooseSchool' => $canChooseSchool,
            'schools' => $schoolOptions,
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(['id', 'name']),
            'levels' => AcademicLevel::query()->with('cycle')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'series' => AcademicSeries::query()->with('cycle')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'subjects' => AcademicSubject::query()->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'active' => (clone $baseQuery)->where('is_active', true)->count(),
                'optional' => (clone $baseQuery)->where('is_optional', true)->count(),
                'conduct' => (clone $baseQuery)->whereHas('subject', fn ($query) => $query->where('is_conduct', true))->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', AcademicCoefficient::class);

        $coefficient = new AcademicCoefficient();
        $prefilledSubjectId = $request->integer('academic_subject_id') ?: $request->integer('subject_id');

        if ($prefilledSubjectId > 0) {
            $subject = AcademicSubject::query()->find($prefilledSubjectId);
            $canPrefillCrossSchool = $request->user()?->can('manageMultiSchool', SystemSetting::class) ?? false;
            $matchesCurrentSchool = $subject && $subject->school_id === $this->schoolContext->current()->id;

            if ($subject && ($matchesCurrentSchool || $canPrefillCrossSchool)) {
                $coefficient->school_id = $subject->school_id;
                $coefficient->academic_subject_id = $subject->id;
                $coefficient->is_optional = $subject->is_optional_by_default;
            }
        }

        if ($request->filled('school_id')) {
            $coefficient->school_id = $request->integer('school_id');
        }

        if ($request->filled('academic_year_id')) {
            $coefficient->academic_year_id = $request->integer('academic_year_id');
        }

        if ($request->filled('level_id')) {
            $coefficient->level_id = $request->integer('level_id');
        }

        if ($request->filled('series_id')) {
            $coefficient->series_id = $request->integer('series_id');
        }

        return view('academic.coefficients.form', $this->formData($request, $coefficient, false));
    }

    public function store(StoreAcademicCoefficientRequest $request): RedirectResponse
    {
        $this->academicCoefficientService->create($request->validated(), $request->user());

        return redirect()
            ->route('academic.coefficients.index')
            ->with('success', __('academic.coefficients.messages.created'));
    }

    public function show(AcademicCoefficient $coefficient): View
    {
        $this->authorize('view', $coefficient);

        return view('academic.coefficients.show', [
            'coefficient' => $coefficient->load(['school', 'academicYear', 'level.cycle', 'series', 'subject', 'creator', 'updater']),
        ]);
    }

    public function edit(Request $request, AcademicCoefficient $coefficient): View
    {
        $this->authorize('update', $coefficient);

        return view('academic.coefficients.form', $this->formData($request, $coefficient, true));
    }

    public function update(UpdateAcademicCoefficientRequest $request, AcademicCoefficient $coefficient): RedirectResponse
    {
        $this->academicCoefficientService->update($coefficient, $request->validated(), $request->user());

        return redirect()
            ->route('academic.coefficients.index')
            ->with('success', __('academic.coefficients.messages.updated'));
    }

    public function toggleActive(Request $request, AcademicCoefficient $coefficient): RedirectResponse
    {
        $this->authorize('disable', $coefficient);

        $this->academicCoefficientService->toggleActive($coefficient, $request->user());

        return back()->with('success', $coefficient->fresh()->is_active
            ? __('academic.coefficients.messages.enabled')
            : __('academic.coefficients.messages.disabled'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request, AcademicCoefficient $coefficient, bool $isEdit): array
    {
        $schoolOptions = School::query()->active()->orderBy('name')->get(['id', 'name']);
        $selectedSchoolId = $coefficient->school_id ?: $this->schoolContext->current()->id;

        return [
            'coefficient' => $coefficient,
            'isEdit' => $isEdit,
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(['id', 'name']),
            'levels' => AcademicLevel::query()->with('cycle')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'series' => AcademicSeries::query()->with('cycle')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'subjects' => AcademicSubject::query()->where('school_id', $selectedSchoolId)->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'schools' => $schoolOptions,
            'canChooseSchool' => $this->canChooseSchool($request, $schoolOptions->count()),
            'currentSchoolId' => $selectedSchoolId,
        ];
    }

    private function canChooseSchool(Request $request, int $schoolCount): bool
    {
        return $schoolCount > 1
            && $this->schoolContext->isMultiSchoolEnabled()
            && ($request->user()?->can('manageMultiSchool', SystemSetting::class) ?? false);
    }
}
