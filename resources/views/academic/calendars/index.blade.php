<x-app-layout>
    <x-slot name="header">
            <x-ui.page-header :title="__('academic.calendars.index.title')" :description="__('academic.calendars.index.description')">
            <x-slot:actions>
                @can('viewAny', \App\Models\AcademicCalendar::class)
                    <x-ui.button :href="route('academic.calendars.periods.index', array_filter(['academic_year_id' => $academicYearId]))" variant="secondary">
                        <i class="fa-solid fa-wand-magic-sparkles text-xs"></i>
                        {{ __('academic.calendars.index.manage_periods_action') }}
                    </x-ui.button>
                @endcan
                @can('create', \App\Models\AcademicCalendar::class)
                    <x-ui.button :href="route('academic.calendars.create')">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('academic.calendars.index.create_action') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('academic.calendars.index.stats.active_calendars')" :value="$stats['active_calendars']" icon="fa-calendar-week" tone="sky" />
            <x-ui.stats-card :label="__('academic.calendars.index.stats.upcoming_events')" :value="$stats['upcoming_events']" icon="fa-calendar-day" tone="emerald" />
            <x-ui.stats-card :label="__('academic.calendars.index.stats.draft_events')" :value="$stats['draft_events']" icon="fa-file-pen" tone="amber" />
            <x-ui.stats-card :label="__('academic.calendars.index.stats.published_events')" :value="$stats['published_events']" icon="fa-bullhorn" tone="violet" />
        </section>

        <x-ui.card>
            <form method="GET" data-auto-submit class="grid gap-3 xl:grid-cols-[minmax(0,1.4fr)_220px_220px_220px_220px_auto] xl:items-center">
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('academic.calendars.index.search_placeholder') }}" class="form-input">

                <select name="academic_year_id" class="form-input">
                    <option value="">{{ __('academic.calendars.index.all_years') }}</option>
                    @foreach ($academicYears as $academicYear)
                        <option value="{{ $academicYear->id }}" @selected($academicYearId === $academicYear->id)>{{ $academicYear->name }}</option>
                    @endforeach
                </select>

                <select name="scope_type" class="form-input">
                    <option value="">{{ __('academic.calendars.index.all_scopes') }}</option>
                    @foreach ($scopeOptions as $scopeOption)
                        <option value="{{ $scopeOption->value }}" @selected($scopeType === $scopeOption->value)>{{ $scopeOption->label() }}</option>
                    @endforeach
                </select>

                <select name="status" class="form-input">
                    <option value="">{{ __('core.common.filters.all_statuses') }}</option>
                    <option value="active" @selected($status === 'active')>{{ __('core.common.states.active') }}</option>
                    <option value="inactive" @selected($status === 'inactive')>{{ __('core.common.states.inactive') }}</option>
                </select>

                @if ($canChooseSchool)
                    <select name="school_id" class="form-input xl:col-span-2">
                        <option value="">{{ __('academic.common.school') }}</option>
                        @foreach ($schools as $school)
                            <option value="{{ $school->id }}" @selected($schoolId === $school->id)>{{ $school->name }}</option>
                        @endforeach
                    </select>
                @endif

                <x-ui.button :href="route('academic.calendars.index')" variant="ghost">
                    {{ __('core.common.actions.reset') }}
                </x-ui.button>
            </form>
        </x-ui.card>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_360px]">
            <div class="space-y-6">
                @if ($calendars->count() > 0)
                    <x-ui.table>
                        <thead>
                            <tr>
                                <th class="table-head-cell">{{ __('academic.calendars.index.table.calendar') }}</th>
                                <th class="table-head-cell">{{ __('academic.calendars.index.table.scope') }}</th>
                                <th class="table-head-cell">{{ __('academic.calendars.index.table.year') }}</th>
                                <th class="table-head-cell">{{ __('academic.calendars.index.table.events') }}</th>
                                <th class="table-head-cell">{{ __('academic.calendars.index.table.status') }}</th>
                                <th class="table-head-cell text-right">{{ __('academic.calendars.index.table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($calendars as $calendar)
                                <tr class="table-body-row">
                                    <td class="table-body-cell">
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $calendar->name }}</p>
                                        @if ($calendar->description)
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $calendar->description }}</p>
                                        @endif
                                        @if ($canChooseSchool)
                                            <p class="mt-2 text-xs font-medium text-gray-400 dark:text-gray-500">{{ $calendar->school->name }}</p>
                                        @endif
                                    </td>
                                    <td class="table-body-cell">
                                        <x-ui.badge tone="sky">{{ $calendar->scope_type->label() }}</x-ui.badge>
                                        @if ($calendar->scopeReferenceLabel())
                                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $calendar->scopeReferenceLabel() }}</p>
                                        @endif
                                    </td>
                                    <td class="table-body-cell text-gray-500 dark:text-gray-400">
                                        {{ $calendar->academicYear->name }}
                                    </td>
                                    <td class="table-body-cell">
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ $calendar->events_count }}</span>
                                    </td>
                                    <td class="table-body-cell">
                                        <div class="space-y-2">
                                            <x-ui.badge :tone="$calendar->is_active ? 'emerald' : 'slate'">
                                                {{ $calendar->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                                            </x-ui.badge>
                                            <x-ui.badge :tone="$calendar->validated_at ? 'emerald' : 'amber'">
                                                {{ $calendar->validated_at ? __('academic.calendars.index.validated') : __('academic.calendars.index.pending_validation') }}
                                            </x-ui.badge>
                                        </div>
                                        @if ($calendar->validated_at)
                                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                                {{ __('academic.calendars.index.validated_on', ['date' => $calendar->validated_at->translatedFormat('d M Y H:i')]) }}
                                            </p>
                                        @endif
                                    </td>
                                    <td class="table-body-cell">
                                        <div class="flex justify-end gap-2">
                                            <x-ui.button :href="route('academic.calendars.show', $calendar)" variant="ghost" size="sm">
                                                {{ __('core.common.actions.view') }}
                                            </x-ui.button>
                                            @can('update', $calendar)
                                                <x-ui.button :href="route('academic.calendars.edit', $calendar)" variant="ghost" size="sm">
                                                    {{ __('core.common.actions.edit') }}
                                                </x-ui.button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>

                    <x-ui.pagination :paginator="$calendars" />
                @else
                    <x-ui.empty-state :title="__('academic.calendars.index.empty.title')" :description="__('academic.calendars.index.empty.description')" icon="fa-calendar-week">
                        <x-slot:actions>
                            @can('create', \App\Models\AcademicCalendar::class)
                                <x-ui.button :href="route('academic.calendars.create')">
                                    <i class="fa-solid fa-plus text-xs"></i>
                                    {{ __('academic.calendars.index.create_action') }}
                                </x-ui.button>
                            @endcan
                        </x-slot:actions>
                    </x-ui.empty-state>
                @endif
            </div>

            <x-ui.card class="space-y-4">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.calendars.index.upcoming_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.calendars.index.upcoming_description') }}</p>
                </div>

                @if ($upcomingEvents->isNotEmpty())
                    <div class="space-y-3">
                        @foreach ($upcomingEvents as $event)
                            <article class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-950/50">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $event->title }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $event->calendar->name }}</p>
                                    </div>
                                    <x-ui.badge :tone="$event->event_type->tone()">{{ $event->event_type->label() }}</x-ui.badge>
                                </div>
                                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ $event->start_datetime?->translatedFormat('d M Y H:i') }}</p>
                            </article>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state :title="__('academic.calendars.index.upcoming_title')" :description="__('academic.calendars.index.empty.description')" icon="fa-calendar-day" class="px-4 py-6" />
                @endif
            </x-ui.card>
        </section>
    </div>
</x-app-layout>
