<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('academic.configuration.index.title')" :description="__('academic.configuration.index.description')">
            <x-slot:actions>
                @can('create', \App\Models\AcademicConfiguration::class)
                    <x-ui.button :href="route('academic.configuration.create')">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('academic.configuration.index.create_action') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('academic.configuration.index.stats.total')" :value="$stats['total']" icon="fa-sliders" tone="sky" />
            <x-ui.stats-card :label="__('academic.configuration.index.stats.active')" :value="$stats['active']" icon="fa-circle-check" tone="emerald" />
            <x-ui.stats-card :label="__('academic.configuration.index.stats.locked')" :value="$stats['locked']" icon="fa-lock" tone="rose" />
            <x-ui.stats-card :label="__('academic.configuration.index.stats.periods')" :value="$stats['periods']" icon="fa-layer-group" tone="violet" />
        </section>

        <x-ui.card>
            <form method="GET" data-auto-submit class="grid gap-3 xl:grid-cols-[minmax(0,1.2fr)_220px_220px_220px_220px_auto] xl:items-center">
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('academic.configuration.index.search_placeholder') }}" class="form-input">

                <select name="academic_year_id" class="form-input">
                    <option value="">{{ __('academic.configuration.index.all_years') }}</option>
                    @foreach ($academicYears as $academicYear)
                        <option value="{{ $academicYear->id }}" @selected($academicYearId === $academicYear->id)>{{ $academicYear->name }}</option>
                    @endforeach
                </select>

                <select name="status" class="form-input">
                    <option value="">{{ __('core.common.filters.all_statuses') }}</option>
                    <option value="active" @selected($status === 'active')>{{ __('core.common.states.active') }}</option>
                    <option value="inactive" @selected($status === 'inactive')>{{ __('core.common.states.inactive') }}</option>
                </select>

                <select name="locked" class="form-input">
                    <option value="">{{ __('academic.configuration.index.all_lock_states') }}</option>
                    <option value="locked" @selected($locked === 'locked')>{{ __('academic.common.locked') }}</option>
                    <option value="unlocked" @selected($locked === 'unlocked')>{{ __('academic.common.unlocked') }}</option>
                </select>

                @if ($canChooseSchool)
                    <select name="school_id" class="form-input xl:col-span-2">
                        <option value="">{{ __('academic.configuration.index.all_schools') }}</option>
                        @foreach ($schools as $school)
                            <option value="{{ $school->id }}" @selected($schoolId === $school->id)>{{ $school->name }}</option>
                        @endforeach
                    </select>
                @endif

                <x-ui.button :href="route('academic.configuration.index')" variant="ghost">
                    {{ __('core.common.actions.reset') }}
                </x-ui.button>
            </form>
        </x-ui.card>

        @if ($configurations->count() > 0)
            <x-ui.table>
                <thead>
                    <tr>
                        <th class="table-head-cell">{{ __('academic.configuration.index.table.year') }}</th>
                        <th class="table-head-cell">{{ __('academic.configuration.index.table.school') }}</th>
                        <th class="table-head-cell">{{ __('academic.configuration.index.table.period_type') }}</th>
                        <th class="table-head-cell">{{ __('academic.configuration.index.table.day_type') }}</th>
                        <th class="table-head-cell">{{ __('academic.configuration.index.table.status') }}</th>
                        <th class="table-head-cell text-right">{{ __('academic.configuration.index.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($configurations as $configuration)
                        <tr class="table-body-row">
                            <td class="table-body-cell">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $configuration->academicYear->name }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    <x-ui.badge tone="sky">{{ $configuration->scope_type->label() }}</x-ui.badge>
                                    @if ($configuration->scopeReferenceLabel())
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $configuration->scopeReferenceLabel() }}</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $configuration->periods_count }} {{ __('academic.configuration.index.periods_count_suffix') }}</p>
                            </td>
                            <td class="table-body-cell text-gray-500 dark:text-gray-400">
                                {{ $configuration->school->name }}
                            </td>
                            <td class="table-body-cell">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $configuration->period_type->label() }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $configuration->period_count }} {{ __('academic.configuration.index.periods_count_suffix') }}</p>
                            </td>
                            <td class="table-body-cell">
                                <x-ui.badge tone="sky">{{ $configuration->school_day_type->label() }}</x-ui.badge>
                            </td>
                            <td class="table-body-cell">
                                <div class="flex flex-wrap gap-2">
                                    <x-ui.badge :tone="$configuration->is_active ? 'emerald' : 'slate'">
                                        {{ $configuration->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                                    </x-ui.badge>
                                    <x-ui.badge :tone="$configuration->is_locked ? 'rose' : 'sky'">
                                        {{ $configuration->is_locked ? __('academic.common.locked') : __('academic.common.unlocked') }}
                                    </x-ui.badge>
                                </div>
                            </td>
                            <td class="table-body-cell">
                                <div class="flex justify-end gap-2">
                                    @can('update', $configuration)
                                        <x-ui.button :href="route('academic.configuration.edit', $configuration)" variant="ghost" size="sm">
                                            {{ __('core.common.actions.edit') }}
                                        </x-ui.button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <x-ui.pagination :paginator="$configurations" />
        @else
            <x-ui.empty-state :title="__('academic.configuration.index.empty.title')" :description="__('academic.configuration.index.empty.description')" icon="fa-sliders">
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
    </div>
</x-app-layout>
