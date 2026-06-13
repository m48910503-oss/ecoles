<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('academic.levels.index.title')" :description="__('academic.levels.index.description')">
            <x-slot:actions>
                @can('create', \App\Models\AcademicLevel::class)
                    <x-ui.button :href="route('academic.levels.create')">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('academic.levels.index.create_action') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('academic.levels.index.stats.total')" :value="$stats['total']" icon="fa-layer-group" tone="sky" />
            <x-ui.stats-card :label="__('academic.levels.index.stats.active')" :value="$stats['active']" icon="fa-circle-check" tone="emerald" />
            <x-ui.stats-card :label="__('academic.levels.index.stats.with_series')" :value="$stats['with_series']" icon="fa-sitemap" tone="violet" />
            <x-ui.stats-card :label="__('academic.levels.index.stats.referenced')" :value="$stats['referenced']" icon="fa-calendar-week" tone="amber" />
        </section>

        <x-ui.card>
            <form method="GET" data-auto-submit class="grid gap-3 xl:grid-cols-[minmax(0,1.4fr)_240px_220px_220px_auto] xl:items-center">
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('academic.levels.index.search_placeholder') }}" class="form-input">

                <select name="cycle_id" class="form-input">
                    <option value="">{{ __('academic.levels.index.all_cycles') }}</option>
                    @foreach ($cycles as $cycle)
                        <option value="{{ $cycle->id }}" @selected($cycleId === $cycle->id)>{{ $cycle->name }}</option>
                    @endforeach
                </select>

                <select name="uses_series" class="form-input">
                    <option value="">{{ __('academic.levels.index.all_series_usage') }}</option>
                    <option value="yes" @selected($usesSeries === 'yes')>{{ __('academic.common.yes') }}</option>
                    <option value="no" @selected($usesSeries === 'no')>{{ __('academic.common.no') }}</option>
                </select>

                <select name="status" class="form-input">
                    <option value="">{{ __('core.common.filters.all_statuses') }}</option>
                    <option value="active" @selected($status === 'active')>{{ __('core.common.states.active') }}</option>
                    <option value="inactive" @selected($status === 'inactive')>{{ __('core.common.states.inactive') }}</option>
                </select>

                <x-ui.button :href="route('academic.levels.index')" variant="ghost">
                    {{ __('core.common.actions.reset') }}
                </x-ui.button>
            </form>
        </x-ui.card>

        @if ($levels->count() > 0)
            <x-ui.table>
                <thead>
                    <tr>
                        <th class="table-head-cell">{{ __('academic.levels.index.table.level') }}</th>
                        <th class="table-head-cell">{{ __('academic.levels.index.table.cycle') }}</th>
                        <th class="table-head-cell">{{ __('academic.levels.index.table.series_usage') }}</th>
                        <th class="table-head-cell">{{ __('academic.levels.index.table.status') }}</th>
                        <th class="table-head-cell text-right">{{ __('academic.levels.index.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($levels as $level)
                        <tr class="table-body-row">
                            <td class="table-body-cell">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $level->name }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $level->short_name ?: $level->code }}</p>
                                @if ($level->description)
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $level->description }}</p>
                                @endif
                            </td>
                            <td class="table-body-cell">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $level->cycle->name }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $level->cycle->category->label() }}</p>
                            </td>
                            <td class="table-body-cell">
                                <x-ui.badge :tone="$level->uses_series ? 'violet' : 'slate'">
                                    {{ $level->uses_series ? __('academic.common.yes') : __('academic.common.no') }}
                                </x-ui.badge>
                                @if ($level->series_count > 0)
                                    <x-ui.badge tone="violet">{{ $level->series_count }} {{ __('academic.levels.show.series') }}</x-ui.badge>
                                @endif
                            </td>
                            <td class="table-body-cell">
                                <div class="flex flex-wrap gap-2">
                                    <x-ui.badge :tone="$level->is_active ? 'emerald' : 'slate'">
                                        {{ $level->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                                    </x-ui.badge>
                                    @if ($level->calendars_count > 0)
                                        <x-ui.badge tone="sky">{{ $level->calendars_count }} {{ __('academic.levels.show.calendars') }}</x-ui.badge>
                                    @endif
                                    @if ($level->classrooms_count > 0)
                                        <x-ui.badge tone="emerald">{{ $level->classrooms_count }} {{ __('academic.levels.show.classrooms') }}</x-ui.badge>
                                    @endif
                                </div>
                            </td>
                            <td class="table-body-cell">
                                <div class="flex justify-end gap-2">
                                    <x-ui.button :href="route('academic.levels.show', $level)" variant="info" size="sm">
                                        {{ __('core.common.actions.view') }}
                                    </x-ui.button>
                                    @can('update', $level)
                                        <x-ui.button :href="route('academic.levels.edit', $level)" variant="warning" size="sm">
                                            {{ __('core.common.actions.edit') }}
                                        </x-ui.button>
                                    @endcan
                                    @can('delete', $level)
                                        <x-ui.confirm-modal
                                            :name="'delete-level-'.$level->id"
                                            :action="route('academic.levels.destroy', $level)"
                                            :description="__('academic.levels.index.delete_confirm')"
                                        />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <x-ui.pagination :paginator="$levels" />
        @else
            <x-ui.empty-state :title="__('academic.levels.index.empty.title')" :description="__('academic.levels.index.empty.description')" icon="fa-layer-group">
                <x-slot:actions>
                    @can('create', \App\Models\AcademicLevel::class)
                        <x-ui.button :href="route('academic.levels.create')">
                            <i class="fa-solid fa-plus text-xs"></i>
                            {{ __('academic.levels.index.create_action') }}
                        </x-ui.button>
                    @endcan
                </x-slot:actions>
            </x-ui.empty-state>
        @endif
    </div>
</x-app-layout>
