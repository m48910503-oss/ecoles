<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('academic.conduct.index.title')" :description="__('academic.conduct.index.description')">
            <x-slot:actions>
                @can('create', \App\Models\AcademicConductSetting::class)
                    <x-ui.button :href="route('academic.conduct.create')">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('academic.conduct.index.create_action') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('academic.conduct.index.stats.total')" :value="$stats['total']" icon="fa-scale-balanced" tone="sky" />
            <x-ui.stats-card :label="__('academic.conduct.index.stats.active')" :value="$stats['active']" icon="fa-circle-check" tone="emerald" />
            <x-ui.stats-card :label="__('academic.conduct.index.stats.sanctions_only')" :value="$stats['sanctions_only']" icon="fa-gavel" tone="amber" />
            <x-ui.stats-card :label="__('academic.conduct.index.stats.average_base_score')" :value="$stats['average_base_score']" icon="fa-hashtag" tone="violet" />
        </section>

        <x-ui.card>
            <form method="GET" data-auto-submit class="grid gap-3 xl:grid-cols-[220px_220px_220px_220px_auto] xl:items-center">
                @if ($canChooseSchool)
                    <select name="school_id" class="form-input">
                        <option value="">{{ __('core.common.filters.all_schools') }}</option>
                        @foreach ($schools as $school)
                            <option value="{{ $school->id }}" @selected($schoolId === $school->id)>{{ $school->name }}</option>
                        @endforeach
                    </select>
                @endif

                <select name="academic_year_id" class="form-input">
                    <option value="">{{ __('academic.conduct.index.all_years') }}</option>
                    @foreach ($academicYears as $academicYear)
                        <option value="{{ $academicYear->id }}" @selected($academicYearId === $academicYear->id)>{{ $academicYear->name }}</option>
                    @endforeach
                </select>

                <select name="level_id" class="form-input">
                    <option value="">{{ __('academic.conduct.index.all_levels') }}</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level->id }}" @selected($levelId === $level->id)>{{ $level->name }}</option>
                    @endforeach
                </select>

                <select name="status" class="form-input">
                    <option value="">{{ __('core.common.filters.all_statuses') }}</option>
                    <option value="active" @selected($status === 'active')>{{ __('core.common.states.active') }}</option>
                    <option value="inactive" @selected($status === 'inactive')>{{ __('core.common.states.inactive') }}</option>
                </select>

                <x-ui.button :href="route('academic.conduct.index')" variant="ghost">
                    {{ __('core.common.actions.reset') }}
                </x-ui.button>
            </form>
        </x-ui.card>

        @if ($conductSettings->count() > 0)
            <x-ui.table>
                <thead>
                    <tr>
                        <th class="table-head-cell">{{ __('academic.conduct.index.table.level') }}</th>
                        <th class="table-head-cell">{{ __('academic.conduct.index.table.year') }}</th>
                        <th class="table-head-cell">{{ __('academic.conduct.index.table.subject') }}</th>
                        <th class="table-head-cell">{{ __('academic.conduct.index.table.base_score') }}</th>
                        <th class="table-head-cell">{{ __('academic.conduct.index.table.status') }}</th>
                        <th class="table-head-cell text-right">{{ __('academic.conduct.index.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($conductSettings as $conductSetting)
                        <tr class="table-body-row">
                            <td class="table-body-cell">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $conductSetting->level->name }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $conductSetting->level->cycle->name }}</p>
                            </td>
                            <td class="table-body-cell">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $conductSetting->academicYear->name }}</p>
                            </td>
                            <td class="table-body-cell">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $conductSetting->subject->name }}</p>
                            </td>
                            <td class="table-body-cell">
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ number_format((float) $conductSetting->base_score, 2, ',', ' ') }}</p>
                                @if ($conductSetting->deductions_from_sanctions_only)
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('academic.conduct.form.deductions_only_label') }}</p>
                                @endif
                            </td>
                            <td class="table-body-cell">
                                <x-ui.badge :tone="$conductSetting->is_active ? 'emerald' : 'slate'">
                                    {{ $conductSetting->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                                </x-ui.badge>
                            </td>
                            <td class="table-body-cell">
                                <div class="flex justify-end gap-2">
                                    <x-ui.button :href="route('academic.conduct.show', $conductSetting)" variant="ghost" size="sm">
                                        {{ __('core.common.actions.view') }}
                                    </x-ui.button>
                                    @can('update', $conductSetting)
                                        <x-ui.button :href="route('academic.conduct.edit', $conductSetting)" variant="ghost" size="sm">
                                            {{ __('core.common.actions.edit') }}
                                        </x-ui.button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <x-ui.pagination :paginator="$conductSettings" />
        @else
            <x-ui.empty-state :title="__('academic.conduct.index.empty.title')" :description="__('academic.conduct.index.empty.description')" icon="fa-scale-balanced">
                <x-slot:actions>
                    @can('create', \App\Models\AcademicConductSetting::class)
                        <x-ui.button :href="route('academic.conduct.create')">
                            <i class="fa-solid fa-plus text-xs"></i>
                            {{ __('academic.conduct.index.create_action') }}
                        </x-ui.button>
                    @endcan
                </x-slot:actions>
            </x-ui.empty-state>
        @endif
    </div>
</x-app-layout>
