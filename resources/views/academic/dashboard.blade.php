<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('academic.dashboard.title')" :description="__('academic.dashboard.description')">
            <x-slot:actions>
                @can('viewAny', \App\Models\AcademicConfiguration::class)
                    <x-ui.button :href="route('academic.configuration.index')" variant="secondary">
                        <i class="fa-solid fa-sliders text-xs"></i>
                        {{ __('academic.dashboard.actions.configuration') }}
                    </x-ui.button>
                @endcan
                @can('viewAny', \App\Models\AcademicCalendar::class)
                    <x-ui.button :href="route('academic.calendars.index')">
                        <i class="fa-solid fa-calendar-week text-xs"></i>
                        {{ __('academic.dashboard.actions.calendars') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    @php
        $formatDayHours = static function ($configuration): string {
            if (! $configuration) {
                return '—';
            }

            if ($configuration->day_type?->value === 'continuous') {
                return trim(($configuration->start_time ?? '—').' - '.($configuration->end_time ?? '—'));
            }

            return trim(($configuration->morning_start_time ?? '—').' - '.($configuration->morning_end_time ?? '—').' / '.($configuration->afternoon_start_time ?? '—').' - '.($configuration->afternoon_end_time ?? '—'));
        };
    @endphp

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('academic.dashboard.stats.active_configuration')" :value="$activeConfiguration ? __('academic.common.yes') : __('academic.common.no')" icon="fa-sliders" tone="sky" />
            <x-ui.stats-card :label="__('academic.dashboard.stats.period_type')" :value="$stats['period_type']" icon="fa-layer-group" tone="violet" />
            <x-ui.stats-card :label="__('academic.dashboard.stats.period_count')" :value="$stats['period_count']" icon="fa-hashtag" tone="amber" />
            <x-ui.stats-card :label="__('academic.dashboard.stats.school_day_type')" :value="$stats['school_day_type']" icon="fa-clock" tone="emerald" />
            <x-ui.stats-card :label="__('academic.dashboard.stats.cycles')" :value="$stats['cycles']" icon="fa-diagram-project" tone="sky" />
            <x-ui.stats-card :label="__('academic.dashboard.stats.levels')" :value="$stats['levels']" icon="fa-layer-group" tone="violet" />
            <x-ui.stats-card :label="__('academic.dashboard.stats.series')" :value="$stats['series']" icon="fa-sitemap" tone="amber" />
            <x-ui.stats-card :label="__('academic.dashboard.stats.classes')" :value="$stats['classes']" icon="fa-chalkboard" tone="emerald" />
            <x-ui.stats-card :label="__('academic.dashboard.stats.subjects')" :value="$stats['subjects']" icon="fa-book-open" tone="sky" />
            <x-ui.stats-card :label="__('academic.dashboard.stats.coefficients')" :value="$stats['coefficients']" icon="fa-square-root-variable" tone="violet" />
            <x-ui.stats-card :label="__('academic.dashboard.stats.conduct')" :value="$stats['conduct']" icon="fa-scale-balanced" tone="amber" />
            <x-ui.stats-card :label="__('academic.dashboard.stats.active_calendars')" :value="$stats['active_calendars']" icon="fa-calendar-week" tone="sky" />
            <x-ui.stats-card :label="__('academic.dashboard.stats.upcoming_events')" :value="$stats['upcoming_events']" icon="fa-calendar-day" tone="emerald" />
            <x-ui.stats-card :label="__('academic.dashboard.stats.pending_events')" :value="$stats['pending_events']" icon="fa-hourglass-half" tone="amber" />
            <x-ui.stats-card :label="__('academic.dashboard.stats.published_events')" :value="$stats['published_events']" icon="fa-bullhorn" tone="violet" />
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_360px]">
            <div class="space-y-6">
                <x-ui.card class="space-y-5">
                    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 pb-4 dark:border-gray-800">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.dashboard.configuration_card.title') }}</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.dashboard.configuration_card.description') }}</p>
                        </div>

                        @can('create', \App\Models\AcademicConfiguration::class)
                            <x-ui.button :href="$activeConfiguration ? route('academic.configuration.edit', $activeConfiguration) : route('academic.configuration.create')" variant="ghost" size="sm">
                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                                {{ $activeConfiguration ? __('core.common.actions.edit') : __('core.common.actions.create') }}
                            </x-ui.button>
                        @endcan
                    </div>

                    @if ($activeConfiguration)
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-950/50">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">{{ __('academic.common.academic_year') }}</p>
                                <p class="mt-3 text-base font-semibold text-gray-900 dark:text-white">{{ $activeYear?->name ?? __('academic.common.not_configured') }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-950/50">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">{{ __('academic.dashboard.stats.period_type') }}</p>
                                <p class="mt-3 text-base font-semibold text-gray-900 dark:text-white">{{ $activeConfiguration->period_type->label() }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-950/50">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">{{ __('academic.dashboard.stats.period_count') }}</p>
                                <p class="mt-3 text-base font-semibold text-gray-900 dark:text-white">{{ $activeConfiguration->period_count }}</p>
                            </div>
                            <div class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-950/50">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">{{ __('academic.common.status') }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <x-ui.badge :tone="$activeConfiguration->is_active ? 'emerald' : 'slate'">
                                        {{ $activeConfiguration->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                                    </x-ui.badge>
                                    <x-ui.badge :tone="$activeConfiguration->is_locked ? 'rose' : 'sky'">
                                        {{ $activeConfiguration->is_locked ? __('academic.common.locked') : __('academic.common.unlocked') }}
                                    </x-ui.badge>
                                </div>
                            </div>
                            <div class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-950/50">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">{{ __('academic.common.scope') }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <x-ui.badge tone="sky">{{ $activeConfiguration->scope_type->label() }}</x-ui.badge>
                                    @if ($activeConfiguration->scopeReferenceLabel())
                                        <x-ui.badge tone="violet">{{ $activeConfiguration->scopeReferenceLabel() }}</x-ui.badge>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <x-ui.empty-state :title="__('academic.dashboard.configuration_card.empty_title')" :description="__('academic.dashboard.configuration_card.empty_description')" icon="fa-sliders">
                            <x-slot:actions>
                                @can('create', \App\Models\AcademicConfiguration::class)
                                    <x-ui.button :href="route('academic.configuration.create')">
                                        <i class="fa-solid fa-plus text-xs"></i>
                                        {{ __('academic.configuration.index.create_action') }}
                                    </x-ui.button>
                                @endcan
                            </x-slot:actions>
                        </x-ui.empty-state>
                    @endif
                </x-ui.card>

                <section class="grid gap-6 xl:grid-cols-2">
                    <x-ui.card class="space-y-4">
                        <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.dashboard.periods_card.title') }}</h2>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.dashboard.periods_card.description') }}</p>
                            </div>
                            @can('viewAny', \App\Models\AcademicCalendar::class)
                                <x-ui.button :href="route('academic.calendars.periods.index', array_filter(['academic_year_id' => $activeYear?->id, 'academic_calendar_id' => $activeCalendar?->id]))" variant="ghost" size="sm">
                                    {{ __('academic.calendars.index.manage_periods_action') }}
                                </x-ui.button>
                            @endcan
                        </div>

                        @if ($activePeriods->isNotEmpty())
                            <div class="space-y-3">
                                @foreach ($activePeriods as $period)
                                    <article class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-950/50">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="font-semibold text-gray-900 dark:text-white">{{ $period->name }}</p>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $period->start_date?->translatedFormat('d M Y') }} - {{ $period->end_date?->translatedFormat('d M Y') }}</p>
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                <x-ui.badge tone="sky">#{{ $period->position }}</x-ui.badge>
                                                <x-ui.badge :tone="$period->is_active ? 'emerald' : 'slate'">
                                                    {{ $period->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                                                </x-ui.badge>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <x-ui.empty-state :title="__('academic.dashboard.periods_card.empty_title')" :description="__('academic.dashboard.periods_card.empty_description')" icon="fa-layer-group" class="px-4 py-6" />
                        @endif
                    </x-ui.card>

                    <x-ui.card class="space-y-4">
                        <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.dashboard.day_config_card.title') }}</h2>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.dashboard.day_config_card.description') }}</p>
                            </div>
                            @can('viewAny', \App\Models\AcademicConfiguration::class)
                                <x-ui.button :href="$activeConfiguration ? route('academic.configuration.edit', $activeConfiguration) : route('academic.configuration.create')" variant="ghost" size="sm">
                                    {{ $activeConfiguration ? __('core.common.actions.edit') : __('core.common.actions.create') }}
                                </x-ui.button>
                            @endcan
                        </div>

                        @if ($activeDayConfiguration)
                            <div class="space-y-4">
                                <div class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-950/50">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">{{ __('academic.day_config.form.day_type') }}</p>
                                    <p class="mt-3 text-base font-semibold text-gray-900 dark:text-white">{{ $activeDayConfiguration->day_type->label() }}</p>
                                </div>
                                <div class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-950/50">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">{{ __('academic.common.hours') }}</p>
                                    <p class="mt-3 text-base font-semibold text-gray-900 dark:text-white">{{ $formatDayHours($activeDayConfiguration) }}</p>
                                    @if ($activeDayConfiguration->break_start_time && $activeDayConfiguration->break_end_time)
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $activeDayConfiguration->break_start_time }} - {{ $activeDayConfiguration->break_end_time }}</p>
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <x-ui.badge tone="sky">{{ $activeDayConfiguration->scope_type->label() }}</x-ui.badge>
                                    @if ($activeDayConfiguration->cycle)
                                        <x-ui.badge tone="violet">{{ $activeDayConfiguration->cycle->name }}</x-ui.badge>
                                    @endif
                                </div>
                            </div>
                        @else
                            <x-ui.empty-state :title="__('academic.dashboard.day_config_card.empty_title')" :description="__('academic.dashboard.day_config_card.empty_description')" icon="fa-clock" class="px-4 py-6" />
                        @endif
                    </x-ui.card>
                </section>
            </div>

            <x-ui.card class="space-y-4">
                <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.dashboard.upcoming_card.title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.dashboard.upcoming_card.description') }}</p>
                    </div>
                    @can('viewAny', \App\Models\CalendarEvent::class)
                        <x-ui.button :href="route('academic.events.index')" variant="ghost" size="sm">
                            {{ __('core.common.actions.view') }}
                        </x-ui.button>
                    @endcan
                </div>

                @if ($upcomingEvents->isNotEmpty())
                    <div class="space-y-3">
                        @foreach ($upcomingEvents as $event)
                            <article class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-950/50">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $event->title }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $event->calendar->name }}</p>
                                    </div>
                                    <x-ui.badge :tone="$event->event_type->tone()">{{ $event->event_type->label() }}</x-ui.badge>
                                </div>
                                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ $event->start_datetime?->translatedFormat('d M Y H:i') }}</p>
                            </article>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state :title="__('academic.dashboard.upcoming_card.empty_title')" :description="__('academic.dashboard.upcoming_card.empty_description')" icon="fa-calendar-day" class="px-4 py-6" />
                @endif
            </x-ui.card>
        </section>
    </div>
</x-app-layout>
