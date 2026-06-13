<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('core.schools.index.title')" :description="__('core.schools.index.description')">
            <x-slot:actions>
                @can('create', \App\Models\School::class)
                    @if ($multiSchool['enabled'])
                    <x-ui.button :href="route('core.schools.create')">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('core.schools.index.create_action') }}
                    </x-ui.button>
                    @endif
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('core.schools.index.stats.total')" :value="$stats['total']" icon="fa-building-columns" tone="sky" />
            <x-ui.stats-card :label="__('core.schools.index.stats.active')" :value="$stats['active']" icon="fa-circle-check" tone="emerald" />
            <x-ui.stats-card :label="__('core.schools.index.stats.inactive')" :value="$stats['inactive']" icon="fa-circle-pause" tone="amber" />
            <x-ui.stats-card :label="__('core.schools.index.stats.current')" :value="$stats['current_school_code']" :caption="$stats['current_school_name']" icon="fa-location-dot" tone="violet" />
        </section>

        <x-ui.alert tone="info">
            {{ __('core.schools.index.mode_info') }}
            @if ($activeAcademicYear)
                <span class="font-semibold">{{ $activeAcademicYear->name }}</span>.
            @endif
        </x-ui.alert>

        <x-ui.card>
            <form method="GET" data-auto-submit class="grid gap-3 xl:grid-cols-[minmax(0,1.2fr)_220px_auto_auto]">
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('core.schools.index.search_placeholder') }}" class="form-input">

                <select name="status" class="form-input">
                    <option value="">{{ __('core.common.filters.all_statuses') }}</option>
                    <option value="active" @selected($status === 'active')>{{ __('core.common.states.active') }}</option>
                    <option value="inactive" @selected($status === 'inactive')>{{ __('core.common.states.inactive') }}</option>
                </select>

                <x-ui.button type="submit" variant="secondary">
                    <i class="fa-solid fa-filter text-xs"></i>
                    {{ __('core.common.actions.filter') }}
                </x-ui.button>

                <x-ui.button :href="route('core.schools.index')" variant="ghost">
                    {{ __('core.common.actions.reset') }}
                </x-ui.button>
            </form>
        </x-ui.card>

        @if ($schools->count() > 0)
            <x-ui.table>
                <thead>
                    <tr>
                        <th class="table-head-cell">{{ __('core.schools.index.table.school') }}</th>
                        <th class="table-head-cell">{{ __('core.schools.index.table.contact') }}</th>
                        <th class="table-head-cell">{{ __('core.schools.index.table.status') }}</th>
                        <th class="table-head-cell">{{ __('core.schools.index.table.type') }}</th>
                        <th class="table-head-cell text-right">{{ __('core.schools.index.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($schools as $school)
                        <tr class="table-body-row">
                            <td class="table-body-cell">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $school->name }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $school->address ?: '—' }}</p>
                            </td>
                            <td class="table-body-cell text-sm text-gray-500 dark:text-gray-400">
                                <p>{{ $school->email ?: '—' }}</p>
                                <p class="mt-1">{{ $school->phone ?: '—' }}</p>
                            </td>
                            <td class="table-body-cell">
                                <x-ui.badge :tone="$school->is_active ? 'emerald' : 'rose'">
                                    {{ $school->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                                </x-ui.badge>
                            </td>
                            <td class="table-body-cell">
                                <div class="flex flex-wrap gap-2">
                                    @if ($school->is_default)
                                        <x-ui.badge tone="sky">{{ __('core.schools.index.default_badge') }}</x-ui.badge>
                                    @endif
                                    @if ($multiSchool['current_school_code'] === $school->code)
                                        <x-ui.badge tone="violet">{{ __('core.schools.index.current_badge') }}</x-ui.badge>
                                    @endif
                                </div>
                            </td>
                            <td class="table-body-cell">
                                <div class="flex justify-end gap-2">
                                    @can('update', $school)
                                        <x-ui.button :href="route('core.schools.edit', $school)" variant="ghost" size="sm">
                                            {{ __('core.common.actions.edit') }}
                                        </x-ui.button>
                                    @endcan

                                    @can('switch', $school)
                                        @if ($multiSchool['enabled'] && $school->is_active && $multiSchool['current_school_code'] !== $school->code)
                                            <form method="POST" action="{{ route('core.schools.switch', $school) }}">
                                                @csrf
                                                <x-ui.button type="submit" variant="secondary" size="sm">
                                                    {{ __('core.schools.index.switch_action') }}
                                                </x-ui.button>
                                            </form>
                                        @endif
                                    @endcan

                                    @if (! $school->is_active && ! $school->is_default)
                                        @can('delete', $school)
                                            <x-ui.confirm-modal
                                                :name="'delete-school-'.$school->id"
                                                :action="route('core.schools.destroy', $school)"
                                                :description="__('core.schools.index.delete_confirm')"
                                                :trigger-label="__('core.common.actions.delete')"
                                            />
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        @else
            <x-ui.empty-state :title="__('core.schools.index.empty.title')" :description="__('core.schools.index.empty.description')" icon="fa-building-columns">
                <x-slot:actions>
                    @can('create', \App\Models\School::class)
                        @if ($multiSchool['enabled'])
                        <x-ui.button :href="route('core.schools.create')">
                            <i class="fa-solid fa-plus text-xs"></i>
                            {{ __('core.schools.index.create_action') }}
                        </x-ui.button>
                        @endif
                    @endcan
                </x-slot:actions>
            </x-ui.empty-state>
        @endif

        <x-ui.pagination :paginator="$schools" />
    </div>
</x-app-layout>
