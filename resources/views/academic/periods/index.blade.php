<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('academic.periods.index.title')" :description="__('academic.periods.index.description')">
            <x-slot:actions>
                @can('create', \App\Models\AcademicPeriod::class)
                    <x-ui.button :href="route('academic.periods.create')">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('academic.periods.index.create_action') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('academic.periods.index.stats.total')" :value="$stats['total']" icon="fa-layer-group" tone="sky" />
            <x-ui.stats-card :label="__('academic.periods.index.stats.active')" :value="$stats['active']" icon="fa-circle-check" tone="emerald" />
            <x-ui.stats-card :label="__('academic.periods.index.stats.locked')" :value="$stats['locked']" icon="fa-lock" tone="rose" />
            <x-ui.stats-card :label="__('academic.periods.index.stats.open')" :value="$stats['open']" icon="fa-calendar-day" tone="violet" />
        </section>

        <x-ui.card>
            <form method="GET" data-auto-submit class="grid gap-3 xl:grid-cols-[minmax(0,1.2fr)_220px_240px_240px_220px_220px_auto] xl:items-center">
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('academic.periods.index.search_placeholder') }}" class="form-input">

                <select name="academic_year_id" class="form-input">
                    <option value="">{{ __('academic.periods.index.all_years') }}</option>
                    @foreach ($academicYears as $academicYear)
                        <option value="{{ $academicYear->id }}" @selected($academicYearId === $academicYear->id)>{{ $academicYear->name }}</option>
                    @endforeach
                </select>

                <select name="academic_calendar_id" class="form-input">
                    <option value="">{{ __('academic.common.calendar') }}</option>
                    @foreach ($calendars as $calendar)
                        <option value="{{ $calendar->id }}" @selected($academicCalendarId === $calendar->id)>{{ $calendar->academicYear->name }} - {{ $calendar->name }}</option>
                    @endforeach
                </select>

                <select name="academic_configuration_id" class="form-input">
                    <option value="">{{ __('academic.periods.index.all_configurations') }}</option>
                    @foreach ($configurations as $configuration)
                        <option value="{{ $configuration->id }}" @selected($configurationId === $configuration->id)>{{ $configuration->academicYear->name }} - {{ $configuration->school->name }}</option>
                    @endforeach
                </select>

                <select name="status" class="form-input">
                    <option value="">{{ __('core.common.filters.all_statuses') }}</option>
                    <option value="active" @selected($status === 'active')>{{ __('core.common.states.active') }}</option>
                    <option value="inactive" @selected($status === 'inactive')>{{ __('core.common.states.inactive') }}</option>
                </select>

                <select name="locked" class="form-input">
                    <option value="">{{ __('academic.periods.index.all_lock_states') }}</option>
                    <option value="locked" @selected($locked === 'locked')>{{ __('academic.common.locked') }}</option>
                    <option value="unlocked" @selected($locked === 'unlocked')>{{ __('academic.common.unlocked') }}</option>
                </select>

                <x-ui.button :href="route('academic.periods.index')" variant="ghost">
                    {{ __('core.common.actions.reset') }}
                </x-ui.button>
            </form>
        </x-ui.card>

        @if ($periods->count() > 0)
            <x-ui.table>
                <thead>
                    <tr>
                        <th class="table-head-cell">{{ __('academic.periods.index.table.period') }}</th>
                        <th class="table-head-cell">{{ __('academic.common.calendar') }}</th>
                        <th class="table-head-cell">{{ __('academic.periods.index.table.configuration') }}</th>
                        <th class="table-head-cell">{{ __('academic.periods.index.table.dates') }}</th>
                        <th class="table-head-cell">{{ __('academic.periods.index.table.status') }}</th>
                        <th class="table-head-cell text-right">{{ __('academic.periods.index.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($periods as $period)
                        <tr class="table-body-row">
                            <td class="table-body-cell">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $period->name }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">#{{ $period->position }}</p>
                            </td>
                            <td class="table-body-cell">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $period->academicCalendar?->name ?? $period->configuration?->calendar?->name ?? __('academic.common.not_configured') }}</p>
                                @if ($period->academicCalendar?->scope_type ?? $period->configuration?->calendar?->scope_type)
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ ($period->academicCalendar?->scope_type ?? $period->configuration?->calendar?->scope_type)->label() }}</p>
                                @endif
                            </td>
                            <td class="table-body-cell">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $period->configuration?->academicYear?->name ?? __('academic.common.not_configured') }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $period->configuration?->school?->name ?? __('academic.common.not_configured') }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    @if ($period->configuration?->scope_type)
                                        <x-ui.badge tone="sky">{{ $period->configuration->scope_type->label() }}</x-ui.badge>
                                    @endif
                                    @if ($period->configuration?->scopeReferenceLabel())
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $period->configuration->scopeReferenceLabel() }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="table-body-cell text-gray-500 dark:text-gray-400">
                                {{ $period->start_date?->translatedFormat('d M Y') }}<br>
                                {{ $period->end_date?->translatedFormat('d M Y') }}
                            </td>
                            <td class="table-body-cell">
                                <div class="flex flex-wrap gap-2">
                                    <x-ui.badge :tone="$period->is_active ? 'emerald' : 'slate'">
                                        {{ $period->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                                    </x-ui.badge>
                                    <x-ui.badge :tone="$period->is_locked ? 'rose' : 'sky'">
                                        {{ $period->is_locked ? __('academic.common.locked') : __('academic.common.unlocked') }}
                                    </x-ui.badge>
                                </div>
                            </td>
                            <td class="table-body-cell">
                                <div class="flex justify-end gap-2">
                                    <x-ui.button :href="route('academic.periods.show', $period)" variant="ghost" size="sm">
                                        {{ __('core.common.actions.view') }}
                                    </x-ui.button>
                                    @can('update', $period)
                                        <x-ui.button :href="route('academic.periods.edit', $period)" variant="ghost" size="sm">
                                            {{ __('core.common.actions.edit') }}
                                        </x-ui.button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <x-ui.pagination :paginator="$periods" />
        @else
            <x-ui.empty-state :title="__('academic.periods.index.empty.title')" :description="__('academic.periods.index.empty.description')" icon="fa-layer-group">
                <x-slot:actions>
                    @can('create', \App\Models\AcademicPeriod::class)
                        <x-ui.button :href="route('academic.periods.create')">
                            <i class="fa-solid fa-plus text-xs"></i>
                            {{ __('academic.periods.index.create_action') }}
                        </x-ui.button>
                    @endcan
                </x-slot:actions>
            </x-ui.empty-state>
        @endif
    </div>
</x-app-layout>
