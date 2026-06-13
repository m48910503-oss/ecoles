<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('academic.coefficients.index.title')" :description="__('academic.coefficients.index.description')">
            <x-slot:actions>
                @can('create', \App\Models\AcademicCoefficient::class)
                    <x-ui.button :href="route('academic.coefficients.create')">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('academic.coefficients.index.create_action') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('academic.coefficients.index.stats.total')" :value="$stats['total']" icon="fa-square-root-variable" tone="sky" />
            <x-ui.stats-card :label="__('academic.coefficients.index.stats.active')" :value="$stats['active']" icon="fa-circle-check" tone="emerald" />
            <x-ui.stats-card :label="__('academic.coefficients.index.stats.optional')" :value="$stats['optional']" icon="fa-toggle-on" tone="amber" />
            <x-ui.stats-card :label="__('academic.coefficients.index.stats.conduct')" :value="$stats['conduct']" icon="fa-scale-balanced" tone="violet" />
        </section>

        <x-ui.card>
            <form method="GET" data-auto-submit class="grid gap-3 xl:grid-cols-[220px_220px_220px_220px_220px_220px_auto] xl:items-center">
                @if ($canChooseSchool)
                    <select name="school_id" class="form-input">
                        <option value="">{{ __('core.common.filters.all_schools') }}</option>
                        @foreach ($schools as $school)
                            <option value="{{ $school->id }}" @selected($schoolId === $school->id)>{{ $school->name }}</option>
                        @endforeach
                    </select>
                @endif

                <select name="academic_year_id" class="form-input">
                    <option value="">{{ __('academic.coefficients.index.all_years') }}</option>
                    @foreach ($academicYears as $academicYear)
                        <option value="{{ $academicYear->id }}" @selected($academicYearId === $academicYear->id)>{{ $academicYear->name }}</option>
                    @endforeach
                </select>

                <select name="level_id" class="form-input">
                    <option value="">{{ __('academic.coefficients.index.all_levels') }}</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level->id }}" @selected($levelId === $level->id)>{{ $level->name }}</option>
                    @endforeach
                </select>

                <select name="series_id" class="form-input">
                    <option value="">{{ __('academic.coefficients.index.all_series') }}</option>
                    @foreach ($series as $serie)
                        <option value="{{ $serie->id }}" @selected($seriesId === $serie->id)>{{ $serie->name }}</option>
                    @endforeach
                </select>

                <select name="academic_subject_id" class="form-input">
                    <option value="">{{ __('academic.coefficients.index.all_subjects') }}</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected($subjectId === $subject->id)>{{ $subject->name }}</option>
                    @endforeach
                </select>

                <select name="status" class="form-input">
                    <option value="">{{ __('core.common.filters.all_statuses') }}</option>
                    <option value="active" @selected($status === 'active')>{{ __('core.common.states.active') }}</option>
                    <option value="inactive" @selected($status === 'inactive')>{{ __('core.common.states.inactive') }}</option>
                </select>

                <x-ui.button :href="route('academic.coefficients.index')" variant="ghost">
                    {{ __('core.common.actions.reset') }}
                </x-ui.button>
            </form>
        </x-ui.card>

        @if ($coefficients->count() > 0)
            <x-ui.table>
                <thead>
                    <tr>
                        <th class="table-head-cell">{{ __('academic.coefficients.index.table.subject') }}</th>
                        <th class="table-head-cell">{{ __('academic.coefficients.index.table.target') }}</th>
                        <th class="table-head-cell">{{ __('academic.coefficients.index.table.coefficient') }}</th>
                        <th class="table-head-cell">{{ __('academic.coefficients.index.table.rules') }}</th>
                        <th class="table-head-cell">{{ __('academic.coefficients.index.table.status') }}</th>
                        <th class="table-head-cell text-right">{{ __('academic.coefficients.index.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($coefficients as $coefficient)
                        <tr class="table-body-row">
                            <td class="table-body-cell">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $coefficient->subject->name }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $coefficient->subject->code }}</p>
                            </td>
                            <td class="table-body-cell">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $coefficient->academicYear->name }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $coefficient->level->name }}@if($coefficient->series) - {{ $coefficient->series->short_name ?: $coefficient->series->name }}@endif</p>
                            </td>
                            <td class="table-body-cell">
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ number_format((float) $coefficient->coefficient, 2, ',', ' ') }}</p>
                            </td>
                            <td class="table-body-cell">
                                <div class="flex flex-wrap gap-2">
                                    <x-ui.badge :tone="$coefficient->is_optional ? 'amber' : 'slate'">
                                        {{ $coefficient->is_optional ? __('academic.common.yes') : __('academic.common.no') }}
                                    </x-ui.badge>
                                    @if ($coefficient->subject->is_conduct)
                                        <x-ui.badge tone="violet">{{ __('academic.coefficients.index.stats.conduct') }}</x-ui.badge>
                                    @endif
                                </div>
                            </td>
                            <td class="table-body-cell">
                                <x-ui.badge :tone="$coefficient->is_active ? 'emerald' : 'slate'">
                                    {{ $coefficient->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                                </x-ui.badge>
                            </td>
                            <td class="table-body-cell">
                                <div class="flex justify-end gap-2">
                                    <x-ui.button :href="route('academic.coefficients.show', $coefficient)" variant="info" size="sm">
                                        {{ __('core.common.actions.view') }}
                                    </x-ui.button>
                                    @can('update', $coefficient)
                                        <x-ui.button :href="route('academic.coefficients.edit', $coefficient)" variant="warning" size="sm">
                                            {{ __('core.common.actions.edit') }}
                                        </x-ui.button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <x-ui.pagination :paginator="$coefficients" />
        @else
            <x-ui.empty-state :title="__('academic.coefficients.index.empty.title')" :description="__('academic.coefficients.index.empty.description')" icon="fa-square-root-variable">
                <x-slot:actions>
                    @can('create', \App\Models\AcademicCoefficient::class)
                        <x-ui.button :href="route('academic.coefficients.create')">
                            <i class="fa-solid fa-plus text-xs"></i>
                            {{ __('academic.coefficients.index.create_action') }}
                        </x-ui.button>
                    @endcan
                </x-slot:actions>
            </x-ui.empty-state>
        @endif
    </div>
</x-app-layout>
