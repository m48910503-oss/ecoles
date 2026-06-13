<?php

namespace App\Http\Controllers\Academic;

use App\Enums\Academic\AcademicPeriodType;
use App\Enums\Academic\CalendarScopeType;
use App\Enums\Academic\SchoolDayType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreAcademicConfigurationRequest;
use App\Http\Requests\Academic\UpdateAcademicConfigurationRequest;
use App\Models\SchoolDayConfiguration;
use App\Models\AcademicConfiguration;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SystemSetting;
use App\Services\Academic\AcademicConfigurationService;
use App\Services\Core\SchoolContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicConfigurationController extends Controller
{
    public function __construct(
        private readonly AcademicConfigurationService $academicConfigurationService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AcademicConfiguration::class);

        $search = trim((string) $request->string('search'));
        $academicYearId = $request->integer('academic_year_id') ?: null;
        $status = trim((string) $request->string('status'));
        $locked = trim((string) $request->string('locked'));
        $schoolOptions = School::query()->active()->orderBy('name')->get(['id', 'name']);
        $canChooseSchool = $this->canChooseSchool($request, $schoolOptions->count());
        $schoolId = $canChooseSchool
            ? ($request->integer('school_id') ?: null)
            : $this->schoolContext->current()->id;

        $baseQuery = AcademicConfiguration::query()
            ->with(['academicYear', 'school', 'cycle', 'coveredCycles'])
            ->withCount('periods')
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->when($search !== '', fn ($query) => $query->whereHas('academicYear', fn ($yearQuery) => $yearQuery->where('name', 'like', "%{$search}%")))
            ->when($academicYearId, fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($locked === 'locked', fn ($query) => $query->where('is_locked', true))
            ->when($locked === 'unlocked', fn ($query) => $query->where('is_locked', false))
            ->orderByDesc('is_active')
            ->orderByDesc('academic_year_id')
            ->orderByRaw("CASE scope_type WHEN 'global' THEN 0 WHEN 'cycle' THEN 1 ELSE 2 END")
            ->orderBy('cycle_id');

        $configurations = (clone $baseQuery)
            ->paginate(12)
            ->withQueryString();

        return view('academic.configuration.index', [
            'configurations' => $configurations,
            'search' => $search,
            'academicYearId' => $academicYearId,
            'status' => $status,
            'locked' => $locked,
            'schoolId' => $schoolId,
            'schools' => $schoolOptions,
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(['id', 'name']),
            'canChooseSchool' => $canChooseSchool,
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'active' => (clone $baseQuery)->where('is_active', true)->count(),
                'locked' => (clone $baseQuery)->where('is_locked', true)->count(),
                'periods' => (clone $baseQuery)->sum('period_count'),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', AcademicConfiguration::class);

        $configuration = new AcademicConfiguration();
        $configuration->academic_year_id = $request->integer('academic_year_id') ?: null;
        $configuration->scope_type = $request->string('scope_type')->toString() ?: CalendarScopeType::Global->value;
        $configuration->cycle_id = $request->integer('cycle_id') ?: null;

        return view('academic.configuration.form', $this->formData($request, $configuration, false));
    }

    public function store(StoreAcademicConfigurationRequest $request): RedirectResponse
    {
        $this->academicConfigurationService->create($request->validated(), $request->user());

        return redirect()
            ->route('academic.configuration.index')
            ->with('success', __('academic.configuration.messages.created'));
    }

    public function edit(Request $request, AcademicConfiguration $configuration): View
    {
        $this->authorize('update', $configuration);

        return view('academic.configuration.form', $this->formData($request, $configuration, true));
    }

    public function update(UpdateAcademicConfigurationRequest $request, AcademicConfiguration $configuration): RedirectResponse
    {
        $this->academicConfigurationService->update($configuration, $request->validated(), $request->user());

        return redirect()
            ->route('academic.configuration.index')
            ->with('success', __('academic.configuration.messages.updated'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Request $request, AcademicConfiguration $configuration, bool $isEdit): array
    {
        $schoolOptions = School::query()->active()->orderBy('name')->get(['id', 'name']);
        $academicYears = AcademicYear::query()
            ->with(['cycles' => fn ($query) => $query->orderBy('sort_order')->orderBy('name')])
            ->orderByDesc('start_date')
            ->get(['id', 'name']);
        $selectedAcademicYearId = old('academic_year_id', $configuration->academic_year_id ?: $request->integer('academic_year_id') ?: $academicYears->first()?->id);
        $selectedScopeType = old('scope_type', $configuration->scope_type?->value ?? CalendarScopeType::Global->value);
        $selectedCycleIds = collect(old('cycle_ids', []));

        if ($selectedCycleIds->isEmpty()) {
            $selectedCycleIds = collect(
                $configuration->exists
                    ? $configuration->resolvedCoveredCycleIds()
                    : array_filter([(int) $request->integer('cycle_id') ?: null])
            );
        }

        $selectedCycleIds = $selectedCycleIds
            ->map(fn ($cycleId) => (int) $cycleId)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
        $selectedAcademicYear = $selectedAcademicYearId
            ? $academicYears->firstWhere('id', (int) $selectedAcademicYearId)
            : null;
        $previewCycleId = $selectedCycleIds[0] ?? null;

        return [
            'configuration' => $configuration,
            'isEdit' => $isEdit,
            'academicYears' => $academicYears,
            'scopeOptions' => [CalendarScopeType::Global, CalendarScopeType::Cycle],
            'periodTypes' => AcademicPeriodType::cases(),
            'schoolDayTypes' => SchoolDayType::cases(),
            'schools' => $schoolOptions,
            'canChooseSchool' => $this->canChooseSchool($request, $schoolOptions->count()),
            'currentSchoolId' => $configuration->school_id ?: $this->schoolContext->current()->id,
            'selectedAcademicYearId' => $selectedAcademicYearId,
            'selectedScopeType' => $selectedScopeType,
            'selectedCycleIds' => $selectedCycleIds,
            'dayConfigurationModel' => $configuration->exists
                ? SchoolDayConfiguration::query()
                    ->where('school_id', $configuration->school_id ?: $this->schoolContext->current()->id)
                    ->where('academic_year_id', $configuration->academic_year_id)
                    ->where('scope_type', $configuration->scope_type === CalendarScopeType::Cycle ? CalendarScopeType::Cycle->value : ($configuration->scope_type?->value ?? CalendarScopeType::Global->value))
                    ->when(
                        $configuration->scope_type === CalendarScopeType::Cycle,
                        fn ($query) => $query->where('cycle_id', $previewCycleId),
                        fn ($query) => $query->whereNull('cycle_id'),
                    )
                    ->first()
                : (
                    $selectedAcademicYear && $selectedScopeType === CalendarScopeType::Cycle->value && $previewCycleId
                        ? SchoolDayConfiguration::query()
                            ->where('school_id', $configuration->school_id ?: $this->schoolContext->current()->id)
                            ->where('academic_year_id', (int) $selectedAcademicYearId)
                            ->where('scope_type', CalendarScopeType::Cycle->value)
                            ->where('cycle_id', (int) $previewCycleId)
                            ->first()
                        : SchoolDayConfiguration::query()
                            ->where('school_id', $configuration->school_id ?: $this->schoolContext->current()->id)
                            ->where('academic_year_id', $selectedAcademicYearId)
                            ->where('scope_type', CalendarScopeType::Global->value)
                            ->whereNull('cycle_id')
                            ->first()
                ),
        ];
    }

    private function canChooseSchool(Request $request, int $schoolCount): bool
    {
        return $schoolCount > 1
            && $this->schoolContext->isMultiSchoolEnabled()
            && ($request->user()?->can('manageMultiSchool', SystemSetting::class) ?? false);
    }
}
