<x-app-layout>
    @php
        $hasConfiguration = $configuration !== null;
        $hasCalendar = $calendar !== null;
        $hasPeriods = $periods->isNotEmpty();
        $calendarValidated = $hasCalendar && $calendar->validated_at !== null;
        $configurationScopeValue = $configuration?->scope_type?->value ?? ($configuration?->scope_type ? (string) $configuration->scope_type : null);
        $isGlobalCalendarWorkflow = $configurationScopeValue === null
            || $configurationScopeValue === \App\Enums\Academic\CalendarScopeType::Global->value;
        $globalPeriodsRoute = route('academic.calendars.periods.index', array_filter([
            'academic_year_id' => $academicYear->id,
            'academic_calendar_id' => $calendar?->id,
            'school_id' => $currentSchool->id,
        ]));
        $globalEventsCreateRoute = route('academic.events.create', array_filter([
            'academic_year_id' => $academicYear->id,
            'academic_calendar_id' => $calendar?->id,
            'target_type' => \App\Enums\Academic\CalendarTargetType::Global->value,
        ]));
        $globalCalendarShowRoute = $hasCalendar ? route('academic.calendars.show', $calendar) : null;

        $calendarStatusLabel = $hasCalendar
            ? ($calendarValidated ? __('academic.calendars.show.validated') : __('academic.calendars.show.pending_validation'))
            : __('core.academic_years.show.calendar_not_generated_label');

        $yearProgressValue = '—';
        $yearProgressCaption = __('core.academic_years.show.not_generated');

        if ($academicYear->start_date && $academicYear->end_date) {
            $yearStart = $academicYear->start_date->copy()->startOfDay();
            $yearEnd = $academicYear->end_date->copy()->endOfDay();
            $today = now();

            if ($today->lt($yearStart)) {
                $yearProgressValue = '0%';
                $yearProgressCaption = __('core.academic_years.show.progress_not_started', [
                    'date' => $yearStart->translatedFormat('d M Y'),
                ]);
            } elseif ($today->gt($yearEnd)) {
                $yearProgressValue = '100%';
                $yearProgressCaption = __('core.academic_years.show.progress_completed', [
                    'date' => $yearEnd->translatedFormat('d M Y'),
                ]);
            } else {
                $totalDays = max($yearStart->diffInDays($yearEnd), 1);
                $elapsedDays = $yearStart->diffInDays($today);
                $remainingDays = (int) max($today->diffInDays($yearEnd), 0);

                $yearProgressValue = (int) round(($elapsedDays / $totalDays) * 100).'%';
                $yearProgressCaption = trans_choice(
                    'core.academic_years.show.progress_remaining',
                    $remainingDays,
                    ['count' => $remainingDays]
                );
            }
        }

        $calendarReadinessSteps = ($hasConfiguration ? 1 : 0) + ($hasPeriods ? 1 : 0) + ($calendarValidated ? 1 : 0);

        $overviewKpiCards = [
            [
                'label' => __('core.academic_years.form.start_date'),
                'value' => $academicYear->start_date?->translatedFormat('d M Y') ?: '—',
                'icon' => 'fa-calendar-day',
                'tone' => 'slate',
                'width' => 'w-[12.5rem]',
            ],
            [
                'label' => __('core.academic_years.form.end_date'),
                'value' => $academicYear->end_date?->translatedFormat('d M Y') ?: '—',
                'icon' => 'fa-calendar-check',
                'tone' => 'slate',
                'width' => 'w-[12.5rem]',
            ],
            [
                'label' => __('core.settings.organization.current_school_label'),
                'value' => $currentSchool->name,
                'icon' => 'fa-school',
                'tone' => 'slate',
                'width' => 'w-[14rem]',
            ],
            [
                'label' => __('core.academic_years.form.enrollment_label'),
                'value' => $academicYear->enrollment_open
                    ? __('core.academic_years.show.enrollment_open_label')
                    : __('core.academic_years.show.enrollment_closed_label'),
                'icon' => 'fa-door-open',
                'tone' => $academicYear->enrollment_open ? 'emerald' : 'amber',
                'width' => 'w-[14rem]',
            ],
            [
                'label' => __('core.settings.organization.mode_label'),
                'value' => $academicYear->multi_school_enabled
                    ? __('core.settings.organization.modes.multi')
                    : __('core.settings.organization.modes.mono'),
                'icon' => 'fa-building',
                'tone' => $academicYear->multi_school_enabled ? 'sky' : 'slate',
                'width' => 'w-[14rem]',
            ],
        ];

        $journeySteps = [
            [
                'order' => '01',
                'title' => __('core.academic_years.show.step_year_title'),
                'description' => __('core.academic_years.show.step_year_description'),
                'status' => __('core.academic_years.show.step_complete_label'),
                'tone' => 'emerald',
                'icon' => 'fa-calendar-days',
                'action_label' => __('core.common.actions.edit'),
                'action_href' => route('core.academic-years.edit', $academicYear),
                'action_variant' => 'secondary',
                'completed' => true,
                'action_type' => 'link',
            ],
            [
                'order' => '02',
                'title' => __('core.academic_years.show.step_configuration_title'),
                'description' => __('core.academic_years.show.step_configuration_description'),
                'status' => $hasConfiguration ? __('core.academic_years.show.step_complete_label') : __('core.academic_years.show.step_current_label'),
                'tone' => $hasConfiguration ? 'emerald' : 'amber',
                'icon' => 'fa-sliders',
                'action_label' => $hasConfiguration
                    ? __('core.academic_years.show.configuration_action')
                    : __('core.academic_years.show.configuration_create_action'),
                'action_href' => $hasConfiguration
                    ? route('academic.configuration.edit', $configuration)
                    : route('academic.configuration.create', ['academic_year_id' => $academicYear->id]),
                'action_variant' => $hasConfiguration ? 'secondary' : 'primary',
                'completed' => $hasConfiguration,
                'action_type' => 'link',
            ],
            [
                'order' => '03',
                'title' => __('core.academic_years.show.step_periods_title'),
                'description' => __('core.academic_years.show.step_periods_description'),
                'status' => $hasPeriods
                    ? __('core.academic_years.show.step_complete_label')
                    : ($hasConfiguration ? __('core.academic_years.show.step_current_label') : __('core.academic_years.show.step_pending_label')),
                'tone' => $hasPeriods ? 'emerald' : ($hasConfiguration ? 'amber' : 'slate'),
                'icon' => 'fa-layer-group',
                'action_label' => __('core.academic_years.show.periods_action'),
                'action_href' => route('academic.calendars.periods.index', array_filter(['academic_year_id' => $academicYear->id, 'academic_calendar_id' => $calendar?->id, 'school_id' => $currentSchool->id])),
                'action_variant' => 'secondary',
                'completed' => $hasPeriods,
                'action_type' => 'link',
            ],
            [
                'order' => '04',
                'title' => __('core.academic_years.show.step_validation_title'),
                'description' => __('core.academic_years.show.step_validation_description'),
                'status' => $calendarValidated
                    ? __('core.academic_years.show.step_complete_label')
                    : ($hasCalendar ? __('core.academic_years.show.step_current_label') : __('core.academic_years.show.step_pending_label')),
                'tone' => $calendarValidated ? 'emerald' : ($hasCalendar ? 'amber' : 'slate'),
                'icon' => 'fa-circle-check',
                'action_label' => $calendarValidated
                    ? __('core.academic_years.show.calendar_action')
                    : ($hasCalendar ? __('academic.calendars.show.validate_action') : null),
                'action_href' => $hasCalendar ? route('academic.calendars.show', $calendar) : null,
                'validate_action' => $hasCalendar && ! $calendarValidated ? route('academic.calendars.validate', $calendar) : null,
                'action_variant' => $calendarValidated ? 'secondary' : 'warning',
                'completed' => $calendarValidated,
                'action_type' => $calendarValidated || ! $hasCalendar ? 'link' : 'form',
            ],
        ];

        $journeyCompletedCount = collect($journeySteps)->where('completed', true)->count();
        $journeyTotalCount = count($journeySteps);
        $journeyRemainingCount = max($journeyTotalCount - $journeyCompletedCount, 0);
        $journeyProgressPercent = (int) round(($journeyCompletedCount / max($journeyTotalCount, 1)) * 100);
        $journeyStatusTone = match (true) {
            $journeyCompletedCount === $journeyTotalCount => 'emerald',
            $journeyCompletedCount > 0 => 'amber',
            default => 'slate',
        };
        $journeyStatusLabel = trans_choice('core.academic_years.show.journey_progress', $journeyCompletedCount, [
            'completed' => $journeyCompletedCount,
            'total' => $journeyTotalCount,
        ]);
        $journeyNextStep = collect($journeySteps)->first(fn (array $step) => ! $step['completed']);
        $journeyNextStepLabel = $journeyNextStep['title'] ?? __('core.academic_years.show.journey_complete_label');
        $journeyNextActionLabel = $journeyNextStep['action_label'] ?? null;
        $journeyNextActionHref = $journeyNextStep['action_href'] ?? null;
        $journeyNextActionType = $journeyNextStep['action_type'] ?? 'link';
        $journeyNextActionVariant = $journeyNextStep['action_variant'] ?? 'primary';
        $journeyNextValidateAction = $journeyNextStep['validate_action'] ?? null;
        $journeyToneClasses = [
            'emerald' => [
                'surface' => 'academic-year-focus-card academic-year-focus-card--emerald',
                'icon' => 'academic-year-tone-icon--emerald',
            ],
            'amber' => [
                'surface' => 'academic-year-focus-card academic-year-focus-card--amber',
                'icon' => 'academic-year-tone-icon--amber',
            ],
            'slate' => [
                'surface' => 'academic-year-focus-card academic-year-focus-card--slate',
                'icon' => 'academic-year-tone-icon--slate',
            ],
        ];

        $journeyFocusTone = $journeyToneClasses[$journeyStatusTone] ?? $journeyToneClasses['slate'];
        $journeyButtonVariant = match ($journeyStatusTone) {
            'emerald' => 'soft-emerald',
            'amber' => 'soft-amber',
            default => 'soft-slate',
        };

        $cycleGroupToneMap = [
            'preschool' => [
                'accent' => 'academic-year-cycle-card academic-year-cycle-card--amber',
                'icon' => 'academic-year-tone-icon--amber',
                'button_variant' => 'soft-amber',
                'stats' => [
                    'academic-year-cycle-stat academic-year-cycle-stat--slate',
                    'academic-year-cycle-stat academic-year-cycle-stat--slate',
                    'academic-year-cycle-stat academic-year-cycle-stat--amber',
                ],
            ],
            'primary' => [
                'accent' => 'academic-year-cycle-card academic-year-cycle-card--emerald',
                'icon' => 'academic-year-tone-icon--emerald',
                'button_variant' => 'soft-emerald',
                'stats' => [
                    'academic-year-cycle-stat academic-year-cycle-stat--slate',
                    'academic-year-cycle-stat academic-year-cycle-stat--emerald',
                    'academic-year-cycle-stat academic-year-cycle-stat--slate',
                ],
            ],
            'secondary' => [
                'accent' => 'academic-year-cycle-card academic-year-cycle-card--violet',
                'icon' => 'academic-year-tone-icon--violet',
                'button_variant' => 'soft-violet',
                'stats' => [
                    'academic-year-cycle-stat academic-year-cycle-stat--slate',
                    'academic-year-cycle-stat academic-year-cycle-stat--slate',
                    'academic-year-cycle-stat academic-year-cycle-stat--violet',
                ],
            ],
        ];
    @endphp
    <x-slot name="header">
        <section class="rounded-[2rem] border border-sky-300/80 bg-gradient-to-r from-sky-100 via-blue-100 to-cyan-100 px-4 py-3 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_30px_rgba(14,165,233,0.10)] transition-colors duration-300 dark:border-sky-900/40 dark:bg-gradient-to-r dark:from-sky-950 dark:via-blue-950/70 dark:to-cyan-950/70 dark:shadow-none sm:px-5">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-500 text-white shadow-md shadow-blue-500/20">
                        <i class="fa-solid fa-calendar-days text-sm"></i>
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-lg font-bold tracking-tight text-gray-900 dark:text-white sm:text-xl">
                                {{ $academicYear->name }}
                            </h2>

                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">
                                    {{ __('core.academic_years.show.year_status_label') }}
                                </span>
                                <x-ui.badge :tone="$academicYear->is_active ? 'emerald' : 'slate'">
                                    {{ $academicYear->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                                </x-ui.badge>
                            </div>
                        </div>

                        <p class="mt-1 max-w-3xl text-xs leading-relaxed text-gray-600 dark:text-gray-300 sm:text-sm">
                            {{ __('core.academic_years.show.hub_subtitle') }}
                        </p>

                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-3 lg:justify-end">
                    @can('update', $academicYear)
                        <x-ui.button
                            :href="route('core.academic-years.edit', $academicYear)"
                            variant="primary"
                            class="academic-year-action-button !rounded-full !bg-blue-600 !shadow-lg !shadow-blue-500/20 hover:!bg-blue-500"
                        >
                            {{ __('core.common.actions.edit') }}
                        </x-ui.button>
                    @endcan

                    <x-ui.button
                        :href="route('core.academic-years.index')"
                        variant="secondary"
                        class="academic-year-action-button !rounded-full !border-slate-200 !bg-white !text-slate-700 hover:!border-blue-200 hover:!text-blue-600"
                    >
                        {{ __('core.common.actions.back') }}
                    </x-ui.button>

                    @can('delete', $academicYear)
                        <x-ui.confirm-modal
                            :name="'delete-academic-year-'.$academicYear->id"
                            :action="route('core.academic-years.destroy', $academicYear)"
                            :description="__('core.academic_years.index.delete_confirm')"
                            :warning="$academicYear->is_active ? __('core.academic_years.delete_active_warning') : null"
                            :trigger-label="__('core.common.actions.delete')"
                        />
                    @endcan
                </div>
            </div>
        </section>
    </x-slot>

    <div class="space-y-5">
        <section>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">{{ __('core.academic_years.show.overview_title') }}</h1>
        </section>

        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-5">
            @foreach ($overviewKpiCards as $card)
                <x-ui.stats-card
                    class="academic-year-summary-card academic-year-overview-card"
                    size="compact"
                    :label="$card['label']"
                    :value="$card['value']"
                    :icon="$card['icon']"
                    :tone="$card['tone']"
                />
            @endforeach

            @if ($academicYear->notes)
                <x-ui.card variant="metric" padding="p-3.5 sm:p-4" class="academic-year-summary-card md:col-span-2 xl:col-span-3 2xl:col-span-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">{{ __('core.academic_years.show.context_note_label') }}</p>
                            <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-gray-600 dark:text-gray-300">{{ $academicYear->notes }}</p>
                        </div>

                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            <i class="fa-solid fa-note-sticky text-xs"></i>
                        </span>
                    </div>
                </x-ui.card>
            @endif
        </section>

        <section class="page-card page-card--dense academic-year-journey-shell space-y-4">
            <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_312px]">
                <div class="space-y-3.5">
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-[1.52rem] font-semibold leading-tight text-gray-900 dark:text-white sm:text-[1.68rem]">{{ __('core.academic_years.show.journey_title') }}</h2>
                        <x-ui.badge :tone="$journeyStatusTone">{{ $journeyStatusLabel }}</x-ui.badge>
                    </div>
                    <div class="academic-year-journey-meter space-y-3" x-data="{ progressWidth: @js($journeyProgressPercent) + '%' }">
                        <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">
                            <span>{{ __('core.academic_years.show.journey_title') }}</span>
                            <span>{{ $journeyProgressPercent }}%</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-slate-800/90">
                            <div class="h-full rounded-full bg-sky-500" :style="{ width: progressWidth }"></div>
                        </div>
                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-300">
                            <span class="font-semibold text-gray-800 dark:text-white">{{ $journeyCompletedCount }}/{{ $journeyTotalCount }}</span>
                            <span>{{ trans_choice('core.academic_years.show.journey_remaining_label', $journeyRemainingCount, ['count' => $journeyRemainingCount]) }}</span>
                        </div>
                    </div>

                    <div class="grid gap-2.5 sm:grid-cols-2">
                        <x-ui.stats-card
                            class="academic-year-summary-card"
                            size="compact"
                            :label="__('core.academic_years.show.progress_title')"
                            :value="$yearProgressValue"
                            :caption="$yearProgressCaption"
                            icon="fa-chart-line"
                            tone="sky"
                        />

                        <x-ui.stats-card
                            class="academic-year-summary-card"
                            size="compact"
                            :label="__('core.academic_years.show.calendar_status_label')"
                            :value="$calendarStatusLabel"
                            :caption="$hasCalendar
                                ? __('core.academic_years.show.calendar_readiness_caption', ['count' => $calendarReadinessSteps])
                                : __('core.academic_years.show.calendar_pending_caption')"
                            icon="fa-list-check"
                            :tone="$calendarValidated ? 'emerald' : ($hasCalendar ? 'amber' : 'slate')"
                        />
                    </div>
                </div>

                <div class="rounded-[1.45rem] border p-3.5 shadow-[0_1px_2px_rgba(15,23,42,0.03),0_10px_24px_rgba(15,23,42,0.04)] transition-colors duration-300 sm:p-4 {{ $journeyFocusTone['surface'] }}">
                    <div class="flex h-full flex-col justify-between gap-3.5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">
                                    {{ __('core.academic_years.show.next_step_label') }}
                                </p>
                                <h3 class="mt-1.5 text-[1.38rem] font-semibold leading-tight text-gray-900 dark:text-white sm:text-[1.52rem]">
                                    {{ $journeyNextStepLabel }}
                                </h3>
                            </div>

                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $journeyFocusTone['icon'] }}">
                                <i class="fa-solid fa-route text-sm"></i>
                            </span>
                        </div>

                        @if ($journeyNextActionLabel)
                            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200/80 pt-2.5 dark:border-slate-800/80">
                                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                                    {{ $journeyCompletedCount }}/{{ $journeyTotalCount }}
                                </span>

                                @if ($journeyNextActionType === 'form' && $journeyNextValidateAction)
                                    <form method="POST" action="{{ $journeyNextValidateAction }}">
                                        @csrf
                                        <x-ui.button type="submit" :variant="$journeyNextActionVariant === 'secondary' ? $journeyButtonVariant : $journeyNextActionVariant">
                                            {{ $journeyNextActionLabel }}
                                        </x-ui.button>
                                    </form>
                                @elseif ($journeyNextActionHref)
                                    <x-ui.button :href="$journeyNextActionHref" :variant="$journeyNextActionVariant === 'secondary' ? $journeyButtonVariant : $journeyNextActionVariant">
                                        {{ $journeyNextActionLabel }}
                                    </x-ui.button>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        @if ($isGlobalCalendarWorkflow)
            <section class="page-card page-card--dense space-y-4">
                <div class="space-y-1">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.academic_years.show.global_calendar_actions_title') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('core.academic_years.show.global_calendar_actions_description') }}</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <x-ui.button :href="$globalPeriodsRoute" variant="soft-amber" class="academic-year-action-button">
                        {{ __('core.academic_years.show.periods_action') }}
                    </x-ui.button>

                    <x-ui.button :href="$globalEventsCreateRoute" variant="soft-emerald" class="academic-year-action-button">
                        {{ __('core.academic_years.show.sector_actions_create_event_action') }}
                    </x-ui.button>

                    @if ($globalCalendarShowRoute)
                        <x-ui.button :href="$globalCalendarShowRoute" variant="secondary" class="academic-year-action-button">
                            {{ __('core.academic_years.show.calendar_action') }}
                        </x-ui.button>
                    @endif
                </div>
            </section>
        @endif

        <section class="space-y-3">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.academic_years.show.cycle_management_title') }}</h2>
                </div>
            </div>
            
            <div class="grid gap-3 xl:grid-cols-3">
                @foreach ($cycleGroups as $cycleGroup)
                    @php
                        $groupTone = $cycleGroupToneMap[$cycleGroup['key']];
                    @endphp

                    <article class="relative overflow-hidden rounded-[1.15rem] border p-3.5 shadow-[0_1px_2px_rgba(15,23,42,0.03),0_6px_18px_rgba(15,23,42,0.02)] transition-shadow duration-300 hover:shadow-[0_2px_6px_rgba(15,23,42,0.04),0_12px_24px_rgba(15,23,42,0.05)] dark:shadow-none {{ $groupTone['accent'] }}">
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r {{ $cycleGroup['tone'] === 'amber' ? 'from-amber-300 to-orange-200' : ($cycleGroup['tone'] === 'emerald' ? 'from-emerald-300 to-cyan-200' : 'from-violet-300 to-indigo-200') }}"></div>

                        <div class="flex h-full flex-col gap-3">
                            <div class="flex items-start justify-between gap-3">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $groupTone['icon'] }}">
                                    <i class="fa-solid {{ $cycleGroup['icon'] }} text-sm"></i>
                                </span>

                                <x-ui.badge :tone="$cycleGroup['status_tone']">{{ $cycleGroup['status_label'] }}</x-ui.badge>
                            </div>

                            <div class="min-w-0">
                                <h3 class="text-[1.05rem] font-semibold tracking-tight text-gray-900 dark:text-white">{{ $cycleGroup['label'] }}</h3>
                            </div>

                            <div class="grid grid-cols-3 gap-1.5">
                                <div class="{{ $groupTone['stats'][0] }}">
                                    <p class="academic-year-cycle-stat__label">
                                        {{ __('core.academic_years.show.cycle_management_cycles_label') }}
                                    </p>
                                    <p class="academic-year-cycle-stat__value">{{ $cycleGroup['cycles_count'] }}</p>
                                </div>
                                <div class="{{ $groupTone['stats'][1] }}">
                                    <p class="academic-year-cycle-stat__label">
                                        {{ __('core.academic_years.show.cycle_management_levels_label') }}
                                    </p>
                                    <p class="academic-year-cycle-stat__value">{{ $cycleGroup['levels_count'] }}</p>
                                </div>
                                <div class="{{ $groupTone['stats'][2] }}">
                                    <p class="academic-year-cycle-stat__label">
                                        {{ __('core.academic_years.show.cycle_management_calendars_label') }}
                                    </p>
                                    <p class="academic-year-cycle-stat__value">{{ $cycleGroup['calendars_count'] }}</p>
                                </div>
                            </div>

                            <div class="mt-auto flex items-center justify-end pt-1">
                                <x-ui.button :href="$cycleGroup['sector_route']" :variant="$groupTone['button_variant']" size="sm" class="academic-year-action-button">
                                    {{ __('core.academic_years.show.cycle_management_open_page_action') }}
                                </x-ui.button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

    </div>
</x-app-layout>
