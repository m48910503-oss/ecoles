<?php

namespace App\Http\Controllers\Academic;

use App\Enums\Academic\CalendarScopeType;
use App\Enums\Academic\SchoolDayType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreSchoolDayConfigurationRequest;
use App\Http\Requests\Academic\UpdateSchoolDayConfigurationRequest;
use App\Models\AcademicCycle;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolDayConfiguration;
use App\Models\SystemSetting;
use App\Services\Academic\SchoolDayConfigurationService;
use App\Services\Core\SchoolContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchoolDayConfigurationController extends Controller
{
    public function __construct(
        private readonly SchoolDayConfigurationService $schoolDayConfigurationService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', SchoolDayConfiguration::class);

        $academicYearId = $request->integer('academic_year_id') ?: null;
        $scopeType = trim((string) $request->string('scope_type'));
        $dayType = trim((string) $request->string('day_type'));
        $status = trim((string) $request->string('status'));
        $schoolOptions = School::query()->active()->orderBy('name')->get(['id', 'name']);
        $canChooseSchool = $this->canChooseSchool($request, $schoolOptions->count());
        $schoolId = $canChooseSchool
            ? ($request->integer('school_id') ?: null)
            : $this->schoolContext->current()->id;

        $baseQuery = SchoolDayConfiguration::query()
            ->with(['academicYear', 'school', 'cycle'])
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->when($academicYearId, fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->when($scopeType !== '', fn ($query) => $query->where('scope_type', $scopeType))
            ->when($dayType !== '', fn ($query) => $query->where('day_type', $dayType))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false));

        $dayConfigurations = (clone $baseQuery)
            ->orderByDesc('academic_year_id')
            ->orderByDesc('is_active')
            ->paginate(12)
            ->withQueryString();

        return view('academic.day-configurations.index', [
            'dayConfigurations' => $dayConfigurations,
            'academicYearId' => $academicYearId,
            'scopeType' => $scopeType,
            'dayType' => $dayType,
            'status' => $status,
            'schoolId' => $schoolId,
            'schools' => $schoolOptions,
            'canChooseSchool' => $canChooseSchool,
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(['id', 'name']),
            'scopeOptions' => [CalendarScopeType::Global, CalendarScopeType::Cycle],
            'dayTypeOptions' => SchoolDayType::cases(),
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'continuous' => (clone $baseQuery)->where('day_type', SchoolDayType::Continuous->value)->count(),
                'discontinuous' => (clone $baseQuery)->where('day_type', SchoolDayType::Discontinuous->value)->count(),
                'locked' => (clone $baseQuery)->where('is_locked', true)->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', SchoolDayConfiguration::class);

        return view('academic.day-configurations.form', $this->formData($request, new SchoolDayConfiguration(), false));
    }

    public function store(StoreSchoolDayConfigurationRequest $request): RedirectResponse
    {
        $this->schoolDayConfigurationService->create($request->validated(), $request->user());

        return redirect()
            ->route('academic.day-configurations.index')
            ->with('success', __('academic.day_config.messages.created'));
    }

    public function show(SchoolDayConfiguration $dayConfiguration): View
    {
        $this->authorize('view', $dayConfiguration);

        return view('academic.day-configurations.show', [
            'dayConfiguration' => $dayConfiguration->load(['academicYear', 'school', 'cycle', 'creator', 'updater']),
        ]);
    }

    public function edit(Request $request, SchoolDayConfiguration $dayConfiguration): View
    {
        $this->authorize('update', $dayConfiguration);

        return view('academic.day-configurations.form', $this->formData($request, $dayConfiguration, true));
    }

    public function update(UpdateSchoolDayConfigurationRequest $request, SchoolDayConfiguration $dayConfiguration): RedirectResponse
    {
        $this->schoolDayConfigurationService->update($dayConfiguration, $request->validated(), $request->user());

        return redirect()
            ->route('academic.day-configurations.index')
            ->with('success', __('academic.day_config.messages.updated'));
    }

    public function toggleLock(Request $request, SchoolDayConfiguration $dayConfiguration): RedirectResponse
    {
        $this->authorize('lock', $dayConfiguration);

        $this->schoolDayConfigurationService->toggleLock($dayConfiguration, $request->user());

        return back()->with('success', $dayConfiguration->fresh()->is_locked
            ? __('academic.day_config.messages.locked')
            : __('academic.day_config.messages.unlocked'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request, SchoolDayConfiguration $dayConfiguration, bool $isEdit): array
    {
        $schoolOptions = School::query()->active()->orderBy('name')->get(['id', 'name']);

        return [
            'dayConfiguration' => $dayConfiguration,
            'isEdit' => $isEdit,
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(['id', 'name']),
            'cycles' => AcademicCycle::query()->where('is_active', true)->orderBy('sort_order')->get(['id', 'name']),
            'scopeOptions' => [CalendarScopeType::Global, CalendarScopeType::Cycle],
            'dayTypeOptions' => SchoolDayType::cases(),
            'schools' => $schoolOptions,
            'canChooseSchool' => $this->canChooseSchool($request, $schoolOptions->count()),
            'currentSchoolId' => $dayConfiguration->school_id ?: $this->schoolContext->current()->id,
        ];
    }

    private function canChooseSchool(Request $request, int $schoolCount): bool
    {
        return $schoolCount > 1
            && $this->schoolContext->isMultiSchoolEnabled()
            && ($request->user()?->can('manageMultiSchool', SystemSetting::class) ?? false);
    }
}
