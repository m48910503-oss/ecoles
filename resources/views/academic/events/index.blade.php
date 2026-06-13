<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('academic.events.index.title')" :description="__('academic.events.index.description')">
            <x-slot:actions>
                @can('create', \App\Models\CalendarEvent::class)
                    <x-ui.button :href="route('academic.events.create')">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('academic.events.index.create_action') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('academic.events.index.stats.upcoming')" :value="$stats['upcoming']" icon="fa-calendar-day" tone="sky" />
            <x-ui.stats-card :label="__('academic.events.index.stats.drafts')" :value="$stats['drafts']" icon="fa-file-pen" tone="amber" />
            <x-ui.stats-card :label="__('academic.events.index.stats.published')" :value="$stats['published']" icon="fa-bullhorn" tone="emerald" />
            <x-ui.stats-card :label="__('academic.events.index.stats.internal')" :value="$stats['internal']" icon="fa-lock" tone="rose" />
        </section>

        <x-ui.card>
            <form method="GET" data-auto-submit class="grid gap-3 xl:grid-cols-[minmax(0,1.2fr)_repeat(9,minmax(150px,1fr))] xl:items-center">
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('academic.events.index.search_placeholder') }}" class="form-input">

                <select name="academic_year_id" class="form-input">
                    <option value="">{{ __('academic.calendars.index.all_years') }}</option>
                    @foreach ($academicYears as $academicYear)
                        <option value="{{ $academicYear->id }}" @selected($academicYearId === $academicYear->id)>{{ $academicYear->name }}</option>
                    @endforeach
                </select>

                <select name="academic_calendar_id" class="form-input">
                    <option value="">{{ __('academic.events.index.all_calendars') }}</option>
                    @foreach ($calendars as $calendar)
                        <option value="{{ $calendar->id }}" @selected($calendarId === $calendar->id)>{{ $calendar->name }}</option>
                    @endforeach
                </select>

                <select name="event_type" class="form-input">
                    <option value="">{{ __('academic.events.index.all_types') }}</option>
                    @foreach ($eventTypes as $typeOption)
                        <option value="{{ $typeOption->value }}" @selected($eventType === $typeOption->value)>{{ $typeOption->label() }}</option>
                    @endforeach
                </select>

                <select name="status" class="form-input">
                    <option value="">{{ __('academic.events.index.all_statuses') }}</option>
                    @foreach ($statusOptions as $statusOption)
                        <option value="{{ $statusOption->value }}" @selected($status === $statusOption->value)>{{ $statusOption->label() }}</option>
                    @endforeach
                </select>

                <select name="target_type" class="form-input">
                    <option value="">{{ __('academic.events.index.all_targets') }}</option>
                    @foreach ($targetOptions as $targetOption)
                        <option value="{{ $targetOption->value }}" @selected($targetType === $targetOption->value)>{{ $targetOption->label() }}</option>
                    @endforeach
                </select>

                <select name="visibility" class="form-input">
                    <option value="">{{ __('academic.events.index.all_visibility') }}</option>
                    @foreach ($visibilityOptions as $visibilityOption)
                        <option value="{{ $visibilityOption->value }}" @selected($visibility === $visibilityOption->value)>{{ $visibilityOption->label() }}</option>
                    @endforeach
                </select>

                <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-input" placeholder="{{ __('academic.events.index.from') }}">
                <input type="date" name="date_to" value="{{ $dateTo }}" class="form-input" placeholder="{{ __('academic.events.index.to') }}">

                @if ($canChooseSchool)
                    <select name="school_id" class="form-input">
                        <option value="">{{ __('academic.common.school') }}</option>
                        @foreach ($schools as $school)
                            <option value="{{ $school->id }}" @selected($schoolId === $school->id)>{{ $school->name }}</option>
                        @endforeach
                    </select>
                @endif

                <input type="hidden" name="view_mode" value="{{ $viewMode }}">

                <div class="flex flex-wrap gap-3 xl:col-span-full">
                    <x-ui.button :href="route('academic.events.index')" variant="ghost">
                        {{ __('core.common.actions.reset') }}
                    </x-ui.button>
                    <a href="{{ request()->fullUrlWithQuery(['view_mode' => 'list']) }}" class="{{ $viewMode === 'list' ? 'btn-primary' : 'btn-secondary' }}">{{ __('academic.events.index.view_list') }}</a>
                    <a href="{{ request()->fullUrlWithQuery(['view_mode' => 'month']) }}" class="{{ $viewMode === 'month' ? 'btn-primary' : 'btn-secondary' }}">{{ __('academic.events.index.view_month') }}</a>
                </div>
            </form>
        </x-ui.card>

        @if ($events->count() === 0)
            <x-ui.empty-state :title="__('academic.events.index.empty.title')" :description="__('academic.events.index.empty.description')" icon="fa-calendar-check">
                <x-slot:actions>
                    @can('create', \App\Models\CalendarEvent::class)
                        <x-ui.button :href="route('academic.events.create')">
                            <i class="fa-solid fa-plus text-xs"></i>
                            {{ __('academic.events.index.create_action') }}
                        </x-ui.button>
                    @endcan
                </x-slot:actions>
            </x-ui.empty-state>
        @elseif ($viewMode === 'month')
            <div class="grid gap-5 lg:grid-cols-2">
                @foreach ($monthGroups as $month => $monthEvents)
                    <x-ui.card class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $month }}</h2>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $monthEvents->count() }}</span>
                        </div>

                        <div class="space-y-3">
                            @foreach ($monthEvents as $event)
                                <article class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-950/50">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-gray-900 dark:text-white">{{ $event->title }}</p>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $event->calendar->name }}</p>
                                        </div>
                                        <x-ui.badge :tone="$event->status->tone()">{{ $event->status->label() }}</x-ui.badge>
                                    </div>
                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <x-ui.badge :tone="$event->event_type->tone()">{{ $event->event_type->label() }}</x-ui.badge>
                                        <x-ui.badge :tone="$event->visibility->tone()">{{ $event->visibility->label() }}</x-ui.badge>
                                        @if ($event->period)
                                            <x-ui.badge tone="violet">{{ $event->period->name }}</x-ui.badge>
                                        @endif
                                    </div>
                                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $event->start_datetime?->translatedFormat('d M Y H:i') }} - {{ $event->end_datetime?->translatedFormat('d M Y H:i') }}
                                    </p>
                                </article>
                            @endforeach
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        @else
            <x-ui.table>
                <thead>
                    <tr>
                        <th class="table-head-cell">{{ __('academic.events.index.table.event') }}</th>
                        <th class="table-head-cell">{{ __('academic.events.index.table.calendar') }}</th>
                        <th class="table-head-cell">{{ __('academic.events.index.table.date') }}</th>
                        <th class="table-head-cell">{{ __('academic.events.index.table.target') }}</th>
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
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <x-ui.badge :tone="$event->event_type->tone()">{{ $event->event_type->label() }}</x-ui.badge>
                                    <x-ui.badge tone="sky">{{ $event->target_type->label() }}</x-ui.badge>
                                </div>
                            </td>
                            <td class="table-body-cell text-gray-500 dark:text-gray-400">
                                {{ $event->calendar->name }}<br>
                                <span class="text-xs">{{ $event->calendar->academicYear->name }}</span>
                            </td>
                            <td class="table-body-cell text-gray-500 dark:text-gray-400">
                                {{ $event->start_datetime?->translatedFormat('d M Y H:i') }}<br>
                                {{ $event->end_datetime?->translatedFormat('d M Y H:i') }}
                                @if ($event->period)
                                    <p class="mt-2 text-xs text-violet-600 dark:text-violet-300">{{ $event->period->name }}</p>
                                @endif
                            </td>
                            <td class="table-body-cell">
                                <x-ui.badge tone="sky">{{ $event->target_type->label() }}</x-ui.badge>
                                @if (in_array($event->target_type->value, ['cycle', 'level', 'class'], true))
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $event->targetReferenceLabel() }}</p>
                                @endif
                            </td>
                            <td class="table-body-cell">
                                <x-ui.badge :tone="$event->status->tone()">{{ $event->status->label() }}</x-ui.badge>
                            </td>
                            <td class="table-body-cell">
                                <x-ui.badge :tone="$event->visibility->tone()">{{ $event->visibility->label() }}</x-ui.badge>
                            </td>
                            <td class="table-body-cell text-right">
                                <div class="flex justify-end gap-2">
                                    <x-ui.button :href="route('academic.events.show', $event)" variant="ghost" size="sm">
                                        {{ __('core.common.actions.view') }}
                                    </x-ui.button>
                                    @can('update', $event)
                                        <x-ui.button :href="route('academic.events.edit', $event)" variant="ghost" size="sm">
                                            {{ __('core.common.actions.edit') }}
                                        </x-ui.button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <x-ui.pagination :paginator="$events" />
        @endif
    </div>
</x-app-layout>
