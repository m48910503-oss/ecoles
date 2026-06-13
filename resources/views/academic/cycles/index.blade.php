<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('academic.cycles.index.title')" :description="__('academic.cycles.index.description')">
            <x-slot:actions>
                @can('create', \App\Models\AcademicCycle::class)
                    <x-ui.button :href="route('academic.cycles.create')">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('academic.cycles.index.create_action') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card size="compact" :label="__('academic.cycles.index.stats.total')" :value="$stats['total']" icon="fa-diagram-project" tone="sky" />
            <x-ui.stats-card size="compact" :label="__('academic.cycles.index.stats.active')" :value="$stats['active']" icon="fa-circle-check" tone="emerald" />
            <x-ui.stats-card size="compact" :label="__('academic.cycles.index.stats.supports_series')" :value="$stats['supports_series']" icon="fa-sitemap" tone="violet" />
            <x-ui.stats-card size="compact" :label="__('academic.cycles.index.stats.referenced')" :value="$stats['referenced']" icon="fa-link" tone="amber" />
        </section>

        <x-ui.card variant="dense" padding="p-4">
            <form method="GET" data-auto-submit class="grid gap-3 xl:grid-cols-[minmax(0,1.4fr)_220px_220px_220px_auto] xl:items-center">
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('academic.cycles.index.search_placeholder') }}" class="form-input">

                <div class="space-y-1.5">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">{{ __('academic.cycles.index.group_label') }}</p>
                    <select name="group" class="form-input">
                        <option value="">{{ __('academic.cycles.index.all_groups') }}</option>
                        @foreach ($groupOptions as $groupValue => $groupLabel)
                            <option value="{{ $groupValue }}" @selected($group === $groupValue)>{{ $groupLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <select name="status" class="form-input">
                    <option value="">{{ __('core.common.filters.all_statuses') }}</option>
                    <option value="active" @selected($status === 'active')>{{ __('core.common.states.active') }}</option>
                    <option value="inactive" @selected($status === 'inactive')>{{ __('core.common.states.inactive') }}</option>
                </select>

                <x-ui.button :href="route('academic.cycles.index')" variant="ghost" size="sm">
                    {{ __('core.common.actions.reset') }}
                </x-ui.button>
            </form>
        </x-ui.card>

        @if ($cycles->count() > 0)
            <x-ui.table>
                <thead>
                    <tr>
                        <th class="table-head-cell">{{ __('academic.cycles.index.table.cycle') }}</th>
                        <th class="table-head-cell">{{ __('academic.cycles.index.table.structure') }}</th>
                        <th class="table-head-cell">{{ __('academic.cycles.index.table.status') }}</th>
                        <th class="table-head-cell text-right">{{ __('academic.cycles.index.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($cycles as $cycle)
                        <tr class="table-body-row">
                            <td class="table-body-cell">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $cycle->name }}</p>
                                <x-ui.badge :tone="$cycle->category->tone()" class="mt-2">
                                    {{ $cycle->category->label() }}
                                </x-ui.badge>
                                <p class="mt-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">{{ __('academic.common.reference_id') }}</p>
                                <p class="mt-1 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $cycle->code }}</p>
                                @if ($cycle->description)
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $cycle->description }}</p>
                                @endif
                            </td>
                            <td class="table-body-cell text-gray-500 dark:text-gray-400">
                                <div class="space-y-1 text-xs">
                                    <p>{{ __('academic.cycles.show.levels') }}: <span class="font-semibold text-gray-900 dark:text-white">{{ $cycle->levels_count }}</span></p>
                                    <p>{{ __('academic.cycles.show.series') }}: <span class="font-semibold text-gray-900 dark:text-white">{{ $cycle->series_count }}</span></p>
                                    <p>{{ __('academic.cycles.show.calendars') }}: <span class="font-semibold text-gray-900 dark:text-white">{{ $cycle->calendars_count }}</span></p>
                                    <p>{{ __('academic.cycles.show.day_configurations') }}: <span class="font-semibold text-gray-900 dark:text-white">{{ $cycle->school_day_configurations_count }}</span></p>
                                </div>
                            </td>
                            <td class="table-body-cell">
                                <x-ui.badge :tone="$cycle->is_active ? 'emerald' : 'slate'">
                                    {{ $cycle->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                                </x-ui.badge>
                            </td>
                            <td class="table-body-cell">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <x-ui.button :href="route('academic.cycles.show', $cycle)" variant="info" size="sm">
                                        {{ __('core.common.actions.view') }}
                                    </x-ui.button>
                                    @can('update', $cycle)
                                        <x-ui.button :href="route('academic.cycles.edit', $cycle)" variant="warning" size="sm">
                                            {{ __('core.common.actions.edit') }}
                                        </x-ui.button>
                                    @endcan
                                    @can('delete', $cycle)
                                        <x-ui.confirm-modal
                                            :name="'delete-cycle-'.$cycle->id"
                                            :action="route('academic.cycles.destroy', $cycle)"
                                            :description="__('academic.cycles.index.delete_confirm')"
                                        />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <x-ui.pagination :paginator="$cycles" />
        @else
            <x-ui.empty-state :title="__('academic.cycles.index.empty.title')" :description="__('academic.cycles.index.empty.description')" icon="fa-diagram-project">
                <x-slot:actions>
                    @can('create', \App\Models\AcademicCycle::class)
                        <x-ui.button :href="route('academic.cycles.create')">
                            <i class="fa-solid fa-plus text-xs"></i>
                            {{ __('academic.cycles.index.create_action') }}
                        </x-ui.button>
                    @endcan
                </x-slot:actions>
            </x-ui.empty-state>
        @endif
    </div>
</x-app-layout>
