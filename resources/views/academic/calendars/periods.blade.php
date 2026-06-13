<x-app-layout>
    @php
        $hasSelectedAcademicYear = $selectedAcademicYear !== null;
        $hasSelectedCalendar = $selectedCalendar !== null;
        $hasPeriods = $periods->isNotEmpty();
        $calendarValidated = $hasSelectedCalendar && $selectedCalendar->validated_at !== null;
        $periodDatesComplete = $hasPeriods && $periods->every(fn ($period): bool => $period->start_date !== null && $period->end_date !== null);
        $canGeneratePeriods = $hasSelectedCalendar && $configuration !== null;
        $canSaveAndValidate = $hasPeriods && $configuration !== null;
        $canCreateEvents = $hasSelectedCalendar && $hasPeriods && $calendarValidated;

        $selectedSchool = $schools->firstWhere('id', $schoolId);
        $selectedSchoolName = $selectedSchool?->name ?? __('core.settings.organization.current_school_label');
        $yearHubRoute = $hasSelectedAcademicYear ? route('core.academic-years.show', $selectedAcademicYear) : null;

        $filters = array_filter([
            'academic_year_id' => $selectedAcademicYearId ?: null,
            'academic_calendar_id' => $hasSelectedCalendar ? $selectedCalendar->id : null,
            'school_id' => $schoolId ?: null,
            'group' => $group,
        ]);

        $calendarStatusLabel = $hasSelectedCalendar
            ? ($calendarValidated ? __('academic.calendars.show.validated') : __('academic.calendars.show.pending_validation'))
            : __('core.academic_years.show.not_generated');
        $calendarStatusTone = $hasSelectedCalendar
            ? ($calendarValidated ? 'emerald' : 'amber')
            : 'slate';

        $workflowSteps = [
            [
                'icon' => 'fa-calendar-days',
                'title' => __('academic.calendars.periods.workflow_step_1_title'),
                'description' => __('academic.calendars.periods.workflow_step_1_description'),
                'done' => $hasSelectedCalendar,
            ],
            [
                'icon' => 'fa-wand-magic-sparkles',
                'title' => __('academic.calendars.periods.workflow_step_2_title'),
                'description' => __('academic.calendars.periods.workflow_step_2_description'),
                'done' => $hasPeriods,
            ],
            [
                'icon' => 'fa-pen-to-square',
                'title' => __('academic.calendars.periods.workflow_step_3_title'),
                'description' => __('academic.calendars.periods.workflow_step_3_description'),
                'done' => $periodDatesComplete,
            ],
            [
                'icon' => 'fa-circle-check',
                'title' => __('academic.calendars.periods.workflow_step_4_title'),
                'description' => __('academic.calendars.periods.workflow_step_4_description'),
                'done' => $calendarValidated,
            ],
        ];

        $completedSteps = collect($workflowSteps)->where('done', true)->count();
        $workflowCompleted = $completedSteps === count($workflowSteps);
        $workflowStatusLabel = $workflowCompleted
            ? __('academic.calendars.periods.workflow_status_complete', ['completed' => $completedSteps, 'total' => count($workflowSteps)])
            : __('academic.calendars.periods.workflow_status_progress', ['completed' => $completedSteps, 'total' => count($workflowSteps)]);
        $hasUnlockedPeriods = $hasPeriods && $periods->contains(fn ($period): bool => ! $period->is_locked);
        $periodCardThemes = [
            [
                'card' => 'border-sky-200 bg-sky-50/70 shadow-sky-100/70 dark:border-gray-800 dark:bg-gray-950/60 dark:shadow-none',
                'stripe' => 'bg-sky-400',
                'icon' => 'bg-sky-500',
            ],
            [
                'card' => 'border-emerald-200 bg-emerald-50/70 shadow-emerald-100/70 dark:border-gray-800 dark:bg-gray-950/60 dark:shadow-none',
                'stripe' => 'bg-emerald-400',
                'icon' => 'bg-emerald-500',
            ],
            [
                'card' => 'border-amber-200 bg-amber-50/70 shadow-amber-100/70 dark:border-gray-800 dark:bg-gray-950/60 dark:shadow-none',
                'stripe' => 'bg-amber-400',
                'icon' => 'bg-amber-500',
            ],
            [
                'card' => 'border-violet-200 bg-violet-50/70 shadow-violet-100/70 dark:border-gray-800 dark:bg-gray-950/60 dark:shadow-none',
                'stripe' => 'bg-violet-400',
                'icon' => 'bg-violet-500',
            ],
        ];

        $eventRouteParameters = [];

        if ($hasSelectedCalendar) {
            $eventRouteParameters = [
                'academic_year_id' => $selectedCalendar->academic_year_id,
                'academic_calendar_id' => $selectedCalendar->id,
            ];

            if ($selectedCalendar->scope_type === \App\Enums\Academic\CalendarScopeType::Cycle && $selectedCalendar->singleCoveredCycleId()) {
                $eventRouteParameters['target_type'] = \App\Enums\Academic\CalendarTargetType::Cycle->value;
                $eventRouteParameters['target_id'] = $selectedCalendar->singleCoveredCycleId();
                $eventRouteParameters['target_cycle_id'] = $selectedCalendar->singleCoveredCycleId();
            } elseif ($selectedCalendar->scope_type === \App\Enums\Academic\CalendarScopeType::Level && $selectedCalendar->level_id) {
                $eventRouteParameters['target_type'] = \App\Enums\Academic\CalendarTargetType::Level->value;
                $eventRouteParameters['target_id'] = $selectedCalendar->level_id;
                $eventRouteParameters['target_cycle_id'] = $selectedCalendar->level?->cycle_id;
            } elseif ($selectedCalendar->scope_type === \App\Enums\Academic\CalendarScopeType::ClassRoom && $selectedCalendar->class_id) {
                $eventRouteParameters['target_type'] = \App\Enums\Academic\CalendarTargetType::ClassRoom->value;
                $eventRouteParameters['target_id'] = $selectedCalendar->class_id;
                $eventRouteParameters['target_cycle_id'] = $selectedCalendar->classroom?->level?->cycle_id;
            }
        }
    @endphp

    <x-slot name="header">
        <section class="calendar-periods-header px-4 py-4 sm:px-5">
            <div class="relative flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0 space-y-3">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] !text-slate-600 dark:!text-sky-100/85">
                        <span>{{ __('academic.common.calendar') }}</span>
                        <span class="!text-slate-300 dark:!text-sky-100/55">/</span>
                        <span>{{ __('academic.calendars.periods.title') }}</span>
                    </div>

                    <div>
                        <h1 class="text-2xl font-bold tracking-tight !text-slate-900 dark:!text-white">
                            {{ __('academic.calendars.periods.title') }}
                        </h1>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="calendar-periods-chip calendar-periods-chip--year">
                            <i class="fa-solid fa-calendar-days text-[10px]"></i>
                            {{ $hasSelectedAcademicYear ? $selectedAcademicYear->name : __('core.academic_years.show.not_generated') }}
                        </span>

                        <span class="calendar-periods-chip calendar-periods-chip--school">
                            <i class="fa-solid fa-school text-[10px]"></i>
                            {{ $selectedSchoolName }}
                        </span>

                        <span class="calendar-periods-chip calendar-periods-chip--calendar">
                            <i class="fa-solid fa-calendar-week text-[10px]"></i>
                            {{ $hasSelectedCalendar ? $selectedCalendar->name : __('core.academic_years.show.not_generated') }}
                        </span>
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap items-center justify-start gap-2 rounded-xl border border-white/70 bg-white/80 p-2 shadow-sm backdrop-blur-sm dark:border-sky-200/30 dark:bg-slate-950/45 dark:shadow-none lg:justify-end">
                    <x-ui.badge :tone="$calendarStatusTone">
                        <i class="fa-solid {{ $calendarValidated ? 'fa-circle-check' : 'fa-hourglass-half' }} text-[10px]"></i>
                        {{ $calendarStatusLabel }}
                    </x-ui.badge>

                    <x-ui.button :href="route('academic.calendars.index', array_filter(['group' => $group]))" variant="secondary" size="sm" class="!border-slate-300 !bg-white !text-slate-700 hover:!border-sky-300 hover:!text-sky-700 dark:!border-blue-700 dark:!bg-blue-500/20 dark:!text-blue-100">
                        <i class="fa-solid fa-arrow-left text-[11px]"></i>
                        {{ __('core.common.actions.back') }}
                    </x-ui.button>

                    @if ($yearHubRoute)
                        <x-ui.button :href="$yearHubRoute" variant="primary" size="sm" class="whitespace-nowrap">
                            <i class="fa-solid fa-school text-[11px]"></i>
                            {{ __('academic.calendars.periods.year_overview_action') }}
                        </x-ui.button>
                    @endif
                </div>
            </div>
        </section>
    </x-slot>

    <div class="space-y-6 pb-24" x-data="{ workflowPanelOpen: false }" @keydown.escape.window="workflowPanelOpen = false">
        @if ($group)
            <x-ui.alert tone="info">
                {{ __('academic.calendars.periods.group_context', ['group' => __('academic.shared.cycle_groups.'.$group)]) }}
            </x-ui.alert>
        @endif

        @if ($hasSelectedCalendar)
            <form id="period-generation-form" method="POST" action="{{ route('academic.calendars.periods.generate') }}">
                @csrf
                <input type="hidden" name="academic_year_id" value="{{ $selectedAcademicYear->id }}">
                <input type="hidden" name="academic_calendar_id" value="{{ $selectedCalendar->id }}">
                @if ($group)
                    <input type="hidden" name="group" value="{{ $group }}">
                @endif
                @if ($canChooseSchool)
                    <input type="hidden" name="school_id" value="{{ $schoolId }}">
                @endif
            </form>

            <x-ui.card class="space-y-5">
                <div class="flex flex-col gap-3 border-b border-gray-100 pb-4 dark:border-gray-800 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.calendars.periods.periods_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.calendars.periods.periods_description') }}</p>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <x-ui.badge tone="sky">{{ $periods->count() }} {{ __('academic.configuration.index.periods_count_suffix') }}</x-ui.badge>
                        <x-ui.badge :tone="$calendarStatusTone">{{ $calendarStatusLabel }}</x-ui.badge>
                        <x-ui.badge :tone="$workflowCompleted ? 'emerald' : 'sky'">{{ $workflowStatusLabel }}</x-ui.badge>

                        <x-ui.button type="button" variant="soft-sky" size="sm" @click="workflowPanelOpen = true">
                            <i class="fa-solid fa-circle-question text-xs"></i>
                            {{ __('academic.calendars.periods.workflow_panel_action') }}
                        </x-ui.button>
                    </div>
                </div>

                @if (! $configuration)
                    <x-ui.alert tone="warning">{{ __('academic.calendars.periods.configuration_missing') }}</x-ui.alert>
                @elseif ($calendarValidated && $hasUnlockedPeriods)
                    <x-ui.alert tone="info">{{ __('academic.calendars.periods.validation_editability_hint') }}</x-ui.alert>
                @endif

                @if ($hasPeriods)
                    <form id="periods-update-form" method="POST" action="{{ route('academic.calendars.periods.update', $selectedAcademicYear) }}" class="space-y-5">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="academic_year_id" value="{{ $selectedAcademicYear->id }}">
                        <input type="hidden" name="academic_calendar_id" value="{{ $selectedCalendar->id }}">
                        @if ($group)
                            <input type="hidden" name="group" value="{{ $group }}">
                        @endif
                        @if ($canChooseSchool)
                            <input type="hidden" name="school_id" value="{{ $schoolId }}">
                        @endif

                        <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-3">
                            @foreach ($periods as $index => $period)
                                @php
                                    $periodCardTheme = $periodCardThemes[$index % count($periodCardThemes)];
                                @endphp

                                <article class="relative overflow-hidden rounded-2xl border p-5 pt-6 shadow-sm transition-colors duration-300 {{ $periodCardTheme['card'] }}">
                                    <span class="absolute inset-x-0 top-0 h-1 {{ $periodCardTheme['stripe'] }}"></span>

                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">
                                                {{ __('academic.calendars.periods.period_label', ['number' => $period->position]) }}
                                            </p>
                                            <div class="mt-3 flex items-center gap-3">
                                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl text-white shadow-sm shadow-slate-900/10 dark:bg-sky-500 {{ $periodCardTheme['icon'] }}">
                                                    <i class="fa-solid fa-layer-group text-sm"></i>
                                                </span>
                                                <div class="min-w-0">
                                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $period->name }}</h3>
                                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                        {{ $period->start_date?->translatedFormat('d M Y') ?: '—' }} - {{ $period->end_date?->translatedFormat('d M Y') ?: '—' }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap justify-end gap-2">
                                            <x-ui.badge :tone="$period->is_active ? 'emerald' : 'slate'">
                                                {{ $period->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                                            </x-ui.badge>
                                            <x-ui.badge :tone="$period->is_locked ? 'amber' : 'sky'">
                                                {{ $period->is_locked ? __('academic.common.locked') : __('academic.calendars.periods.period_editable_label') }}
                                            </x-ui.badge>
                                        </div>
                                    </div>

                                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                                        <x-form.input
                                            :name="'periods['.$index.'][start_date]'"
                                            type="date"
                                            :label="__('academic.periods.form.start_date')"
                                            :value="optional($period->start_date)->format('Y-m-d')"
                                            :old-key="'periods.'.$index.'.start_date'"
                                            :error-key="'periods.'.$index.'.start_date'"
                                            icon="fa-calendar-day"
                                            required
                                        />

                                        <x-form.input
                                            :name="'periods['.$index.'][end_date]'"
                                            type="date"
                                            :label="__('academic.periods.form.end_date')"
                                            :value="optional($period->end_date)->format('Y-m-d')"
                                            :old-key="'periods.'.$index.'.end_date'"
                                            :error-key="'periods.'.$index.'.end_date'"
                                            icon="fa-calendar-check"
                                            required
                                        />
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <div class="sticky bottom-3 z-20 rounded-2xl border border-gray-200 bg-white p-4 shadow-lg shadow-slate-900/10 backdrop-blur-sm dark:border-slate-700 dark:bg-slate-900">
                            <div class="flex flex-wrap justify-end gap-2">
                                @if ($canSaveAndValidate)
                                    <x-ui.button type="submit" size="sm">
                                        {{ __('academic.calendars.periods.save_and_validate_action') }}
                                    </x-ui.button>
                                @endif

                                @if ($canCreateEvents)
                                    <x-ui.button :href="route('academic.events.create', array_filter($eventRouteParameters))" variant="soft-emerald" size="sm">
                                        <i class="fa-solid fa-bullhorn text-xs"></i>
                                        {{ __('academic.events.index.create_action') }}
                                    </x-ui.button>
                                @else
                                    <x-ui.button type="button" variant="soft-emerald" size="sm" disabled class="cursor-not-allowed !opacity-100 !border-emerald-300 !bg-emerald-100 !text-emerald-700 dark:!border-emerald-700 dark:!bg-emerald-500/20 dark:!text-emerald-100">
                                        <i class="fa-solid fa-bullhorn text-xs"></i>
                                        {{ __('academic.events.index.create_action') }}
                                    </x-ui.button>
                                @endif
                            </div>

                            @unless ($canCreateEvents)
                                <p class="mt-3 text-xs text-slate-600 dark:text-slate-300">
                                    {{ __('academic.calendars.periods.events_unlock_hint') }}
                                </p>
                            @endunless
                        </div>
                    </form>
                @else
                    <x-ui.empty-state :title="__('academic.calendars.periods.empty_title')" :description="__('academic.calendars.periods.empty_description')" icon="fa-calendar-week">
                        <x-slot:actions>
                            @if ($canGeneratePeriods)
                                <x-ui.button type="submit" form="period-generation-form">
                                    <i class="fa-solid fa-wand-magic-sparkles text-xs"></i>
                                    {{ __('academic.calendars.periods.generate_action') }}
                                </x-ui.button>
                            @endif

                            <x-ui.button type="button" variant="soft-emerald" size="sm" disabled class="cursor-not-allowed !opacity-100 !border-emerald-300 !bg-emerald-100 !text-emerald-700 dark:!border-emerald-700 dark:!bg-emerald-500/20 dark:!text-emerald-100">
                                <i class="fa-solid fa-bullhorn text-xs"></i>
                                {{ __('academic.events.index.create_action') }}
                            </x-ui.button>
                        </x-slot:actions>
                    </x-ui.empty-state>

                    <p class="text-xs text-slate-600 dark:text-slate-300">{{ __('academic.calendars.periods.events_unlock_hint') }}</p>
                @endif
            </x-ui.card>

            <div x-cloak x-show="workflowPanelOpen" x-transition.opacity.duration.150ms class="fixed inset-0 z-[60] flex justify-end">
                <button type="button" class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm" @click="workflowPanelOpen = false" aria-label="{{ __('core.common.actions.close') }}"></button>

                <aside
                    x-show="workflowPanelOpen"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="translate-x-full"
                    class="relative h-full w-full max-w-md overflow-y-auto border-l border-slate-200 bg-white p-5 shadow-2xl shadow-slate-950/20 dark:border-slate-800 dark:bg-slate-950"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="workflow-panel-title"
                >
                    <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-4 dark:border-slate-800">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">{{ __('academic.calendars.periods.workflow_panel_eyebrow') }}</p>
                            <h2 id="workflow-panel-title" class="mt-2 text-lg font-semibold text-slate-950 dark:text-white">{{ __('academic.calendars.periods.workflow_title') }}</h2>
                        </div>

                        <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-sky-200 hover:text-sky-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-sky-800 dark:hover:text-sky-300" @click="workflowPanelOpen = false">
                            <span class="sr-only">{{ __('core.common.actions.close') }}</span>
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>

                    <div class="mt-5 space-y-3">
                        @foreach ($workflowSteps as $step)
                            <article class="rounded-2xl border p-4 transition-colors duration-200 {{ $step['done'] ? 'border-emerald-200 bg-emerald-50/70 dark:border-emerald-800/60 dark:bg-emerald-500/10' : 'border-gray-100 bg-gray-50/80 dark:border-gray-800 dark:bg-gray-900/70' }}">
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $step['done'] ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300' }}">
                                        <i class="fa-solid {{ $step['icon'] }} text-sm"></i>
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $step['title'] }}</h3>
                                            <x-ui.badge :tone="$step['done'] ? 'emerald' : 'slate'">
                                                {{ $step['done'] ? __('core.academic_years.show.step_complete_label') : __('core.academic_years.show.step_pending_label') }}
                                            </x-ui.badge>
                                        </div>
                                        <p class="mt-2 text-xs leading-relaxed text-gray-500 dark:text-gray-400">{{ $step['description'] }}</p>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </aside>
            </div>
        @else
            <x-ui.empty-state :title="__('academic.calendars.periods.select_calendar_title')" :description="__('academic.calendars.periods.select_calendar_description')" icon="fa-calendar-plus">
                <x-slot:actions>
                    @if ($yearHubRoute)
                        <x-ui.button :href="$yearHubRoute" variant="secondary">
                            {{ __('academic.calendars.periods.year_overview_action') }}
                        </x-ui.button>
                    @endif
                </x-slot:actions>
            </x-ui.empty-state>
        @endif
    </div>
</x-app-layout>
