<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreAcademicConductSettingRequest;
use App\Http\Requests\Academic\UpdateAcademicConductSettingRequest;
use App\Models\AcademicConductSetting;
use App\Models\AcademicLevel;
use App\Models\AcademicSubject;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SystemSetting;
use App\Services\Academic\AcademicConductSettingService;
use App\Services\Core\SchoolContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicConductSettingController extends Controller
{
    public function __construct(
        private readonly AcademicConductSettingService $academicConductSettingService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AcademicConductSetting::class);

        $status = trim((string) $request->string('status'));
        $academicYearId = $request->integer('academic_year_id') ?: null;
        $levelId = $request->integer('level_id') ?: null;
        $schoolOptions = School::query()->active()->orderBy('name')->get(['id', 'name']);
        $canChooseSchool = $this->canChooseSchool($request, $schoolOptions->count());
        $schoolId = $canChooseSchool
            ? ($request->integer('school_id') ?: null)
            : $this->schoolContext->current()->id;

        $baseQuery = AcademicConductSetting::query()
            ->with(['school', 'academicYear', 'level.cycle', 'subject'])
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($academicYearId !== null, fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->when($levelId !== null, fn ($query) => $query->where('level_id', $levelId));

        return view('academic.conduct.index', [
            'conductSettings' => (clone $baseQuery)
                ->orderByDesc('is_active')
                ->orderBy('academic_year_id')
                ->paginate(12)
                ->withQueryString(),
            'status' => $status,
            'academicYearId' => $academicYearId,
            'levelId' => $levelId,
            'schoolId' => $schoolId,
            'canChooseSchool' => $canChooseSchool,
            'schools' => $schoolOptions,
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(['id', 'name']),
            'levels' => AcademicLevel::query()->with('cycle')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'active' => (clone $baseQuery)->where('is_active', true)->count(),
                'sanctions_only' => (clone $baseQuery)->where('deductions_from_sanctions_only', true)->count(),
                'average_base_score' => number_format((float) ((clone $baseQuery)->avg('base_score') ?? 0), 2, ',', ' '),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', AcademicConductSetting::class);

        return view('academic.conduct.form', $this->formData($request, new AcademicConductSetting(), false));
    }

    public function store(StoreAcademicConductSettingRequest $request): RedirectResponse
    {
        $this->academicConductSettingService->create($request->validated(), $request->user());

        return redirect()
            ->route('academic.conduct.index')
            ->with('success', __('academic.conduct.messages.created'));
    }

    public function show(AcademicConductSetting $conduct_setting): View
    {
        $this->authorize('view', $conduct_setting);

        return view('academic.conduct.show', [
            'conductSetting' => $conduct_setting->load(['school', 'academicYear', 'level.cycle', 'subject', 'creator', 'updater']),
        ]);
    }

    public function edit(Request $request, AcademicConductSetting $conduct_setting): View
    {
        $this->authorize('update', $conduct_setting);

        return view('academic.conduct.form', $this->formData($request, $conduct_setting, true));
    }

    public function update(UpdateAcademicConductSettingRequest $request, AcademicConductSetting $conduct_setting): RedirectResponse
    {
        $this->academicConductSettingService->update($conduct_setting, $request->validated(), $request->user());

        return redirect()
            ->route('academic.conduct.index')
            ->with('success', __('academic.conduct.messages.updated'));
    }

    public function toggleActive(Request $request, AcademicConductSetting $conduct_setting): RedirectResponse
    {
        $this->authorize('disable', $conduct_setting);

        $this->academicConductSettingService->toggleActive($conduct_setting, $request->user());

        return back()->with('success', $conduct_setting->fresh()->is_active
            ? __('academic.conduct.messages.enabled')
            : __('academic.conduct.messages.disabled'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request, AcademicConductSetting $conductSetting, bool $isEdit): array
    {
        $schoolOptions = School::query()->active()->orderBy('name')->get(['id', 'name']);
        $selectedSchoolId = $conductSetting->school_id ?: $this->schoolContext->current()->id;

        return [
            'conductSetting' => $conductSetting,
            'isEdit' => $isEdit,
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(['id', 'name']),
            'levels' => AcademicLevel::query()->with('cycle')->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'conductSubjects' => AcademicSubject::query()->where('school_id', $selectedSchoolId)->where('is_active', true)->where('is_conduct', true)->orderBy('sort_order')->orderBy('name')->get(),
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
