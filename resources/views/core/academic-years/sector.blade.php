<x-app-layout>
    @php
    $actionToneClasses = [
        'sky' => 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-200',
        'amber' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-200',
        'emerald' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200',
        'violet' => 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-200',
    ];

    $heroMetricChipClasses = [
        'cycles' => 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-400/45 dark:bg-violet-500/24 dark:text-violet-50',
        'levels' => 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-400/45 dark:bg-sky-500/24 dark:text-sky-50',
        'classes' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-400/45 dark:bg-emerald-500/24 dark:text-emerald-50',
        'calendars' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-400/45 dark:bg-amber-500/24 dark:text-amber-50',
    ];

    $heroStatusChipClasses = [
        'slate' => 'border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-400/40 dark:bg-slate-500/24 dark:text-slate-50',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-400/45 dark:bg-amber-500/24 dark:text-amber-50',
        'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-400/45 dark:bg-emerald-500/24 dark:text-emerald-50',
        'violet' => 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-400/45 dark:bg-violet-500/24 dark:text-violet-50',
    ];

    $cycleMetricChipClasses = [
        'levels' => 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-400/35 dark:bg-sky-500/18 dark:text-sky-50',
        'classes' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-400/35 dark:bg-emerald-500/18 dark:text-emerald-50',
    ];

    $sectorThemes = [
        'preschool' => [
            'heroGradient' => 'from-white via-amber-50/60 to-slate-50 dark:from-slate-900 dark:via-slate-900 dark:to-slate-950',
            'heroOrb' => 'bg-amber-200/20',
            'heroBorder' => 'border-amber-200/60 dark:border-amber-900/30',
            'heroIcon' => 'bg-amber-100 text-amber-700 border border-amber-200 shadow-sm shadow-amber-500/10 dark:bg-amber-500/10 dark:text-amber-200 dark:border-amber-500/20',
            'button_variant' => 'soft-amber',
            'heroBadge' => 'border-slate-200 bg-white/70 text-slate-600 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-200',
            'heroChip' => 'border-slate-200 bg-white/60 text-slate-600 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-300',
            'panelSurface' => 'bg-white/90 dark:bg-slate-900/70',
            'panelBorder' => 'border-slate-200/80 dark:border-slate-700/70',
            'panelHover' => 'hover:border-amber-200 hover:bg-amber-50/50 dark:hover:border-amber-500/20 dark:hover:bg-slate-900/85',
            'panelBar' => 'from-amber-300 via-amber-200 to-orange-200',
            'listAccent' => 'border-l-amber-200/70',
            'listIcon' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-200',
            'surfaceGlow' => 'bg-amber-100/20',
        ],
        'primary' => [
            'heroGradient' => 'from-white via-emerald-50/60 to-slate-50 dark:from-slate-900 dark:via-slate-900 dark:to-slate-950',
            'heroOrb' => 'bg-emerald-200/20',
            'heroBorder' => 'border-emerald-200/60 dark:border-emerald-900/30',
            'heroIcon' => 'bg-emerald-100 text-emerald-700 border border-emerald-200 shadow-sm shadow-emerald-500/10 dark:bg-emerald-500/10 dark:text-emerald-200 dark:border-emerald-500/20',
            'button_variant' => 'soft-emerald',
            'heroBadge' => 'border-slate-200 bg-white/70 text-slate-600 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-200',
            'heroChip' => 'border-slate-200 bg-white/60 text-slate-600 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-300',
            'panelSurface' => 'bg-white/90 dark:bg-slate-900/70',
            'panelBorder' => 'border-slate-200/80 dark:border-slate-700/70',
            'panelHover' => 'hover:border-emerald-200 hover:bg-emerald-50/50 dark:hover:border-emerald-500/20 dark:hover:bg-slate-900/85',
            'panelBar' => 'from-emerald-300 via-cyan-200 to-sky-200',
            'listAccent' => 'border-l-emerald-200/70',
            'listIcon' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200',
            'surfaceGlow' => 'bg-emerald-100/20',
        ],
        'secondary' => [
            'heroGradient' => 'from-white via-violet-50/60 to-slate-50 dark:from-slate-900 dark:via-slate-900 dark:to-slate-950',
            'heroOrb' => 'bg-violet-200/20',
            'heroBorder' => 'border-violet-200/60 dark:border-violet-900/30',
            'heroIcon' => 'bg-violet-100 text-violet-700 border border-violet-200 shadow-sm shadow-violet-500/10 dark:bg-violet-500/10 dark:text-violet-200 dark:border-violet-500/20',
            'button_variant' => 'soft-violet',
            'heroBadge' => 'border-slate-200 bg-white/70 text-slate-600 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-200',
            'heroChip' => 'border-slate-200 bg-white/60 text-slate-600 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-300',
            'panelSurface' => 'bg-white/90 dark:bg-slate-900/70',
            'panelBorder' => 'border-slate-200/80 dark:border-slate-700/70',
            'panelHover' => 'hover:border-violet-200 hover:bg-violet-50/50 dark:hover:border-violet-500/20 dark:hover:bg-slate-900/85',
            'panelBar' => 'from-violet-300 via-indigo-200 to-sky-200',
            'listAccent' => 'border-l-violet-200/70',
            'listIcon' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-200',
            'surfaceGlow' => 'bg-violet-100/20',
        ],
    ];

        $sectorTheme = $sectorThemes[$cycleGroup['key']] ?? $sectorThemes['secondary'];

        $singleCycleMode = $cycleGroup['is_single_cycle'] ?? false;
        $currentCycle = $cycleGroup['current_cycle'] ?? null;
        $primaryAction = $cycleGroup['primary_action'] ?? null;
        $shortcutActions = collect($cycleGroup['quick_actions'] ?? []);
        $heroEyebrow = $singleCycleMode
            ? __('core.academic_years.show.single_cycle_title')
            : __('core.academic_years.show.cycle_management_title');
        $heroMeta = $academicYear->name;
        $cycleSectionTitle = $singleCycleMode
            ? __('core.academic_years.show.cycle_management_cycle_label')
            : __('core.academic_years.show.cycle_management_cycles_label');
        $summaryTones = ['slate', 'slate', 'sky', 'slate'];
        $summaryCards = [
            [
                'label' => __('core.academic_years.show.cycle_management_levels_label'),
                'value' => $cycleGroup['levels_count'],
                'caption' => null,
                'icon' => 'fa-layer-group',
                'tone' => $summaryTones[0],
            ],
            [
                'label' => __('core.academic_years.show.cycle_management_classes_label'),
                'value' => $cycleGroup['classrooms_count'],
                'caption' => null,
                'icon' => 'fa-chalkboard-user',
                'tone' => $summaryTones[1],
            ],
            [
                'label' => __('core.academic_years.show.cycle_management_calendars_label'),
                'value' => $cycleGroup['calendars_count'],
                'caption' => null,
                'icon' => 'fa-calendar-week',
                'tone' => $summaryTones[2],
            ],
            [
                'label' => __('core.academic_years.show.calendar_status_label'),
                'value' => $cycleGroup['status_label'],
                'caption' => $cycleGroup['calendars_count'] > 0
                    ? __('core.academic_years.show.cycle_management_validation_progress', [
                        'validated' => $cycleGroup['validated_count'],
                        'total' => $cycleGroup['calendars_count'],
                    ])
                    : __('core.academic_years.show.not_generated'),
                'icon' => 'fa-circle-check',
                'tone' => $cycleGroup['status_tone'],
            ],
        ];

        if (! $singleCycleMode) {
            array_unshift($summaryCards, [
                'label' => __('core.academic_years.show.cycle_management_cycles_label'),
                'value' => $cycleGroup['cycles_count'],
                'caption' => __('academic.shared.cycle_groups.'.$cycleGroup['key']),
                'icon' => 'fa-diagram-project',
                'tone' => 'slate',
            ]);
        }

        if ($cycleGroup['needs_calendar'] ?? false) {
            $shortcutActions = $shortcutActions->reject(fn (array $action): bool => ($action['key'] ?? null) === 'create_calendar');
        }
    @endphp

    <x-slot name="header">
        <section class="relative overflow-hidden rounded-[1.75rem] border {{ $sectorTheme['heroBorder'] }} bg-white/95 bg-gradient-to-br {{ $sectorTheme['heroGradient'] }} px-4 py-3.5 shadow-[0_18px_50px_rgba(15,23,42,0.10)] transition-colors duration-300 dark:bg-slate-900/80 sm:px-5 sm:py-4">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -right-20 -top-20 h-56 w-56 rounded-full {{ $sectorTheme['heroOrb'] }} blur-3xl"></div>
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.14),transparent_34%)] dark:bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.04),transparent_34%)]"></div>
            </div>

            <div class="relative space-y-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0 space-y-2.5">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $sectorTheme['heroIcon'] }}">
                                <i class="fa-solid {{ $cycleGroup['icon'] }} text-lg"></i>
                            </span>

                            <div class="min-w-0">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.34em] text-slate-500 dark:text-slate-400">{{ $heroEyebrow }}</p>
                                <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-4xl">{{ $cycleGroup['label'] }}</h1>
                            </div>
                        </div>

                        <p class="max-w-2xl text-sm font-medium text-slate-600 dark:text-slate-300">
                            {{ $heroMeta }}
                        </p>

                        <div class="flex flex-wrap gap-2">
                            @if (! $singleCycleMode)
                                <span class="inline-flex items-center gap-2 rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $heroMetricChipClasses['cycles'] }}">
                                    {{ $cycleGroup['cycles_count'] }} {{ __('core.academic_years.show.cycle_management_cycles_label') }}
                                </span>
                            @endif
                            <span class="inline-flex items-center gap-2 rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $heroMetricChipClasses['levels'] }}">
                                {{ $cycleGroup['levels_count'] }} {{ __('core.academic_years.show.cycle_management_levels_label') }}
                            </span>
                            <span class="inline-flex items-center gap-2 rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $heroMetricChipClasses['classes'] }}">
                                {{ $cycleGroup['classrooms_count'] }} {{ __('core.academic_years.show.cycle_management_classes_label') }}
                            </span>
                            <span class="inline-flex items-center gap-2 rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $heroMetricChipClasses['calendars'] }}">
                                {{ $cycleGroup['calendars_count'] }} {{ __('core.academic_years.show.cycle_management_calendars_label') }}
                            </span>
                            <span class="inline-flex items-center gap-2 rounded-full border px-2.5 py-1 text-[11px] font-semibold {{ $heroStatusChipClasses[$cycleGroup['status_tone']] ?? $heroStatusChipClasses['slate'] }}">
                                {{ $cycleGroup['status_label'] }}
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 lg:justify-end">
                        <x-ui.button :href="$cycleGroup['back_route']" variant="primary" size="sm" class="academic-year-action-button">
                            {{ __('core.common.actions.back') }}
                        </x-ui.button>

                        @if (! ($cycleGroup['needs_calendar'] ?? false))
                            @can('viewAny', \App\Models\AcademicCycle::class)
                                <x-ui.button :href="$cycleGroup['manage_cycles_route']" :variant="$sectorTheme['button_variant']" size="sm" class="academic-year-action-button">
                                    {{ $cycleGroup['manage_cycle_action_label'] }}
                                </x-ui.button>
                            @endcan
                        @endif
                    </div>
                </div>

                @if (($cycleGroup['needs_calendar'] ?? false) && $primaryAction)
                    <div class="rounded-[1.35rem] border border-slate-200 bg-white/90 p-3.5 shadow-sm backdrop-blur-sm sm:p-4 dark:border-slate-700/70 dark:bg-slate-900/70">
                        <div class="flex flex-col gap-3.5 xl:flex-row xl:items-end xl:justify-between">
                            <div class="min-w-0 xl:max-w-3xl">
                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.24em] text-slate-500 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-300">
                                    {{ $primaryAction['badge'] }}
                                </span>

                                <p class="mt-2.5 text-base font-semibold tracking-tight text-slate-900 dark:text-white sm:text-lg">
                                    {{ $primaryAction['title'] }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-3 xl:justify-end">
                                @can('create', \App\Models\AcademicCalendar::class)
                                    <x-ui.button :href="$primaryAction['href']" size="md" class="academic-year-action-button">
                                        {{ $primaryAction['action'] }}
                                    </x-ui.button>
                                @endcan

                                @can('viewAny', \App\Models\AcademicCycle::class)
                                    <x-ui.button
                                        :href="$cycleGroup['manage_cycles_route']"
                                        variant="soft-slate"
                                        size="md"
                                        class="academic-year-action-button"
                                    >
                                        {{ $cycleGroup['manage_cycle_action_label'] }}
                                    </x-ui.button>
                                @endcan
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </x-slot>

    <div class="space-y-5">
        <section class="space-y-2.5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.academic_years.show.cycle_actions_title') }}</h2>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-2 {{ ($cycleGroup['needs_calendar'] ?? false) ? 'xl:grid-cols-3' : 'xl:grid-cols-4' }}">
            @foreach ($shortcutActions as $action)
                @can($action['ability'], $action['model'])
                    @php
                        $actionAvailable = $action['is_available'] ?? true;
                    @endphp
                    <x-ui.card class="flex h-full flex-col justify-between gap-4 overflow-hidden p-4 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_8px_24px_rgba(15,23,42,0.03)] transition-all duration-300 dark:shadow-none {{ $actionAvailable ? 'border '.$sectorTheme['panelBorder'].' '.$sectorTheme['panelSurface'].' hover:-translate-y-0.5 '.$sectorTheme['panelHover'] : 'border border-dashed border-slate-200/80 bg-slate-50/80 dark:border-slate-700/70 dark:bg-slate-900/55' }}">
                        <span class="h-1 w-16 rounded-full bg-gradient-to-r {{ $actionAvailable ? $sectorTheme['panelBar'] : 'from-slate-300 via-slate-200 to-slate-100 dark:from-slate-600 dark:via-slate-700 dark:to-slate-800' }}"></span>

                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $actionToneClasses[$action['tone']] ?? $actionToneClasses['sky'] }}">
                                <i class="fa-solid {{ $action['icon'] }} text-sm"></i>
                            </span>

                            <div class="min-w-0">
                                <h2 class="text-base font-semibold tracking-tight text-gray-900 dark:text-white">{{ $action['label'] }}</h2>

                                @if (! $actionAvailable && ! empty($action['hint']))
                                    <p class="mt-3 text-sm font-medium text-amber-700 dark:text-amber-200">
                                        {{ $action['hint'] }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @if ($actionAvailable)
                                <x-ui.button :href="$action['href']" :variant="$sectorTheme['button_variant']" size="sm" class="academic-year-action-button w-full sm:w-auto">
                                    {{ $action['action'] }}
                                </x-ui.button>
                            @endif
                        </div>
                    </x-ui.card>
                @endcan
            @endforeach
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 {{ $singleCycleMode ? 'xl:grid-cols-4' : 'xl:grid-cols-5' }}">
            @foreach ($summaryCards as $card)
                <x-ui.stats-card
                    class="academic-year-summary-card"
                    size="compact"
                    :label="$card['label']"
                    :value="$card['value']"
                    :caption="$card['caption'] ?? null"
                    :icon="$card['icon']"
                    :tone="$card['tone']"
                />
            @endforeach
        </section>

        <section class="grid gap-4 xl:grid-cols-[1fr_1.15fr]">
            <x-ui.card class="space-y-3.5">
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3 dark:border-gray-800">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $cycleSectionTitle }}</h2>
                    </div>

                    @if ($singleCycleMode && $currentCycle)
                        <x-ui.badge :tone="$currentCycle->is_active ? 'emerald' : 'slate'">
                            {{ $currentCycle->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                        </x-ui.badge>
                    @else
                        <x-ui.badge tone="sky">{{ $cycleGroup['cycles_count'] }}</x-ui.badge>
                    @endif
                </div>

                @if ($cycleGroup['cycles']->isNotEmpty())
                    <div class="space-y-2.5">
                        @foreach ($cycleGroup['cycles'] as $cycle)
                            @php
                                $cycleCalendar = $cycleGroup['calendars']->firstWhere('cycle_id', $cycle->id);
                            @endphp
                            <article
                                class="relative flex items-start justify-between gap-3 overflow-hidden rounded-[1.25rem] border border-gray-200/70 bg-white/85 p-3.5 transition-colors duration-300 hover:bg-white dark:border-gray-800 dark:bg-gray-950/40 dark:hover:bg-gray-900/80 {{ $sectorTheme['panelHover'] }} {{ $sectorTheme['listAccent'] }}"
                            >
                                <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b {{ $sectorTheme['panelBar'] }}"></div>

                                <div class="min-w-0 pl-1">
                                    <div class="flex items-start gap-3">
                                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $sectorTheme['listIcon'] }}">
                                            <i class="fa-solid fa-diagram-project text-xs"></i>
                                        </span>

                                        <div class="min-w-0">
                                            <a href="{{ route('academic.cycles.show', $cycle) }}" class="text-base font-semibold text-gray-900 transition-colors hover:text-sky-600 dark:text-white dark:hover:text-sky-300">
                                                {{ $cycle->name }}
                                            </a>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $cycle->code }}</p>
                                            @if ($cycle->description)
                                                <p class="mt-2 text-sm leading-relaxed text-gray-500 dark:text-gray-400">{{ $cycle->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="flex shrink-0 flex-col gap-3 sm:min-w-[15.5rem] sm:items-end">
                                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                                        <x-ui.badge :tone="$cycle->is_active ? 'emerald' : 'slate'">
                                            {{ $cycle->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                                        </x-ui.badge>
                                        @unless ($cycleCalendar)
                                            <x-ui.badge tone="amber">
                                                {{ __('core.academic_years.show.not_generated') }}
                                            </x-ui.badge>
                                        @endunless
                                    </div>

                                    <div class="flex flex-wrap gap-2 sm:justify-end">
                                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $cycleMetricChipClasses['levels'] }}">
                                            {{ $cycle->levels_count }} {{ __('core.academic_years.show.cycle_management_levels_label') }}
                                        </span>
                                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $cycleMetricChipClasses['classes'] }}">
                                            {{ $cycle->classrooms_count ?? 0 }} {{ __('core.academic_years.show.cycle_management_classes_label') }}
                                        </span>
                                    </div>

                                    @if ($cycleCalendar)
                                        <div class="flex flex-wrap gap-2 sm:justify-end">
                                            <x-ui.button :href="route('core.academic-years.cycles.show', ['academic_year' => $academicYear, 'cycle' => $cycle])" variant="soft-violet" size="sm" class="academic-year-action-button">
                                                {{ __('core.academic_years.show.cycle_management_open_page_action') }}
                                            </x-ui.button>
                                            <x-ui.button :href="route('academic.calendars.show', $cycleCalendar)" variant="soft-slate" size="sm" class="academic-year-action-button">
                                                {{ __('core.academic_years.show.calendar_view_action') }}
                                            </x-ui.button>
                                        </div>
                                    @else
                                        <div class="flex w-full flex-wrap gap-2 sm:justify-end">
                                            <x-ui.button :href="route('core.academic-years.cycles.show', ['academic_year' => $academicYear, 'cycle' => $cycle])" variant="soft-violet" size="sm" class="academic-year-action-button">
                                                {{ __('core.academic_years.show.cycle_management_open_page_action') }}
                                            </x-ui.button>
                                            <x-ui.button
                                                :href="route('academic.calendars.create', [
                                                    'academic_year_id' => $academicYear->id,
                                                    'group' => $cycleGroup['key'],
                                                    'scope_type' => \App\Enums\Academic\CalendarScopeType::Cycle->value,
                                                    'cycle_id' => $cycle->id,
                                                    'from_periods' => 1,
                                                ])"
                                                size="sm"
                                                class="academic-year-action-button"
                                            >
                                                {{ __('core.academic_years.show.sector_actions_create_calendar_action') }}
                                            </x-ui.button>
                                        </div>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state
                        :title="__('academic.cycles.index.empty.title')"
                        :description="__('academic.cycles.index.empty.description')"
                        icon="fa-diagram-project"
                    />
                @endif
            </x-ui.card>

            <x-ui.card class="space-y-3.5">
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-3 dark:border-gray-800">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.academic_years.show.cycle_management_calendars_label') }}</h2>
                    </div>

                    <x-ui.badge tone="{{ $cycleGroup['status_tone'] }}">{{ $cycleGroup['status_label'] }}</x-ui.badge>
                </div>

                @if ($cycleGroup['calendars']->isNotEmpty())
                    <div class="space-y-2.5">
                        @foreach ($cycleGroup['calendars'] as $calendarItem)
                            <article
                                class="relative flex items-start justify-between gap-3 overflow-hidden rounded-[1.25rem] border border-gray-200/70 bg-white/85 p-3.5 transition-colors duration-300 hover:bg-white dark:border-gray-800 dark:bg-gray-950/40 dark:hover:bg-gray-900/80 {{ $sectorTheme['panelHover'] }} {{ $sectorTheme['listAccent'] }}"
                            >
                                <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b {{ $sectorTheme['panelBar'] }}"></div>

                                <div class="min-w-0 pl-1">
                                    <div class="flex items-start gap-3">
                                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $sectorTheme['listIcon'] }}">
                                            <i class="fa-solid fa-calendar-week text-xs"></i>
                                        </span>

                                        <div class="min-w-0">
                                            <a href="{{ route('academic.calendars.show', $calendarItem) }}" class="text-base font-semibold text-gray-900 transition-colors hover:text-sky-600 dark:text-white dark:hover:text-sky-300">
                                                {{ $calendarItem->name }}
                                            </a>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                {{ $calendarItem->periods_count }} {{ __('academic.configuration.index.periods_count_suffix') }}
                                                ·
                                                {{ trans_choice('core.academic_years.show.events_published', $calendarItem->published_events_count, ['count' => $calendarItem->published_events_count]) }}
                                            </p>
                                            @if ($calendarItem->cycle)
                                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $calendarItem->cycle->name }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="flex shrink-0 flex-col items-end gap-2">
                                    <x-ui.badge :tone="$calendarItem->validated_at ? 'emerald' : 'amber'">
                                        {{ $calendarItem->validated_at ? __('core.academic_years.show.validated') : __('core.academic_years.show.pending_validation') }}
                                    </x-ui.badge>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $calendarItem->scopeReferenceLabel() ?: $cycleGroup['label'] }}
                                    </span>
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <x-ui.button :href="route('academic.calendars.show', $calendarItem)" variant="soft-slate" size="sm" class="academic-year-action-button">
                                            {{ __('core.academic_years.show.calendar_view_action') }}
                                        </x-ui.button>
                                        @if ($calendarItem->validated_at === null && $calendarItem->is_active)
                                            <form method="POST" action="{{ route('academic.calendars.validate', $calendarItem) }}">
                                                @csrf
                                                @method('PATCH')
                                                <x-ui.button type="submit" variant="soft-amber" size="sm" class="academic-year-action-button">
                                                    {{ __('academic.calendars.show.validate_action') }}
                                                </x-ui.button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <x-ui.alert tone="info" :title="__('core.academic_years.show.calendar_empty_title')">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p>{{ __('core.academic_years.show.cycle_management_empty') }}</p>

                            @can('create', \App\Models\AcademicCalendar::class)
                                <x-ui.button :href="$cycleGroup['create_calendar_route']" size="md" class="academic-year-action-button">
                                    {{ __('core.academic_years.show.sector_actions_create_calendar_action') }}
                                </x-ui.button>
                            @endcan
                        </div>
                    </x-ui.alert>
                @endif
            </x-ui.card>
        </section>
    </div>
</x-app-layout>
