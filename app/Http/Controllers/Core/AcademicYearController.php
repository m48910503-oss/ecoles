<?php

namespace App\Http\Controllers\Core;

use App\Enums\Academic\AcademicCycleCategory;
use App\Enums\Academic\AcademicPeriodType;
use App\Enums\Academic\CalendarEventStatus;
use App\Enums\Academic\CalendarScopeType;
use App\Enums\Academic\SchoolDayType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Core\StoreAcademicYearStepOneRequest;
use App\Http\Requests\Core\StoreAcademicYearStepTwoRequest;
use App\Http\Requests\Core\StoreAcademicYearStepThreeRequest;
use App\Http\Requests\Core\StoreAcademicYearRequest;
use App\Http\Requests\Core\UpdateAcademicYearRequest;
use App\Models\AcademicCalendar;
use App\Models\AcademicClassroom;
use App\Models\AcademicConfiguration;
use App\Models\AcademicCycle;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolDayConfiguration;
use App\Services\Core\AcademicYearService;
use App\Services\Core\SchoolContext;
use App\Support\AcademicYearWizard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    public function __construct(
        private readonly AcademicYearService $academicYearService,
        private readonly SchoolContext $schoolContext,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AcademicYear::class);

        $search = trim((string) $request->string('search'));
        $status = trim((string) $request->string('status'));
        $enrollment = trim((string) $request->string('enrollment'));

        $academicYears = AcademicYear::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($enrollment === 'open', fn ($query) => $query->where('enrollment_open', true))
            ->when($enrollment === 'closed', fn ($query) => $query->where('enrollment_open', false))
            ->orderByDesc('is_active')
            ->orderByDesc('start_date')
            ->paginate(12)
            ->withQueryString();

        return view('core.academic-years.index', [
            'academicYears' => $academicYears,
            'search' => $search,
            'status' => $status,
            'enrollment' => $enrollment,
            'stats' => [
                'total' => AcademicYear::query()->count(),
                'active' => AcademicYear::query()->where('is_active', true)->count(),
                'open' => AcademicYear::query()->where('enrollment_open', true)->count(),
                'closed' => AcademicYear::query()->where('enrollment_open', false)->count(),
            ],
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('create', AcademicYear::class);

        if ($request->boolean('reset')) {
            $this->clearWizardState($request);
        }

        $requestedStep = $request->integer('wizard_step');

        if ($requestedStep === 1) {
            return $this->renderWizardStep($request, 1);
        }

        $step = $this->resolveWizardStep($this->wizardState($request));

        if ($step > 1) {
            return redirect()->route($this->wizardRouteForStep($step));
        }

        return $this->renderWizardStep($request, 1);
    }

    public function storeStepOne(StoreAcademicYearStepOneRequest $request): RedirectResponse
    {
        $this->saveWizardState($request, $request->validated());

        return redirect()->route('core.academic-years.create.step2');
    }

    public function createStepTwo(Request $request): View|RedirectResponse
    {
        $this->authorize('create', AcademicYear::class);

        if ($redirect = $this->redirectIfWizardIncomplete($request, 2)) {
            return $redirect;
        }

        return $this->renderWizardStep($request, 2);
    }

    public function storeStepTwo(StoreAcademicYearStepTwoRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (! AcademicCycle::query()->exists()) {
            $data['cycle_ids'] = [];
        }

        $this->saveWizardState($request, $data);

        return redirect()->route('core.academic-years.create.step3');
    }

    public function createStepThree(Request $request): View|RedirectResponse
    {
        $this->authorize('create', AcademicYear::class);

        if ($redirect = $this->redirectIfWizardIncomplete($request, 3)) {
            return $redirect;
        }

        return $this->renderWizardStep($request, 3);
    }

    public function storeStepThree(StoreAcademicYearStepThreeRequest $request): RedirectResponse
    {
        $this->saveWizardState($request, $request->validated());

        return redirect()->route('core.academic-years.create.step4');
    }

    public function createStepFour(Request $request): View|RedirectResponse
    {
        $this->authorize('create', AcademicYear::class);

        if ($redirect = $this->redirectIfWizardIncomplete($request, 4)) {
            return $redirect;
        }

        return $this->renderWizardStep($request, 4);
    }

    public function store(StoreAcademicYearRequest $request): RedirectResponse
    {
        $this->academicYearService->create($request->validated(), $request->user());
        $this->clearWizardState($request);

        return redirect()
            ->route('core.academic-years.index')
            ->with('success', __('core.academic_years.messages.created'));
    }

    /**
     * @return View|RedirectResponse
     */
    private function renderWizardStep(Request $request, int $step): View|RedirectResponse
    {
        $state = $this->wizardState($request);
        $selectedCycles = $this->selectedCyclesFromWizardState($state);

        return view('core.academic-years.wizard', [
            'academicYear' => new AcademicYear(),
            'step' => $step,
            'wizardSteps' => $this->wizardSteps($step),
            'wizardState' => $state,
            'wizardSummary' => $this->wizardSummary($state, $selectedCycles),
            'hasActiveAcademicYear' => AcademicYear::query()->where('is_active', true)->exists(),
            'cycles' => $this->availableCycles(),
            'selectedCycles' => $selectedCycles,
            'hasCycles' => AcademicCycle::query()->exists(),
            'periodTypes' => AcademicPeriodType::cases(),
            'schoolDayTypes' => SchoolDayType::cases(),
            'configuration' => null,
            'dayConfiguration' => null,
            'currentSchoolId' => $this->schoolContext->current()->id,
            'canManageMultiSchool' => $request->user()?->can('manageMultiSchool', \App\Models\SystemSetting::class) ?? false,
        ]);
    }

    private function wizardState(Request $request): array
    {
        $state = $request->session()->get(AcademicYearWizard::SESSION_KEY, []);

        return is_array($state) ? $state : [];
    }

    private function saveWizardState(Request $request, array $data): void
    {
        $request->session()->put(
            AcademicYearWizard::SESSION_KEY,
            array_merge($this->wizardState($request), $data)
        );
    }

    private function clearWizardState(Request $request): void
    {
        $request->session()->forget(AcademicYearWizard::SESSION_KEY);
    }

    private function resolveWizardStep(array $state): int
    {
        if (! filled($state['start_date'] ?? null) || ! filled($state['end_date'] ?? null)) {
            return 1;
        }

        if (AcademicCycle::query()->exists()) {
            $cycleIds = $state['cycle_ids'] ?? null;

            if (! is_array($cycleIds) || collect($cycleIds)->filter(static fn ($cycleId): bool => filled($cycleId))->isEmpty()) {
                return 2;
            }
        }

        if (! array_key_exists('is_active', $state) || ! array_key_exists('enrollment_open', $state)) {
            return 3;
        }

        return 4;
    }

    private function redirectIfWizardIncomplete(Request $request, int $requestedStep): ?RedirectResponse
    {
        $currentStep = $this->resolveWizardStep($this->wizardState($request));

        if ($currentStep < $requestedStep) {
            return redirect()->route($this->wizardRouteForStep($currentStep));
        }

        return null;
    }

    private function wizardRouteForStep(int $step): string
    {
        return match ($step) {
            1 => 'core.academic-years.create',
            2 => 'core.academic-years.create.step2',
            3 => 'core.academic-years.create.step3',
            default => 'core.academic-years.create.step4',
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function wizardSteps(int $currentStep): array
    {
        $steps = [
            [
                'number' => 1,
                'title' => __('core.academic_years.wizard.steps.general.title'),
                'description' => __('core.academic_years.wizard.steps.general.description'),
                'route' => route('core.academic-years.create'),
            ],
            [
                'number' => 2,
                'title' => __('core.academic_years.wizard.steps.cycles.title'),
                'description' => __('core.academic_years.wizard.steps.cycles.description'),
                'route' => route('core.academic-years.create.step2'),
            ],
            [
                'number' => 3,
                'title' => __('core.academic_years.wizard.steps.activation.title'),
                'description' => __('core.academic_years.wizard.steps.activation.description'),
                'route' => route('core.academic-years.create.step3'),
            ],
            [
                'number' => 4,
                'title' => __('core.academic_years.wizard.steps.configuration.title'),
                'description' => __('core.academic_years.wizard.steps.configuration.description'),
                'route' => route('core.academic-years.create.step4'),
            ],
        ];

        return array_map(static function (array $step) use ($currentStep): array {
            $step['status'] = match (true) {
                $step['number'] < $currentStep => 'completed',
                $step['number'] === $currentStep => 'current',
                default => 'pending',
            };
            $step['href'] = $step['number'] <= $currentStep ? $step['route'] : null;

            return $step;
        }, $steps);
    }

    /**
     * @param  array<string, mixed>  $state
     * @return Collection<int, AcademicCycle>
     */
    private function selectedCyclesFromWizardState(array $state): Collection
    {
        $cycleIds = collect($state['cycle_ids'] ?? [])
            ->map(static fn ($cycleId) => (int) $cycleId)
            ->filter(static fn (int $cycleId): bool => $cycleId > 0)
            ->values()
            ->all();

        if ($cycleIds === []) {
            return AcademicCycle::query()
                ->active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        return AcademicCycle::query()
            ->whereIn('id', $cycleIds)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function wizardSummary(array $state, Collection $selectedCycles): array
    {
        $startDate = $state['start_date'] ?? null;
        $endDate = $state['end_date'] ?? null;

        return [
            'year_label' => $this->buildYearLabel($startDate, $endDate),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => $state['notes'] ?? null,
            'selected_cycles' => $selectedCycles,
            'is_active' => (bool) ($state['is_active'] ?? false),
            'enrollment_open' => (bool) ($state['enrollment_open'] ?? false),
            'multi_school_enabled' => (bool) ($state['multi_school_enabled'] ?? false),
        ];
    }

    private function buildYearLabel(?string $startDate, ?string $endDate): string
    {
        if (! filled($startDate) || ! filled($endDate)) {
            return '—';
        }

        return date('Y', strtotime($startDate)).'-'.date('Y', strtotime($endDate));
    }

    public function show(Request $request, AcademicYear $academicYear): View
    {
        $this->authorize('view', $academicYear);
        $this->academicYearService->ensureDefaultGlobalCalendar($academicYear, $request->user());

        $currentSchool = $this->schoolContext->current();
        $selectedCycles = $this->selectedCyclesForYear($academicYear);

        $configurationQuery = AcademicConfiguration::query()
            ->with(['periods' => fn ($query) => $query->orderBy('position'), 'cycle', 'coveredCycles'])
            ->where('school_id', $currentSchool->id)
            ->where('academic_year_id', $academicYear->id);

        $configuration = (clone $configurationQuery)
            ->where('scope_type', CalendarScopeType::Global->value)
            ->whereNull('cycle_id')
            ->first()
            ?? (clone $configurationQuery)
                ->where('is_active', true)
                ->orderByRaw("CASE scope_type WHEN 'global' THEN 0 WHEN 'cycle' THEN 1 ELSE 2 END")
                ->orderBy('id')
                ->first()
            ?? (clone $configurationQuery)
                ->orderByRaw("CASE scope_type WHEN 'global' THEN 0 WHEN 'cycle' THEN 1 ELSE 2 END")
                ->orderBy('id')
                ->first();

        $calendarQuery = AcademicCalendar::query()
            ->with(['creator', 'validator', 'coveredCycles', 'periods' => fn ($query) => $query->orderBy('position')])
            ->withCount([
                'events as published_events_count' => fn ($query) => $query->where('status', CalendarEventStatus::Published->value),
                'events as upcoming_events_count' => fn ($query) => $query
                    ->where('status', CalendarEventStatus::Published->value)
                    ->where('start_datetime', '>=', now()),
            ])
            ->where('school_id', $currentSchool->id)
            ->where('academic_year_id', $academicYear->id);

        $calendar = (clone $calendarQuery)
            ->where('scope_type', CalendarScopeType::Global->value)
            ->whereNull('cycle_id')
            ->first()
            ?? (clone $calendarQuery)
                ->where('is_active', true)
                ->orderByRaw("CASE scope_type WHEN 'global' THEN 0 WHEN 'cycle' THEN 1 WHEN 'level' THEN 2 WHEN 'class' THEN 3 ELSE 4 END")
                ->orderBy('id')
                ->first()
            ?? (clone $calendarQuery)
                ->orderByRaw("CASE scope_type WHEN 'global' THEN 0 WHEN 'cycle' THEN 1 WHEN 'level' THEN 2 WHEN 'class' THEN 3 ELSE 4 END")
                ->orderBy('id')
                ->first();

        $cycleGroups = $this->buildCycleGroups($academicYear, $currentSchool, $selectedCycles->pluck('id')->all());

        return view('core.academic-years.show', [
            'academicYear' => $academicYear,
            'currentSchool' => $currentSchool,
            'configuration' => $configuration,
            'calendar' => $calendar,
            'periods' => $calendar?->periods ?? ($configuration?->periods ?? collect()),
            'selectedCycles' => $selectedCycles,
            'cycleGroups' => $cycleGroups,
        ]);
    }

    public function showSector(Request $request, AcademicYear $academicYear, string $group): View
    {
        $this->authorize('view', $academicYear);

        $currentSchool = $this->schoolContext->current();
        $groupKey = $this->resolveCycleGroupKey($group);
        $selectedCycles = $this->selectedCyclesForYear($academicYear);

        abort_unless($groupKey !== null, 404);

        return view('core.academic-years.sector', [
            'academicYear' => $academicYear,
            'currentSchool' => $currentSchool,
            'cycleGroup' => $this->buildCycleGroup($academicYear, $currentSchool, $groupKey, $selectedCycles->pluck('id')->all()),
        ]);
    }

    public function edit(Request $request, AcademicYear $academicYear): View
    {
        $this->authorize('update', $academicYear);

        $cycles = $this->availableCycles();
        $selectedCycles = $this->selectedCyclesForYear($academicYear);
        $configuration = $academicYear->academicConfigurations()
            ->where('school_id', $this->schoolContext->current()->id)
            ->where('scope_type', CalendarScopeType::Global->value)
            ->whereNull('cycle_id')
            ->first();

        return view('core.academic-years.form', [
            'academicYear' => $academicYear,
            'isEdit' => true,
            'hasActiveAcademicYear' => AcademicYear::query()
                ->where('is_active', true)
                ->whereKeyNot($academicYear->id)
                ->exists(),
            'configuration' => $configuration,
            'dayConfiguration' => SchoolDayConfiguration::query()
                ->where('school_id', $this->schoolContext->current()->id)
                ->where('academic_year_id', $academicYear->id)
                ->where('scope_type', 'global')
                ->first(),
            'cycles' => $cycles,
            'selectedCycles' => $selectedCycles,
            'periodTypes' => AcademicPeriodType::cases(),
            'schoolDayTypes' => SchoolDayType::cases(),
            'configureNowDefault' => $request->boolean('configure') || $configuration !== null,
            'currentSchoolId' => $this->schoolContext->current()->id,
        ]);
    }

    public function update(UpdateAcademicYearRequest $request, AcademicYear $academicYear): RedirectResponse
    {
        $this->academicYearService->update($academicYear, $request->validated(), $request->user());

        return redirect()
            ->route('core.academic-years.index')
            ->with('success', __('core.academic_years.messages.updated'));
    }

    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        $this->authorize('delete', $academicYear);

        $this->academicYearService->delete($academicYear, request()->user());

        return redirect()
            ->route('core.academic-years.index')
            ->with('success', __('core.academic_years.messages.deleted'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildCycleGroups(AcademicYear $academicYear, School $currentSchool, array $selectedCycleIds): array
    {
        return collect(array_keys($this->cycleGroupDefinitions()))
            ->map(fn (string $group) => $this->buildCycleGroup($academicYear, $currentSchool, $group, $selectedCycleIds))
            ->values()
            ->all();
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function cycleGroupDefinitions(): array
    {
        return [
            'preschool' => [
                'tone' => 'amber',
                'icon' => 'fa-baby',
                'description' => __('core.academic_years.show.cycle_groups.preschool_description'),
            ],
            'primary' => [
                'tone' => 'emerald',
                'icon' => 'fa-book-open-reader',
                'description' => __('core.academic_years.show.cycle_groups.primary_description'),
            ],
            'secondary' => [
                'tone' => 'violet',
                'icon' => 'fa-graduation-cap',
                'description' => __('core.academic_years.show.cycle_groups.secondary_description'),
            ],
        ];
    }

    private function resolveCycleGroupKey(string $group): ?string
    {
        return array_key_exists($group, $this->cycleGroupDefinitions()) ? $group : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCycleGroup(AcademicYear $academicYear, School $currentSchool, string $group, array $selectedCycleIds): array
    {
        $definitions = $this->cycleGroupDefinitions();
        $definition = $definitions[$group] ?? $definitions['secondary'];

        $cycles = AcademicCycle::query()
            ->withCount([
                'levels',
                'classrooms as classrooms_count' => fn ($query) => $query
                    ->where('school_id', $currentSchool->id)
                    ->where('academic_year_id', $academicYear->id),
            ])
            ->whereIn('category', AcademicCycleCategory::managementGroupCategories($group))
            ->whereIn('id', $selectedCycleIds)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $classroomsCount = AcademicClassroom::query()
            ->where('school_id', $currentSchool->id)
            ->where('academic_year_id', $academicYear->id)
            ->whereHas('level', fn ($query) => $query->whereIn('cycle_id', $cycles->pluck('id')))
            ->count();

        $calendars = AcademicCalendar::query()
            ->with(['cycle', 'coveredCycles'])
            ->withCount([
                'periods',
                'events as published_events_count' => fn ($query) => $query->where('status', CalendarEventStatus::Published->value),
            ])
            ->where('school_id', $currentSchool->id)
            ->where('academic_year_id', $academicYear->id)
            ->where('scope_type', CalendarScopeType::Cycle->value)
            ->where(function ($query) use ($cycles): void {
                $query
                    ->whereHas('coveredCycles', fn ($cycleQuery) => $cycleQuery->whereIn('academic_cycles.id', $cycles->pluck('id')))
                    ->orWhereIn('cycle_id', $cycles->pluck('id'));
            })
            ->orderBy('name')
            ->get();

        $calendarCount = $calendars->count();
        $validatedCount = $calendars->whereNotNull('validated_at')->count();

        $statusLabel = match (true) {
            $calendarCount === 0 => __('core.academic_years.show.not_generated'),
            $validatedCount === $calendarCount => __('core.academic_years.show.validated'),
            default => __('core.academic_years.show.cycle_management_validation_progress', [
                'validated' => $validatedCount,
                'total' => $calendarCount,
            ]),
        };

        $statusTone = match (true) {
            $calendarCount === 0 => 'slate',
            $validatedCount === $calendarCount => 'emerald',
            default => 'amber',
        };

        $needsCalendar = $calendarCount === 0;
        $createCalendarRoute = route('academic.calendars.create', [
            'academic_year_id' => $academicYear->id,
            'group' => $group,
            'scope_type' => CalendarScopeType::Cycle->value,
            'from_periods' => 1,
        ]);
        $managePeriodsRoute = route('academic.calendars.periods.index', [
            'academic_year_id' => $academicYear->id,
            'group' => $group,
        ]);
        $createEventRoute = route('academic.events.create', [
            'academic_year_id' => $academicYear->id,
            'group' => $group,
        ]);
        $isSingleCycle = $cycles->count() === 1;
        $primaryCycle = $isSingleCycle ? $cycles->first() : null;
        $manageCyclesRoute = $primaryCycle
            ? route('academic.cycles.show', $primaryCycle)
            : route('academic.cycles.index', ['group' => $group]);
        $cycleConfigurationRoute = $primaryCycle
            ? route('core.academic-years.cycles.show', ['academic_year' => $academicYear, 'cycle' => $primaryCycle])
            : null;
        $manageCyclesLabel = $isSingleCycle
            ? __('core.academic_years.show.cycle_management_open_cycle_action')
            : __('core.academic_years.show.cycle_management_manage_action');

        return [
            'key' => $group,
            'label' => AcademicCycleCategory::managementGroups()[$group] ?? $group,
            'description' => $definition['description'],
            'tone' => $definition['tone'],
            'icon' => $definition['icon'],
            'cycles_count' => $cycles->count(),
            'levels_count' => $cycles->sum('levels_count'),
            'classrooms_count' => $classroomsCount,
            'calendars_count' => $calendarCount,
            'periods_count' => $calendars->sum('periods_count'),
            'validated_count' => $validatedCount,
            'cycles' => $cycles,
            'is_single_cycle' => $isSingleCycle,
            'current_cycle' => $primaryCycle,
            'calendars' => $calendars,
            'needs_calendar' => $needsCalendar,
            'primary_action' => [
                'badge' => __('core.academic_years.show.next_step_label'),
                'title' => __('core.academic_years.show.calendar_not_generated_label'),
                'description' => $isSingleCycle
                    ? __('core.academic_years.show.cycle_focus_description')
                    : __('core.academic_years.show.sector_focus_description', [
                        'cycle' => AcademicCycleCategory::managementGroups()[$group] ?? $group,
                    ]),
                'href' => $createCalendarRoute,
                'action' => __('core.academic_years.show.sector_actions_create_calendar_action'),
            ],
            'quick_actions' => [
                [
                    'key' => 'create_calendar',
                    'label' => __('core.academic_years.show.sector_actions_create_calendar_title'),
                    'description' => __('core.academic_years.show.sector_actions_create_calendar_description'),
                    'action' => __('core.academic_years.show.sector_actions_create_calendar_action'),
                    'href' => $createCalendarRoute,
                    'icon' => 'fa-calendar-plus',
                    'tone' => 'sky',
                    'ability' => 'create',
                    'model' => AcademicCalendar::class,
                    'is_available' => true,
                ],
                [
                    'key' => 'manage_periods',
                    'label' => __('core.academic_years.show.sector_actions_manage_periods_title'),
                    'description' => __('core.academic_years.show.sector_actions_manage_periods_description'),
                    'action' => __('core.academic_years.show.sector_actions_manage_periods_action'),
                    'href' => $managePeriodsRoute,
                    'icon' => 'fa-wand-magic-sparkles',
                    'tone' => 'amber',
                    'ability' => 'viewAny',
                    'model' => AcademicCalendar::class,
                    'is_available' => ! $needsCalendar,
                    'hint' => $needsCalendar ? __('core.academic_years.show.sector_actions_available_after_calendar') : null,
                ],
                [
                    'key' => 'create_event',
                    'label' => __('core.academic_years.show.sector_actions_create_event_title'),
                    'description' => __('core.academic_years.show.sector_actions_create_event_description'),
                    'action' => __('core.academic_years.show.sector_actions_create_event_action'),
                    'href' => $createEventRoute,
                    'icon' => 'fa-bullhorn',
                    'tone' => 'emerald',
                    'ability' => 'create',
                    'model' => \App\Models\CalendarEvent::class,
                    'is_available' => ! $needsCalendar,
                    'hint' => $needsCalendar ? __('core.academic_years.show.sector_actions_available_after_calendar') : null,
                ],
                [
                    'key' => 'manage_cycles',
                    'label' => $isSingleCycle
                        ? __('core.academic_years.show.sector_actions_manage_cycle_title')
                        : __('core.academic_years.show.sector_actions_manage_cycles_title'),
                    'description' => $isSingleCycle
                        ? __('core.academic_years.show.sector_actions_manage_cycle_description')
                        : __('core.academic_years.show.sector_actions_manage_cycles_description'),
                    'action' => $isSingleCycle
                        ? __('core.academic_years.show.sector_actions_manage_cycle_action')
                        : __('core.academic_years.show.sector_actions_manage_cycles_action'),
                    'href' => $manageCyclesRoute,
                    'icon' => 'fa-diagram-project',
                    'tone' => 'violet',
                    'ability' => 'viewAny',
                    'model' => AcademicCycle::class,
                    'is_available' => true,
                ],
            ],
            'status_label' => $statusLabel,
            'status_tone' => $statusTone,
            'sector_route' => $cycleConfigurationRoute ?: route('core.academic-years.sectors.show', ['academic_year' => $academicYear, 'group' => $group]),
            'cycle_configuration_route' => $cycleConfigurationRoute,
            'create_calendar_route' => $createCalendarRoute,
            'manage_periods_route' => $managePeriodsRoute,
            'create_event_route' => $createEventRoute,
            'manage_cycles_route' => $manageCyclesRoute,
            'manage_cycle_action_label' => $manageCyclesLabel,
            'back_route' => route('core.academic-years.show', $academicYear),
        ];
    }

    /**
     * @return Collection<int, AcademicCycle>
     */
    private function availableCycles(): Collection
    {
        return AcademicCycle::query()
            ->orderByDesc('is_active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, AcademicCycle>
     */
    private function selectedCyclesForYear(AcademicYear $academicYear): Collection
    {
        $selectedCycles = $academicYear->cycles()->get();

        if ($selectedCycles->isNotEmpty()) {
            return $selectedCycles;
        }

        return AcademicCycle::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
