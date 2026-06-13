<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('academic.classes.index.title')" :description="__('academic.classes.index.description')">
            <x-slot:actions>
                @can('create', \App\Models\AcademicClassroom::class)
                    <x-ui.button :href="route('academic.classes.create')">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('academic.classes.index.create_action') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('academic.classes.index.stats.total')" :value="$stats['total']" icon="fa-chalkboard" tone="sky" />
            <x-ui.stats-card :label="__('academic.classes.index.stats.active')" :value="$stats['active']" icon="fa-circle-check" tone="emerald" />
            <x-ui.stats-card :label="__('academic.classes.index.stats.with_series')" :value="$stats['with_series']" icon="fa-sitemap" tone="violet" />
            <x-ui.stats-card :label="__('academic.classes.index.stats.referenced')" :value="$stats['referenced']" icon="fa-calendar-week" tone="amber" />
        </section>

        <x-ui.card>
            <form method="GET" data-auto-submit class="grid gap-3 xl:grid-cols-[minmax(0,1.2fr)_220px_220px_220px_220px_auto] xl:items-center">
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('academic.classes.index.search_placeholder') }}" class="form-input">

                @if ($canChooseSchool)
                    <select name="school_id" class="form-input">
                        <option value="">{{ __('core.common.filters.all_schools') }}</option>
                        @foreach ($schools as $school)
                            <option value="{{ $school->id }}" @selected($schoolId === $school->id)>{{ $school->name }}</option>
                        @endforeach
                    </select>
                @endif

                <select name="academic_year_id" class="form-input">
                    <option value="">{{ __('academic.classes.index.all_years') }}</option>
                    @foreach ($academicYears as $academicYear)
                        <option value="{{ $academicYear->id }}" @selected($academicYearId === $academicYear->id)>{{ $academicYear->name }}</option>
                    @endforeach
                </select>

                <select name="level_id" class="form-input">
                    <option value="">{{ __('academic.classes.index.all_levels') }}</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level->id }}" @selected($levelId === $level->id)>{{ $level->name }}</option>
                    @endforeach
                </select>

                <select name="series_id" class="form-input">
                    <option value="">{{ __('academic.classes.index.all_series') }}</option>
                    @foreach ($series as $serie)
                        <option value="{{ $serie->id }}" @selected($seriesId === $serie->id)>{{ $serie->name }}</option>
                    @endforeach
                </select>

                <select name="status" class="form-input">
                    <option value="">{{ __('core.common.filters.all_statuses') }}</option>
                    <option value="active" @selected($status === 'active')>{{ __('core.common.states.active') }}</option>
                    <option value="inactive" @selected($status === 'inactive')>{{ __('core.common.states.inactive') }}</option>
                </select>

                <x-ui.button :href="route('academic.classes.index')" variant="ghost">
                    {{ __('core.common.actions.reset') }}
                </x-ui.button>
            </form>
        </x-ui.card>

        @if ($classrooms->count() > 0)
            <x-ui.table>
                <thead>
                    <tr>
                        <th class="table-head-cell">{{ __('academic.classes.index.table.classroom') }}</th>
                        <th class="table-head-cell">{{ __('academic.classes.index.table.year') }}</th>
                        <th class="table-head-cell">{{ __('academic.classes.index.table.structure') }}</th>
                        <th class="table-head-cell">{{ __('academic.classes.index.table.capacity') }}</th>
                        <th class="table-head-cell">{{ __('academic.classes.index.table.status') }}</th>
                        <th class="table-head-cell text-right">{{ __('academic.classes.index.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($classrooms as $classroom)
                        <tr class="table-body-row">
                            <td class="table-body-cell">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $classroom->name }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $classroom->code }}</p>
                                @if ($classroom->description)
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $classroom->description }}</p>
                                @endif
                            </td>
                            <td class="table-body-cell">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $classroom->academicYear->name }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $classroom->school->name }}</p>
                            </td>
                            <td class="table-body-cell">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $classroom->level->name }}</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <x-ui.badge tone="sky">{{ $classroom->level->cycle->name }}</x-ui.badge>
                                    @if ($classroom->series)
                                        <x-ui.badge tone="violet">{{ $classroom->series->short_name ?: $classroom->series->name }}</x-ui.badge>
                                    @endif
                                </div>
                            </td>
                            <td class="table-body-cell">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $classroom->capacity ?: '—' }}</p>
                            </td>
                            <td class="table-body-cell">
                                <div class="flex flex-wrap gap-2">
                                    <x-ui.badge :tone="$classroom->is_active ? 'emerald' : 'slate'">
                                        {{ $classroom->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                                    </x-ui.badge>
                                    @if ($classroom->calendars_count > 0)
                                        <x-ui.badge tone="amber">{{ $classroom->calendars_count }} {{ __('academic.classes.show.calendars') }}</x-ui.badge>
                                    @endif
                                </div>
                            </td>
                            <td class="table-body-cell">
                                <div class="flex justify-end gap-2">
                                    <x-ui.button :href="route('academic.classes.show', $classroom)" variant="info" size="sm">
                                        {{ __('core.common.actions.view') }}
                                    </x-ui.button>
                                    @can('update', $classroom)
                                        <x-ui.button :href="route('academic.classes.edit', $classroom)" variant="warning" size="sm">
                                            {{ __('core.common.actions.edit') }}
                                        </x-ui.button>
                                    @endcan
                                    @can('delete', $classroom)
                                        <x-ui.confirm-modal
                                            :name="'delete-classroom-'.$classroom->id"
                                            :action="route('academic.classes.destroy', $classroom)"
                                            :description="__('academic.classes.index.delete_confirm')"
                                        />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <x-ui.pagination :paginator="$classrooms" />
        @else
            <x-ui.empty-state :title="__('academic.classes.index.empty.title')" :description="__('academic.classes.index.empty.description')" icon="fa-chalkboard">
                <x-slot:actions>
                    @can('create', \App\Models\AcademicClassroom::class)
                        <x-ui.button :href="route('academic.classes.create')">
                            <i class="fa-solid fa-plus text-xs"></i>
                            {{ __('academic.classes.index.create_action') }}
                        </x-ui.button>
                    @endcan
                </x-slot:actions>
            </x-ui.empty-state>
        @endif
    </div>
</x-app-layout>
