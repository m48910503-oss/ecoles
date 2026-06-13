<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('people.students.index.title')" :description="__('people.students.index.description')">
            <x-slot:actions>
                @can('create', \App\Models\Student::class)
                    <x-ui.button :href="route('people.students.create')">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('people.students.index.create_action') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    @php
        $statusLabels = \App\Models\Student::statusOptions();
        $genderLabels = \App\Models\Student::genderOptions();
    @endphp

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('people.students.index.stats.total')" :value="$stats['total']" icon="fa-user-graduate" tone="sky" />
            <x-ui.stats-card :label="__('people.students.index.stats.active')" :value="$stats['active']" icon="fa-circle-check" tone="emerald" />
            <x-ui.stats-card :label="__('people.students.index.stats.without_parents')" :value="$stats['without_parents']" icon="fa-user-xmark" tone="rose" />
            <x-ui.stats-card :label="__('people.students.index.stats.suspended')" :value="$stats['suspended']" icon="fa-user-slash" tone="violet" />
        </section>

        <x-ui.card>
            <form method="GET" data-auto-submit class="grid gap-3 xl:grid-cols-[minmax(0,1.2fr)_180px_180px_180px_160px_160px_auto_auto]">
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="{{ __('people.students.index.search_placeholder') }}"
                    class="form-input"
                >

                <select name="cycle_id" class="form-input">
                    <option value="">{{ __('people.students.index.filters.all_cycles') }}</option>
                    @foreach ($cycles as $cycle)
                        <option value="{{ $cycle->id }}" @selected((string) $cycleId === (string) $cycle->id)>{{ $cycle->name }}</option>
                    @endforeach
                </select>

                <select name="level_id" class="form-input">
                    <option value="">{{ __('people.students.index.filters.all_levels') }}</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level->id }}" @selected((string) $levelId === (string) $level->id)>{{ $level->name }}</option>
                    @endforeach
                </select>

                <select name="academic_classroom_id" class="form-input">
                    <option value="">{{ __('people.students.index.filters.all_classes') }}</option>
                    @foreach ($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" @selected((string) $classroomId === (string) $classroom->id)>
                            {{ $classroom->name }} @if($classroom->level?->cycle) - {{ $classroom->level->cycle->name }} / {{ $classroom->level->name }} @endif
                        </option>
                    @endforeach
                </select>

                <select name="gender" class="form-input">
                    <option value="">{{ __('people.students.index.filters.all_genders') }}</option>
                    @foreach ($genderLabels as $value => $label)
                        <option value="{{ $value }}" @selected($gender === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="status" class="form-input">
                    <option value="">{{ __('people.students.index.filters.all_statuses') }}</option>
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <x-ui.button type="submit" variant="secondary">
                    <i class="fa-solid fa-filter text-xs"></i>
                    {{ __('core.common.actions.filter') }}
                </x-ui.button>

                <x-ui.button :href="route('people.students.index')" variant="ghost">
                    {{ __('core.common.actions.reset') }}
                </x-ui.button>
            </form>
        </x-ui.card>

        @if ($students->count() > 0)
            <x-ui.table>
                <thead>
                    <tr>
                        <th class="table-head-cell">{{ __('people.students.index.table.student') }}</th>
                        <th class="table-head-cell">{{ __('people.students.index.table.cycle') }}</th>
                        <th class="table-head-cell">{{ __('people.students.index.table.level') }}</th>
                        <th class="table-head-cell">{{ __('people.students.index.table.classroom') }}</th>
                        <th class="table-head-cell">{{ __('people.students.index.table.parents') }}</th>
                        <th class="table-head-cell">{{ __('people.students.index.table.status') }}</th>
                        <th class="table-head-cell text-right">{{ __('people.students.index.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($students as $student)
                        <tr class="table-body-row">
                            <td class="table-body-cell">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-2xl bg-sky-100 text-sm font-bold text-sky-700 dark:bg-sky-500/15 dark:text-sky-300">
                                        @if ($student->photo_path)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($student->photo_path) }}" alt="{{ $student->fullName() }}" class="h-full w-full object-cover">
                                        @else
                                            {{ $student->initials() }}
                                        @endif
                                    </div>

                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $student->fullName() }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $student->matricule }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $genderLabels[$student->gender] ?? $student->gender }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="table-body-cell text-gray-600 dark:text-gray-300">
                                {{ $student->currentClassroom?->level?->cycle?->name ?? '—' }}
                            </td>
                            <td class="table-body-cell text-gray-600 dark:text-gray-300">
                                {{ $student->currentClassroom?->level?->name ?? '—' }}
                            </td>
                            <td class="table-body-cell text-gray-600 dark:text-gray-300">
                                {{ $student->currentClassroom?->name ?? '—' }}
                            </td>
                            <td class="table-body-cell">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-ui.badge :tone="$student->parents_count > 0 ? 'emerald' : 'rose'">
                                        {{ $student->parents_count }}
                                    </x-ui.badge>

                                    @if ($student->parents_count === 0)
                                        <x-ui.badge tone="rose">{{ __('people.students.index.stats.without_parents') }}</x-ui.badge>
                                    @endif
                                </div>
                            </td>
                            <td class="table-body-cell">
                                <x-ui.badge :tone="\App\Models\Student::statusTone($student->status)">
                                    {{ $statusLabels[$student->status] ?? $student->status }}
                                </x-ui.badge>
                            </td>
                            <td class="table-body-cell text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    @can('view', $student)
                                        <x-ui.button :href="route('people.students.show', $student)" variant="ghost" size="sm">
                                            {{ __('people.students.index.actions.view') }}
                                        </x-ui.button>
                                    @endcan

                                    @can('update', $student)
                                        <x-ui.button :href="route('people.students.edit', $student)" variant="secondary" size="sm">
                                            {{ __('people.students.index.actions.edit') }}
                                        </x-ui.button>
                                    @endcan

                                    @can('linkParent', $student)
                                        <x-ui.button :href="route('people.students.show', $student).'#parents'" variant="info" size="sm">
                                            {{ __('people.students.index.actions.link_parent') }}
                                        </x-ui.button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        @else
            <x-ui.empty-state :title="__('people.students.index.empty.title')" :description="__('people.students.index.empty.description')" icon="fa-user-graduate">
                <x-slot:actions>
                    @can('create', \App\Models\Student::class)
                        <x-ui.button :href="route('people.students.create')">
                            <i class="fa-solid fa-plus text-xs"></i>
                            {{ __('people.students.index.create_action') }}
                        </x-ui.button>
                    @endcan
                </x-slot:actions>
            </x-ui.empty-state>
        @endif

        <x-ui.pagination :paginator="$students" />
    </div>
</x-app-layout>
