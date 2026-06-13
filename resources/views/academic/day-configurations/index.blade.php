<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('academic.day_config.index.title')" :description="__('academic.day_config.index.description')">
            <x-slot:actions>
                @can('create', \App\Models\SchoolDayConfiguration::class)
                    <x-ui.button :href="route('academic.day-configurations.create')">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('academic.day_config.index.create_action') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    @php
        $formatHours = static function ($configuration): string {
            if ($configuration->day_type?->value === 'continuous') {
                return trim(($configuration->start_time ?? '—').' - '.($configuration->end_time ?? '—'));
            }

            return trim(($configuration->morning_start_time ?? '—').' - '.($configuration->morning_end_time ?? '—').' / '.($configuration->afternoon_start_time ?? '—').' - '.($configuration->afternoon_end_time ?? '—'));
        };
    @endphp

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('academic.day_config.index.stats.total')" :value="$stats['total']" icon="fa-clock" tone="sky" />
            <x-ui.stats-card :label="__('academic.day_config.index.stats.continuous')" :value="$stats['continuous']" icon="fa-sun" tone="emerald" />
            <x-ui.stats-card :label="__('academic.day_config.index.stats.discontinuous')" :value="$stats['discontinuous']" icon="fa-cloud-sun" tone="amber" />
            <x-ui.stats-card :label="__('academic.day_config.index.stats.locked')" :value="$stats['locked']" icon="fa-lock" tone="rose" />
        </section>

        <x-ui.card>
            <form method="GET" data-auto-submit class="grid gap-3 xl:grid-cols-[220px_220px_220px_220px_220px_auto] xl:items-center">
                <select name="academic_year_id" class="form-input">
                    <option value="">{{ __('academic.day_config.index.all_years') }}</option>
                    @foreach ($academicYears as $academicYear)
                        <option value="{{ $academicYear->id }}" @selected($academicYearId === $academicYear->id)>{{ $academicYear->name }}</option>
                    @endforeach
                </select>

                <select name="scope_type" class="form-input">
                    <option value="">{{ __('academic.day_config.index.all_scopes') }}</option>
                    @foreach ($scopeOptions as $scopeOption)
                        <option value="{{ $scopeOption->value }}" @selected($scopeType === $scopeOption->value)>{{ $scopeOption->label() }}</option>
                    @endforeach
                </select>

                <select name="day_type" class="form-input">
                    <option value="">{{ __('academic.day_config.index.all_day_types') }}</option>
                    @foreach ($dayTypeOptions as $dayTypeOption)
                        <option value="{{ $dayTypeOption->value }}" @selected($dayType === $dayTypeOption->value)>{{ $dayTypeOption->label() }}</option>
                    @endforeach
                </select>

                <select name="status" class="form-input">
                    <option value="">{{ __('core.common.filters.all_statuses') }}</option>
                    <option value="active" @selected($status === 'active')>{{ __('core.common.states.active') }}</option>
                    <option value="inactive" @selected($status === 'inactive')>{{ __('core.common.states.inactive') }}</option>
                </select>

                @if ($canChooseSchool)
                    <select name="school_id" class="form-input xl:col-span-2">
                        <option value="">{{ __('academic.day_config.index.all_schools') }}</option>
                        @foreach ($schools as $school)
                            <option value="{{ $school->id }}" @selected($schoolId === $school->id)>{{ $school->name }}</option>
                        @endforeach
                    </select>
                @endif

                <x-ui.button :href="route('academic.day-configurations.index')" variant="ghost">
                    {{ __('core.common.actions.reset') }}
                </x-ui.button>
            </form>
        </x-ui.card>

        @if ($dayConfigurations->count() > 0)
            <x-ui.table>
                <thead>
                    <tr>
                        <th class="table-head-cell">{{ __('academic.day_config.index.table.scope') }}</th>
                        <th class="table-head-cell">{{ __('academic.day_config.index.table.year') }}</th>
                        <th class="table-head-cell">{{ __('academic.day_config.index.table.day_type') }}</th>
                        <th class="table-head-cell">{{ __('academic.day_config.index.table.hours') }}</th>
                        <th class="table-head-cell">{{ __('academic.day_config.index.table.status') }}</th>
                        <th class="table-head-cell text-right">{{ __('academic.day_config.index.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($dayConfigurations as $dayConfiguration)
                        <tr class="table-body-row">
                            <td class="table-body-cell">
                                <div class="flex flex-wrap gap-2">
                                    <x-ui.badge tone="sky">{{ $dayConfiguration->scope_type->label() }}</x-ui.badge>
                                    @if ($dayConfiguration->cycle)
                                        <x-ui.badge tone="violet">{{ $dayConfiguration->cycle->name }}</x-ui.badge>
                                    @endif
                                </div>
                                @if ($canChooseSchool)
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $dayConfiguration->school->name }}</p>
                                @endif
                            </td>
                            <td class="table-body-cell text-gray-500 dark:text-gray-400">
                                {{ $dayConfiguration->academicYear->name }}
                            </td>
                            <td class="table-body-cell">
                                <x-ui.badge tone="emerald">{{ $dayConfiguration->day_type->label() }}</x-ui.badge>
                            </td>
                            <td class="table-body-cell text-gray-500 dark:text-gray-400">
                                {{ $formatHours($dayConfiguration) }}
                            </td>
                            <td class="table-body-cell">
                                <div class="flex flex-wrap gap-2">
                                    <x-ui.badge :tone="$dayConfiguration->is_active ? 'emerald' : 'slate'">
                                        {{ $dayConfiguration->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                                    </x-ui.badge>
                                    <x-ui.badge :tone="$dayConfiguration->is_locked ? 'rose' : 'sky'">
                                        {{ $dayConfiguration->is_locked ? __('academic.common.locked') : __('academic.common.unlocked') }}
                                    </x-ui.badge>
                                </div>
                            </td>
                            <td class="table-body-cell">
                                <div class="flex justify-end gap-2">
                                    <x-ui.button :href="route('academic.day-configurations.show', $dayConfiguration)" variant="ghost" size="sm">
                                        {{ __('core.common.actions.view') }}
                                    </x-ui.button>
                                    @can('update', $dayConfiguration)
                                        <x-ui.button :href="route('academic.day-configurations.edit', $dayConfiguration)" variant="ghost" size="sm">
                                            {{ __('core.common.actions.edit') }}
                                        </x-ui.button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <x-ui.pagination :paginator="$dayConfigurations" />
        @else
            <x-ui.empty-state :title="__('academic.day_config.index.empty.title')" :description="__('academic.day_config.index.empty.description')" icon="fa-clock">
                <x-slot:actions>
                    @can('create', \App\Models\SchoolDayConfiguration::class)
                        <x-ui.button :href="route('academic.day-configurations.create')">
                            <i class="fa-solid fa-plus text-xs"></i>
                            {{ __('academic.day_config.index.create_action') }}
                        </x-ui.button>
                    @endcan
                </x-slot:actions>
            </x-ui.empty-state>
        @endif
    </div>
</x-app-layout>
