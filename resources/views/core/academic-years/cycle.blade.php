<x-app-layout>
    @php
        $supportsSeries = $cycle->supportsSeries();
        $activeLevelLookup = array_flip($activeLevelIds);
        $activeSeriesLookup = array_flip($activeSeriesIds);
        $levelsUsingSeriesCount = $levels->where('uses_series', true)->count();
        $seriesRelevant = $supportsSeries && $levelsUsingSeriesCount > 0;
        $activeClassroomsCount = $classrooms->where('is_active', true)->count();
        $canStartClasses = $levels->contains(function ($level) use ($activeLevelLookup, $stats) {
            return isset($activeLevelLookup[$level->id]) && (! $level->uses_series || $stats['active_series'] > 0);
        });
        $classroomBlockedDescription = $stats['active_levels'] === 0
            ? __('core.academic_years.show.classroom_creation_blocked_levels_description')
            : __('core.academic_years.show.classroom_creation_blocked_series_description');
        $cycleGroup = $cycle->category?->managementGroup();
        $cycleCalendarValidated = $cycleCalendar?->validated_at !== null;
        $cycleCalendarCreateRoute = route('academic.configuration.create', array_filter([
            'academic_year_id' => $academicYear->id,
            'scope_type' => \App\Enums\Academic\CalendarScopeType::Cycle->value,
            'cycle_id' => $cycle->id,
        ]));
        $cycleCalendarPeriodsRoute = $cycleCalendar
            ? route('academic.calendars.periods.index', array_filter([
                'academic_year_id' => $academicYear->id,
                'academic_calendar_id' => $cycleCalendar->id,
                'school_id' => $currentSchool->id,
                'group' => $cycleGroup,
            ]))
            : null;
        $cycleCalendarEventRoute = $cycleCalendar
            ? route('academic.events.create', array_filter([
                'academic_year_id' => $academicYear->id,
                'group' => $cycleGroup,
                'academic_calendar_id' => $cycleCalendar->id,
                'target_type' => \App\Enums\Academic\CalendarTargetType::Cycle->value,
                'target_id' => $cycle->id,
                'target_cycle_id' => $cycle->id,
            ]))
            : null;
    @endphp

    <x-slot name="header">
        <x-ui.page-header
            :title="$cycle->name"
            :description="__('core.academic_years.show.cycle_configuration_description', ['year' => $academicYear->name])"
        >
            <x-slot:actions>
                <x-ui.button :href="route('core.academic-years.show', $academicYear)" variant="secondary" class="academic-year-action-button">
                    {{ __('core.academic_years.show.back_to_year_action') }}
                </x-ui.button>
                @if ($canViewCycleReference)
                    <x-ui.button :href="route('academic.cycles.show', $cycle)" variant="soft-sky" class="academic-year-action-button">
                        {{ __('core.academic_years.show.cycle_configuration_reference_action') }}
                    </x-ui.button>
                @endif
                @if ($cycleCalendar && $canManageCalendars)
                    <x-ui.button :href="$cycleCalendarPeriodsRoute" variant="soft-amber" class="academic-year-action-button">
                        {{ __('core.academic_years.show.sector_actions_manage_periods_action') }}
                    </x-ui.button>
                @endif
                @if (! $cycleCalendar && $canCreateCycleCalendar)
                    <x-ui.button :href="$cycleCalendarCreateRoute" variant="soft-emerald" class="academic-year-action-button">
                        {{ __('core.academic_years.show.sector_actions_create_calendar_action') }}
                    </x-ui.button>
                @endif
                @if ($cycleCalendar && $canCreateEvents)
                    <x-ui.button :href="$cycleCalendarEventRoute" variant="soft-emerald" class="academic-year-action-button">
                        {{ __('core.academic_years.show.sector_actions_create_event_action') }}
                    </x-ui.button>
                @endif
                @if ($canCreateClassroom && $canStartClasses)
                    <x-ui.button :href="route('academic.classes.create', ['academic_year_id' => $academicYear->id, 'cycle_id' => $cycle->id, 'return_to_year_cycle' => 1])" class="academic-year-action-button">
                        {{ __('academic.classes.index.create_action') }}
                    </x-ui.button>
                @endif
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="overflow-hidden rounded-[2rem] border border-slate-200/80 bg-gradient-to-br from-white via-slate-50 to-white shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950">
            <div class="p-6 xl:p-7">
                <div class="space-y-5">
                    <div class="space-y-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.26em] text-sky-700 dark:text-sky-300">
                            {{ __('core.academic_years.show.cycle_configuration_eyebrow') }}
                        </p>

                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white md:text-[2rem]">
                                {{ __('core.academic_years.show.cycle_configuration_overview_title') }}
                            </h2>

                            <x-ui.badge tone="slate">{{ $cycle->name }}</x-ui.badge>
                            <x-ui.badge :tone="$supportsSeries ? 'violet' : 'sky'">
                                {{ $supportsSeries ? __('core.academic_years.show.supports_series_label') : __('core.academic_years.show.without_series_label') }}
                            </x-ui.badge>
                        </div>

                        <p class="max-w-3xl text-sm leading-7 text-gray-600 dark:text-gray-300">
                            {{ __('core.academic_years.show.workflow_description') }}
                        </p>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-3xl border border-slate-200/80 bg-white p-4 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900/90">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">
                                {{ __('core.academic_years.show.year_status_label') }}
                            </p>
                            <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">{{ $academicYear->name }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $currentSchool->name }}</p>
                        </div>

                        <div class="rounded-3xl border border-slate-200/80 bg-white p-4 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900/90">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">
                                {{ __('core.academic_years.show.active_levels_label') }}
                            </p>
                            <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">{{ $stats['active_levels'] }}/{{ $stats['available_levels'] }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('core.academic_years.show.available_levels_label') }}</p>
                        </div>

                        <div class="rounded-3xl border border-slate-200/80 bg-white p-4 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900/90">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">
                                {{ __('core.academic_years.show.active_series_label') }}
                            </p>
                            <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $seriesRelevant ? $stats['active_series'].'/'.$stats['available_series'] : __('core.academic_years.show.without_series_label') }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $seriesRelevant ? __('academic.common.series') : __('core.academic_years.show.series_not_applicable_title') }}
                            </p>
                        </div>

                        <div class="rounded-3xl border border-slate-200/80 bg-white p-4 shadow-sm shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900/90">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">
                                {{ __('core.academic_years.show.classrooms_count_label') }}
                            </p>
                            @if ($stats['classrooms'] > 0)
                                <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">{{ $stats['classrooms'] }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $activeClassroomsCount }} {{ __('core.common.states.active') }}
                                </p>
                            @else
                                <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ __('core.academic_years.show.classrooms_count_empty_label') }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('core.academic_years.show.classrooms_count_empty_description') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <x-ui.card class="space-y-5 overflow-hidden">
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-4 dark:border-gray-800">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.academic_years.show.cycle_calendar_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('core.academic_years.show.cycle_calendar_description') }}</p>
                    </div>

                    @if ($cycleCalendar)
                        <x-ui.badge :tone="$cycleCalendarValidated ? 'emerald' : 'amber'">
                            {{ $cycleCalendarValidated ? __('core.academic_years.show.validated') : __('core.academic_years.show.pending_validation') }}
                        </x-ui.badge>
                    @else
                        <x-ui.badge tone="slate">{{ __('core.academic_years.show.not_generated') }}</x-ui.badge>
                    @endif
                </div>

                @if ($cycleCalendar)
                    <div class="grid gap-3 md:grid-cols-3">
                        <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">{{ __('core.academic_years.show.calendar_status_label') }}</p>
                            <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $cycleCalendar->name }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $cycleCalendarValidated ? __('academic.calendars.show.validated_on', ['date' => $cycleCalendar->validated_at?->translatedFormat('d M Y')]) : __('academic.calendars.show.validation_hint') }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">{{ __('core.academic_years.show.cycle_calendar_periods_label') }}</p>
                            <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $cycleCalendar->periods_count }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('academic.configuration.index.periods_count_suffix') }}</p>
                        </div>

                        <div class="rounded-2xl border border-slate-200/80 bg-slate-50/80 p-4 dark:border-slate-800 dark:bg-slate-900/60">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">{{ __('core.academic_years.show.cycle_calendar_events_label') }}</p>
                            <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $cycleCalendar->published_events_count }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ trans_choice('core.academic_years.show.events_published', $cycleCalendar->published_events_count, ['count' => $cycleCalendar->published_events_count]) }}
                            </p>
                        </div>
                    </div>
                @else
                    <x-ui.alert tone="info" :title="__('core.academic_years.show.cycle_calendar_missing_title')">
                        {{ __('core.academic_years.show.cycle_calendar_missing_description') }}
                    </x-ui.alert>
                @endif
            </x-ui.card>

            <x-ui.card class="space-y-4 overflow-hidden">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.academic_years.show.cycle_actions_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('core.academic_years.show.cycle_calendar_actions_description') }}</p>
                </div>

                <div class="space-y-3">
                    @if ($cycleCalendar)
                        @if ($canManageCalendars)
                            <x-ui.button :href="route('academic.calendars.show', $cycleCalendar)" variant="soft-slate" class="academic-year-action-button w-full justify-center">
                                {{ __('core.academic_years.show.calendar_view_action') }}
                            </x-ui.button>

                            <x-ui.button :href="$cycleCalendarPeriodsRoute" variant="soft-amber" class="academic-year-action-button w-full justify-center">
                                {{ __('core.academic_years.show.sector_actions_manage_periods_action') }}
                            </x-ui.button>
                        @else
                            <x-ui.button type="button" variant="soft-slate" disabled class="academic-year-action-button w-full justify-center opacity-60 pointer-events-none">
                                {{ __('core.academic_years.show.calendar_view_action') }}
                            </x-ui.button>

                            <x-ui.button type="button" variant="soft-amber" disabled class="academic-year-action-button w-full justify-center opacity-60 pointer-events-none">
                                {{ __('core.academic_years.show.sector_actions_manage_periods_action') }}
                            </x-ui.button>
                        @endif

                        @if ($canCreateEvents)
                            <x-ui.button :href="$cycleCalendarEventRoute" variant="soft-emerald" class="academic-year-action-button w-full justify-center">
                                {{ __('core.academic_years.show.sector_actions_create_event_action') }}
                            </x-ui.button>
                        @else
                            <x-ui.button type="button" variant="soft-emerald" disabled class="academic-year-action-button w-full justify-center opacity-60 pointer-events-none">
                                {{ __('core.academic_years.show.sector_actions_create_event_action') }}
                            </x-ui.button>
                        @endif
                    @else
                        @if ($canCreateCycleCalendar)
                            <x-ui.button :href="$cycleCalendarCreateRoute" class="academic-year-action-button w-full justify-center">
                                {{ __('core.academic_years.show.sector_actions_create_calendar_action') }}
                            </x-ui.button>
                        @else
                            <x-ui.button type="button" disabled class="academic-year-action-button w-full justify-center opacity-60 pointer-events-none">
                                {{ __('core.academic_years.show.sector_actions_create_calendar_action') }}
                            </x-ui.button>
                        @endif

                        <x-ui.button type="button" variant="soft-amber" disabled class="academic-year-action-button w-full justify-center opacity-60 pointer-events-none">
                            {{ __('core.academic_years.show.sector_actions_manage_periods_action') }}
                        </x-ui.button>

                        <x-ui.button type="button" variant="soft-emerald" disabled class="academic-year-action-button w-full justify-center opacity-60 pointer-events-none">
                            {{ __('core.academic_years.show.sector_actions_create_event_action') }}
                        </x-ui.button>

                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('core.academic_years.show.sector_actions_calendar_required_hint') }}
                        </p>
                    @endif

                    @if ($cycleCalendar && (! $canManageCalendars || ! $canCreateEvents))
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('core.academic_years.show.sector_actions_permissions_hint') }}
                        </p>
                    @endif
                </div>
            </x-ui.card>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]">
            <x-ui.card class="space-y-5 overflow-hidden">
                <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-4 dark:border-gray-800">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-sky-100 text-sm font-semibold text-sky-700 dark:bg-sky-900/40 dark:text-sky-200">1</span>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.academic_years.show.annual_levels_title') }}</h2>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('core.academic_years.show.annual_levels_description') }}</p>
                    </div>

                    <x-ui.badge tone="sky">{{ $stats['active_levels'] }}/{{ $stats['available_levels'] }}</x-ui.badge>
                </div>

                @if ($levels->isNotEmpty())
                    <x-ui.table>
                        <thead>
                            <tr>
                                <th class="table-head-cell">{{ __('academic.levels.index.table.level') }}</th>
                                <th class="table-head-cell">{{ __('core.academic_years.show.levels_table_reference_label') }}</th>
                                <th class="table-head-cell">{{ __('core.academic_years.show.levels_table_year_label') }}</th>
                                <th class="table-head-cell">{{ __('core.academic_years.show.levels_table_usage_label') }}</th>
                                <th class="table-head-cell text-right">{{ __('core.academic_years.show.levels_table_actions_label') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($levels as $level)
                                @php
                                    $isActiveForYear = isset($activeLevelLookup[$level->id]);
                                @endphp
                                <tr class="table-body-row">
                                    <td class="table-body-cell">
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $level->name }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $level->short_name ?: $level->code }}</p>
                                    </td>
                                    <td class="table-body-cell">
                                        <div class="flex flex-wrap gap-2">
                                            <x-ui.badge :tone="$level->is_active ? 'emerald' : 'slate'">
                                                {{ $level->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                                            </x-ui.badge>
                                            @if ($seriesRelevant)
                                                <x-ui.badge :tone="$level->uses_series ? 'violet' : 'slate'">
                                                    {{ $level->uses_series ? __('core.academic_years.show.series_optional_label') : __('core.academic_years.show.simple_level_label') }}
                                                </x-ui.badge>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="table-body-cell">
                                        <x-ui.badge :tone="$isActiveForYear ? 'sky' : 'slate'">
                                            {{ $isActiveForYear ? __('core.academic_years.show.active_for_year_label') : __('core.academic_years.show.inactive_for_year_label') }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="table-body-cell">
                                        <div class="flex flex-wrap gap-2">
                                            @if ($level->year_classrooms_count > 0)
                                                <x-ui.badge tone="sky">{{ $level->year_classrooms_count }} {{ __('core.academic_years.show.cycle_management_classes_label') }}</x-ui.badge>
                                            @endif
                                            <x-ui.badge tone="amber">{{ $level->year_coefficients_count }} {{ __('ui.nav.academic_coefficients') }}</x-ui.badge>
                                        </div>
                                    </td>
                                    <td class="table-body-cell">
                                        @if ($canToggleLevels)
                                            <div class="flex justify-end">
                                                <form method="POST" action="{{ route('core.academic-years.cycles.levels.toggle-active', ['academic_year' => $academicYear, 'cycle' => $cycle, 'level' => $level]) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <x-ui.button type="submit" :variant="$isActiveForYear ? 'soft-amber' : 'soft-sky'" size="sm" class="academic-year-action-button">
                                                        {{ $isActiveForYear ? __('core.academic_years.show.remove_from_year_short_action') : __('core.academic_years.show.add_to_year_short_action') }}
                                                    </x-ui.button>
                                                </form>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
                @else
                    <x-ui.empty-state
                        :title="__('core.academic_years.show.annual_levels_empty_title')"
                        :description="__('core.academic_years.show.annual_levels_empty_description')"
                        icon="fa-layer-group"
                    />
                @endif
            </x-ui.card>

            @if ($seriesRelevant)
                <x-ui.card class="space-y-5 overflow-hidden">
                    <div class="flex items-start justify-between gap-4 border-b border-gray-100 pb-4 dark:border-gray-800">
                        <div>
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-violet-100 text-sm font-semibold text-violet-700 dark:bg-violet-900/40 dark:text-violet-200">2</span>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.academic_years.show.annual_series_title') }}</h2>
                            </div>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('core.academic_years.show.annual_series_description') }}</p>
                        </div>

                        <x-ui.badge tone="sky">{{ $stats['active_series'] }}/{{ $stats['available_series'] }}</x-ui.badge>
                    </div>

                    @if ($series->isNotEmpty())
                        <x-ui.table>
                            <thead>
                                <tr>
                                    <th class="table-head-cell">{{ __('academic.series.index.table.series') }}</th>
                                    <th class="table-head-cell">{{ __('core.academic_years.show.series_table_reference_label') }}</th>
                                    <th class="table-head-cell">{{ __('core.academic_years.show.series_table_year_label') }}</th>
                                    <th class="table-head-cell">{{ __('core.academic_years.show.series_table_usage_label') }}</th>
                                    <th class="table-head-cell text-right">{{ __('core.academic_years.show.series_table_actions_label') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach ($series as $serie)
                                    @php
                                        $isActiveForYear = isset($activeSeriesLookup[$serie->id]);
                                    @endphp
                                    <tr class="table-body-row">
                                        <td class="table-body-cell">
                                            <p class="font-semibold text-gray-900 dark:text-white">{{ $serie->name }}</p>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $serie->short_name ?: $serie->code }}</p>
                                        </td>
                                        <td class="table-body-cell">
                                            <x-ui.badge :tone="$serie->is_active ? 'emerald' : 'slate'">
                                                {{ $serie->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                                            </x-ui.badge>
                                        </td>
                                        <td class="table-body-cell">
                                            <x-ui.badge :tone="$isActiveForYear ? 'violet' : 'slate'">
                                                {{ $isActiveForYear ? __('core.academic_years.show.active_for_year_label') : __('core.academic_years.show.inactive_for_year_label') }}
                                            </x-ui.badge>
                                        </td>
                                        <td class="table-body-cell">
                                            <div class="flex flex-wrap gap-2">
                                                <x-ui.badge tone="sky">{{ $serie->levels_count }} {{ __('core.academic_years.show.cycle_management_levels_label') }}</x-ui.badge>
                                                @if ($serie->year_classrooms_count > 0)
                                                    <x-ui.badge tone="violet">{{ $serie->year_classrooms_count }} {{ __('core.academic_years.show.cycle_management_classes_label') }}</x-ui.badge>
                                                @endif
                                                <x-ui.badge tone="amber">{{ $serie->year_coefficients_count }} {{ __('ui.nav.academic_coefficients') }}</x-ui.badge>
                                            </div>
                                        </td>
                                        <td class="table-body-cell">
                                            @if ($canToggleSeries)
                                                <div class="flex justify-end">
                                                    <form method="POST" action="{{ route('core.academic-years.cycles.series.toggle-active', ['academic_year' => $academicYear, 'cycle' => $cycle, 'series' => $serie]) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <x-ui.button type="submit" :variant="$isActiveForYear ? 'soft-amber' : 'soft-violet'" size="sm" class="academic-year-action-button">
                                                            {{ $isActiveForYear ? __('core.academic_years.show.remove_from_year_short_action') : __('core.academic_years.show.add_to_year_short_action') }}
                                                        </x-ui.button>
                                                    </form>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-ui.table>
                    @else
                        <x-ui.empty-state
                            :title="__('core.academic_years.show.annual_series_empty_title')"
                            :description="__('core.academic_years.show.annual_series_empty_description')"
                            icon="fa-sitemap"
                        />
                    @endif
                </x-ui.card>
            @else
                <div class="space-y-6">
                    <x-ui.card class="space-y-4">
                        <div class="flex items-start gap-4">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-200">
                                <i class="fa-solid fa-layer-group text-sm"></i>
                            </span>

                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.academic_years.show.series_not_applicable_title') }}</h2>
                                <p class="mt-1 text-sm leading-7 text-gray-500 dark:text-gray-400">
                                    {{ __('core.academic_years.show.series_not_applicable_description') }}
                                </p>
                            </div>
                        </div>
                    </x-ui.card>
                </div>
            @endif
        </section>

        <section>
            <x-ui.card class="space-y-5 overflow-hidden">
                <div class="flex flex-col gap-3 border-b border-gray-100 pb-4 dark:border-gray-800 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-violet-100 text-sm font-semibold text-violet-700 dark:bg-violet-900/40 dark:text-violet-200">
                                {{ $seriesRelevant ? '3' : '2' }}
                            </span>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.academic_years.show.annual_classes_title') }}</h2>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('core.academic_years.show.annual_classes_description') }}</p>
                    </div>

                    @if ($canCreateClassroom && $canStartClasses)
                        <x-ui.button :href="route('academic.classes.create', ['academic_year_id' => $academicYear->id, 'cycle_id' => $cycle->id, 'return_to_year_cycle' => 1])" size="sm" class="academic-year-action-button">
                            {{ __('academic.classes.index.create_action') }}
                        </x-ui.button>
                    @endif
                </div>

                @if ($classrooms->isNotEmpty())
                    <x-ui.table>
                        <thead>
                            <tr>
                                <th class="table-head-cell">{{ __('core.academic_years.show.classes_table_classroom_label') }}</th>
                                <th class="table-head-cell">{{ __('core.academic_years.show.classes_table_structure_label') }}</th>
                                <th class="table-head-cell">{{ __('core.academic_years.show.classes_table_status_label') }}</th>
                                <th class="table-head-cell text-right">{{ __('core.academic_years.show.classes_table_actions_label') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($classrooms as $classroom)
                                <tr class="table-body-row">
                                    <td class="table-body-cell">
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $classroom->name }}</p>
                                        @if ($classroom->capacity)
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $classroom->capacity }} {{ __('academic.classes.index.table.capacity') }}</p>
                                        @endif
                                    </td>
                                    <td class="table-body-cell">
                                        <div class="flex flex-wrap gap-2">
                                            <x-ui.badge tone="sky">{{ $classroom->level->name }}</x-ui.badge>
                                            @if ($classroom->series)
                                                <x-ui.badge tone="violet">{{ $classroom->series->name }}</x-ui.badge>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="table-body-cell">
                                        <x-ui.badge :tone="$classroom->is_active ? 'emerald' : 'slate'">
                                            {{ $classroom->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="table-body-cell">
                                        <div class="flex justify-end gap-2">
                                            <x-ui.button :href="route('academic.classes.show', $classroom)" variant="soft-slate" size="sm" class="academic-year-action-button">
                                                {{ __('core.common.actions.view') }}
                                            </x-ui.button>
                                            @if ($canUpdateClassroom)
                                                <x-ui.button :href="route('academic.classes.edit', ['classroom' => $classroom, 'return_to_year_cycle' => 1, 'academic_year_id' => $academicYear->id, 'cycle_id' => $cycle->id])" variant="soft-sky" size="sm" class="academic-year-action-button">
                                                    {{ __('core.common.actions.edit') }}
                                                </x-ui.button>
                                            @endif
                                            @if ($canToggleClassroom)
                                                <form method="POST" action="{{ route('academic.classes.toggle-active', $classroom) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <x-ui.button type="submit" variant="{{ $classroom->is_active ? 'soft-amber' : 'soft-emerald' }}" size="sm" class="academic-year-action-button">
                                                        {{ $classroom->is_active ? __('academic.classes.show.toggle_disable') : __('academic.classes.show.toggle_enable') }}
                                                    </x-ui.button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
                @else
                    <x-ui.empty-state
                        :title="__('core.academic_years.show.annual_classes_empty_title')"
                        :description="$canStartClasses ? __('core.academic_years.show.annual_classes_empty_description') : $classroomBlockedDescription"
                        icon="fa-chalkboard-user"
                    >
                        @if ($canCreateClassroom && $canStartClasses)
                            <x-slot:actions>
                                <x-ui.button :href="route('academic.classes.create', ['academic_year_id' => $academicYear->id, 'cycle_id' => $cycle->id, 'return_to_year_cycle' => 1])" class="academic-year-action-button">
                                    {{ __('academic.classes.index.create_action') }}
                                </x-ui.button>
                            </x-slot:actions>
                        @endif
                    </x-ui.empty-state>
                @endif
            </x-ui.card>
        </section>
    </div>
</x-app-layout>
