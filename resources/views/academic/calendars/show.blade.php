<x-app-layout>
    @php
        $eventRouteParameters = [
            'academic_year_id' => $calendar->academic_year_id,
            'academic_calendar_id' => $calendar->id,
        ];

        if ($calendar->scope_type === \App\Enums\Academic\CalendarScopeType::Cycle && $calendar->singleCoveredCycleId()) {
            $eventRouteParameters['target_type'] = \App\Enums\Academic\CalendarTargetType::Cycle->value;
            $eventRouteParameters['target_id'] = $calendar->singleCoveredCycleId();
            $eventRouteParameters['target_cycle_id'] = $calendar->singleCoveredCycleId();
        } elseif ($calendar->scope_type === \App\Enums\Academic\CalendarScopeType::Level && $calendar->level_id) {
            $eventRouteParameters['target_type'] = \App\Enums\Academic\CalendarTargetType::Level->value;
            $eventRouteParameters['target_id'] = $calendar->level_id;
            $eventRouteParameters['target_cycle_id'] = $calendar->level?->cycle_id;
        } elseif ($calendar->scope_type === \App\Enums\Academic\CalendarScopeType::ClassRoom && $calendar->class_id) {
            $eventRouteParameters['target_type'] = \App\Enums\Academic\CalendarTargetType::ClassRoom->value;
            $eventRouteParameters['target_id'] = $calendar->class_id;
            $eventRouteParameters['target_cycle_id'] = $calendar->classroom?->level?->cycle_id;
        }
    @endphp

    <x-slot name="header">
        <x-ui.page-header :title="$calendar->name" :description="$calendar->description ?: __('academic.calendars.index.description')">
            <x-slot:actions>
                <x-ui.button :href="route('academic.calendars.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
                @can('viewAny', \App\Models\AcademicCalendar::class)
                    <x-ui.button :href="route('academic.calendars.periods.index', ['academic_year_id' => $calendar->academic_year_id, 'academic_calendar_id' => $calendar->id, 'school_id' => $calendar->school_id])" variant="secondary">
                        {{ __('academic.calendars.show.manage_periods_action') }}
                    </x-ui.button>
                @endcan
                @if ($calendar->validated_at && $periods->isNotEmpty())
                    @can('create', \App\Models\CalendarEvent::class)
                        <x-ui.button :href="route('academic.events.create', array_filter($eventRouteParameters))" variant="soft-emerald">
                            {{ __('academic.events.index.create_action') }}
                        </x-ui.button>
                    @endcan
                @endif
                @can('update', $calendar)
                    <x-ui.button :href="route('academic.calendars.edit', $calendar)" variant="secondary">
                        {{ __('core.common.actions.edit') }}
                    </x-ui.button>
                @endcan
                @can('disable', $calendar)
                    <form method="POST" action="{{ route('academic.calendars.toggle-active', $calendar) }}">
                        @csrf
                        @method('PATCH')
                        <x-ui.button type="submit">
                            {{ $calendar->is_active ? __('academic.calendars.show.toggle_disable') : __('academic.calendars.show.toggle_enable') }}
                        </x-ui.button>
                    </form>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-6 xl:grid-cols-[1fr_0.95fr]">
            <x-ui.card class="space-y-5">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.calendars.show.year') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $calendar->academicYear->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.calendars.show.school') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $calendar->school->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.calendars.show.scope') }}</p>
                        <div class="mt-2">
                            <x-ui.badge tone="sky">{{ $calendar->scope_type->label() }}</x-ui.badge>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.calendars.show.status') }}</p>
                        <div class="mt-2">
                            <x-ui.badge :tone="$calendar->is_active ? 'emerald' : 'slate'">
                                {{ $calendar->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                            </x-ui.badge>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.calendars.show.validation') }}</p>
                        <div class="mt-2">
                            <x-ui.badge :tone="$calendar->validated_at ? 'emerald' : 'amber'">
                                {{ $calendar->validated_at ? __('academic.calendars.show.validated') : __('academic.calendars.show.pending_validation') }}
                            </x-ui.badge>
                            @if ($calendar->validated_at)
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('academic.calendars.show.validated_on', ['date' => $calendar->validated_at->translatedFormat('d M Y H:i')]) }}
                                    @if ($calendar->validator)
                                        <br>{{ __('academic.calendars.show.validated_by', ['name' => $calendar->validator->name]) }}
                                    @endif
                                </p>
                            @else
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('academic.calendars.show.validation_hint') }}</p>
                            @endif
                            @if (! $calendar->validated_at)
                                <form method="POST" action="{{ route('academic.calendars.validate', $calendar) }}" class="mt-3">
                                    @csrf
                                    @method('PATCH')
                                    <x-ui.button type="submit" variant="secondary">
                                        {{ __('academic.calendars.show.validate_action') }}
                                    </x-ui.button>
                                </form>
                            @endif
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.dashboard.stats.period_type') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $calendar->period_type?->label() ?? __('academic.common.not_configured') }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.dashboard.stats.period_count') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $calendar->period_count ?? '—' }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.calendars.show.creator') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $calendar->creator?->name ?? __('core.audit_logs.index.system') }}</p>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card class="space-y-3">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.calendars.periods.title') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('academic.calendars.show.periods_summary', ['count' => $periods->count()]) }}</p>
                <div class="rounded-2xl border border-gray-100 bg-gray-50/80 px-4 py-4 dark:border-gray-800 dark:bg-gray-950/50">
                    <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                        {{ $calendar->scopeReferenceLabel() ?: __('academic.shared.scope_types.global') }}
                    </p>
                </div>
            </x-ui.card>
        </section>

        @if ($periods->isNotEmpty())
            <x-ui.card class="space-y-5">
                <div class="flex flex-col gap-2 border-b border-gray-100 pb-4 dark:border-gray-800 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.calendars.periods.title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.calendars.periods.description') }}</p>
                    </div>
                    <x-ui.badge tone="sky">{{ $periods->count() }} {{ __('academic.configuration.index.periods_count_suffix') }}</x-ui.badge>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    @foreach ($periods as $period)
                        <article class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-950/50">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">
                                        {{ __('academic.calendars.periods.period_label', ['number' => $period->position]) }}
                                    </p>
                                    <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $period->name }}</p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <x-ui.badge :tone="$period->is_active ? 'emerald' : 'slate'">
                                        {{ $period->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                                    </x-ui.badge>
                                    <x-ui.badge :tone="$period->is_locked ? 'rose' : 'sky'">
                                        {{ $period->is_locked ? __('academic.common.locked') : __('academic.common.unlocked') }}
                                    </x-ui.badge>
                                </div>
                            </div>

                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                <div class="rounded-2xl border border-white/80 bg-white/90 p-3 dark:border-gray-800 dark:bg-gray-900/70">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">{{ __('academic.periods.form.start_date') }}</p>
                                    <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">{{ $period->start_date?->translatedFormat('d M Y') ?: '—' }}</p>
                                </div>
                                <div class="rounded-2xl border border-white/80 bg-white/90 p-3 dark:border-gray-800 dark:bg-gray-900/70">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">{{ __('academic.periods.form.end_date') }}</p>
                                    <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">{{ $period->end_date?->translatedFormat('d M Y') ?: '—' }}</p>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </x-ui.card>
        @endif

        @if ($events->count() > 0)
            <x-ui.table>
                <thead>
                    <tr>
                        <th class="table-head-cell">{{ __('academic.events.index.table.event') }}</th>
                        <th class="table-head-cell">{{ __('academic.events.index.table.date') }}</th>
                        <th class="table-head-cell">{{ __('academic.events.index.table.status') }}</th>
                        <th class="table-head-cell">{{ __('academic.events.index.table.visibility') }}</th>
                        <th class="table-head-cell text-right">{{ __('academic.events.index.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($events as $event)
                        <tr class="table-body-row">
                            <td class="table-body-cell">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $event->title }}</p>
                                <div class="mt-2">
                                    <x-ui.badge :tone="$event->event_type->tone()">{{ $event->event_type->label() }}</x-ui.badge>
                                </div>
                            </td>
                            <td class="table-body-cell text-gray-500 dark:text-gray-400">
                                {{ $event->start_datetime?->translatedFormat('d M Y H:i') }}<br>
                                {{ $event->end_datetime?->translatedFormat('d M Y H:i') }}
                            </td>
                            <td class="table-body-cell">
                                <x-ui.badge :tone="$event->status->tone()">{{ $event->status->label() }}</x-ui.badge>
                            </td>
                            <td class="table-body-cell">
                                <x-ui.badge :tone="$event->visibility->tone()">{{ $event->visibility->label() }}</x-ui.badge>
                            </td>
                            <td class="table-body-cell text-right">
                                <x-ui.button :href="route('academic.events.show', $event)" variant="ghost" size="sm">
                                    {{ __('core.common.actions.view') }}
                                </x-ui.button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <x-ui.pagination :paginator="$events" />
        @else
            <x-ui.empty-state :title="__('academic.calendars.show.events_title')" :description="__('academic.calendars.show.empty_events')" icon="fa-calendar-check" />
        @endif
    </div>
</x-app-layout>
