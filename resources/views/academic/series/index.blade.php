<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('academic.series.index.title')" :description="__('academic.series.index.description')">
            <x-slot:actions>
                @can('create', \App\Models\AcademicSeries::class)
                    <x-ui.button :href="route('academic.series.create')">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('academic.series.index.create_action') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card size="compact" :label="__('academic.series.index.stats.total')" :value="$stats['total']" icon="fa-sitemap" tone="sky" />
            <x-ui.stats-card size="compact" :label="__('academic.series.index.stats.active')" :value="$stats['active']" icon="fa-circle-check" tone="emerald" />
            <x-ui.stats-card size="compact" :label="__('academic.series.index.stats.classrooms')" :value="$stats['classrooms']" icon="fa-chalkboard-user" tone="violet" />
            <x-ui.stats-card size="compact" :label="__('academic.series.index.stats.coefficients')" :value="$stats['coefficients']" icon="fa-square-root-variable" tone="amber" />
        </section>

        <x-ui.card variant="dense" padding="p-4">
            <form method="GET" data-auto-submit class="grid gap-3 xl:grid-cols-[minmax(0,1.4fr)_240px_220px_auto] xl:items-center">
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('academic.series.index.search_placeholder') }}" class="form-input">

                <select name="cycle_id" class="form-input">
                    <option value="">{{ __('academic.series.index.all_cycles') }}</option>
                    @foreach ($cycles as $cycle)
                        <option value="{{ $cycle->id }}" @selected($cycleId === $cycle->id)>{{ $cycle->name }}</option>
                    @endforeach
                </select>

                <select name="status" class="form-input">
                    <option value="">{{ __('core.common.filters.all_statuses') }}</option>
                    <option value="active" @selected($status === 'active')>{{ __('core.common.states.active') }}</option>
                    <option value="inactive" @selected($status === 'inactive')>{{ __('core.common.states.inactive') }}</option>
                </select>

                <x-ui.button :href="route('academic.series.index')" variant="ghost">
                    {{ __('core.common.actions.reset') }}
                </x-ui.button>
            </form>
        </x-ui.card>

        @if ($seriesCollection->count() > 0)
            <x-ui.table>
                <thead>
                    <tr>
                        <th class="table-head-cell">{{ __('academic.series.index.table.series') }}</th>
                        <th class="table-head-cell">{{ __('academic.series.index.table.cycle') }}</th>
                        <th class="table-head-cell">{{ __('academic.series.index.table.status') }}</th>
                        <th class="table-head-cell text-right">{{ __('academic.series.index.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($seriesCollection as $series)
                        <tr class="table-body-row">
                            <td class="table-body-cell">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $series->name }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $series->short_name ?: '—' }}</p>
                                @if ($series->description)
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $series->description }}</p>
                                @endif
                                <p class="mt-2 text-[11px] font-medium text-gray-400 dark:text-gray-500">
                                    {{ __('academic.series.index.usages.classrooms') }}: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $series->classrooms_count }}</span>
                                    <span class="mx-1">·</span>
                                    {{ __('academic.series.index.usages.coefficients') }}: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $series->coefficients_count }}</span>
                                </p>
                            </td>
                            <td class="table-body-cell">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $series->cycle->name }}</p>
                            </td>
                            <td class="table-body-cell">
                                <x-ui.badge :tone="$series->is_active ? 'emerald' : 'slate'">
                                    {{ $series->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                                </x-ui.badge>
                            </td>
                            <td class="table-body-cell">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <x-ui.button :href="route('academic.series.show', $series)" variant="info" size="sm">
                                        {{ __('core.common.actions.view') }}
                                    </x-ui.button>
                                    @can('update', $series)
                                        <x-ui.button :href="route('academic.series.edit', $series)" variant="warning" size="sm">
                                            {{ __('core.common.actions.edit') }}
                                        </x-ui.button>
                                    @endcan
                                    @can('delete', $series)
                                        <x-ui.confirm-modal
                                            :name="'delete-series-'.$series->id"
                                            :action="route('academic.series.destroy', $series)"
                                            :description="__('academic.series.index.delete_confirm')"
                                        />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <x-ui.pagination :paginator="$seriesCollection" />
        @else
            <x-ui.empty-state :title="__('academic.series.index.empty.title')" :description="__('academic.series.index.empty.description')" icon="fa-sitemap">
                <x-slot:actions>
                    @can('create', \App\Models\AcademicSeries::class)
                        <x-ui.button :href="route('academic.series.create')">
                            <i class="fa-solid fa-plus text-xs"></i>
                            {{ __('academic.series.index.create_action') }}
                        </x-ui.button>
                    @endcan
                </x-slot:actions>
            </x-ui.empty-state>
        @endif
    </div>
</x-app-layout>
