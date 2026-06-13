<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('academic.subjects.index.title')" :description="__('academic.subjects.index.description')">
            <x-slot:actions>
                @can('create', \App\Models\AcademicSubject::class)
                    <x-ui.button :href="route('academic.subjects.create')">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('academic.subjects.index.create_action') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('academic.subjects.index.stats.total')" :value="$stats['total']" icon="fa-book-open" tone="sky" />
            <x-ui.stats-card :label="__('academic.subjects.index.stats.active')" :value="$stats['active']" icon="fa-circle-check" tone="emerald" />
            <x-ui.stats-card :label="__('academic.subjects.index.stats.optional')" :value="$stats['optional']" icon="fa-toggle-on" tone="amber" />
            <x-ui.stats-card :label="__('academic.subjects.index.stats.conduct')" :value="$stats['conduct']" icon="fa-scale-balanced" tone="violet" />
        </section>

        <x-ui.card>
            <form method="GET" data-auto-submit class="grid gap-3 xl:grid-cols-[minmax(0,1.2fr)_220px_220px_220px_220px_220px_auto] xl:items-center">
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('academic.subjects.index.search_placeholder') }}" class="form-input">

                @if ($canChooseSchool)
                    <select name="school_id" class="form-input">
                        <option value="">{{ __('core.common.filters.all_schools') }}</option>
                        @foreach ($schools as $school)
                            <option value="{{ $school->id }}" @selected($schoolId === $school->id)>{{ $school->name }}</option>
                        @endforeach
                    </select>
                @endif

                <select name="category" class="form-input">
                    <option value="">{{ __('academic.subjects.index.all_categories') }}</option>
                    @foreach ($categoryOptions as $categoryOption)
                        <option value="{{ $categoryOption->value }}" @selected($category === $categoryOption->value)>{{ $categoryOption->label() }}</option>
                    @endforeach
                </select>

                <select name="optional" class="form-input">
                    <option value="">{{ __('academic.subjects.index.all_optional_states') }}</option>
                    <option value="yes" @selected($optional === 'yes')>{{ __('academic.common.yes') }}</option>
                    <option value="no" @selected($optional === 'no')>{{ __('academic.common.no') }}</option>
                </select>

                <select name="conduct" class="form-input">
                    <option value="">{{ __('academic.subjects.index.all_conduct_states') }}</option>
                    <option value="yes" @selected($conduct === 'yes')>{{ __('academic.common.yes') }}</option>
                    <option value="no" @selected($conduct === 'no')>{{ __('academic.common.no') }}</option>
                </select>

                <select name="status" class="form-input">
                    <option value="">{{ __('core.common.filters.all_statuses') }}</option>
                    <option value="active" @selected($status === 'active')>{{ __('core.common.states.active') }}</option>
                    <option value="inactive" @selected($status === 'inactive')>{{ __('core.common.states.inactive') }}</option>
                </select>

                <x-ui.button :href="route('academic.subjects.index')" variant="ghost">
                    {{ __('core.common.actions.reset') }}
                </x-ui.button>
            </form>
        </x-ui.card>

        @if ($subjects->count() > 0)
            <x-ui.table>
                <thead>
                    <tr>
                        <th class="table-head-cell">{{ __('academic.subjects.index.table.subject') }}</th>
                        <th class="table-head-cell">{{ __('academic.subjects.index.table.category') }}</th>
                        <th class="table-head-cell">{{ __('academic.subjects.index.table.rules') }}</th>
                        <th class="table-head-cell">{{ __('academic.subjects.index.table.status') }}</th>
                        <th class="table-head-cell text-right">{{ __('academic.subjects.index.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($subjects as $subject)
                        <tr class="table-body-row">
                            <td class="table-body-cell">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $subject->name }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $subject->short_name ?: $subject->code }}</p>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $subject->school->name }}</p>
                            </td>
                            <td class="table-body-cell">
                                <x-ui.badge tone="sky">{{ $subject->category?->label() ?? '—' }}</x-ui.badge>
                            </td>
                            <td class="table-body-cell">
                                <div class="flex flex-wrap gap-2">
                                    <x-ui.badge :tone="$subject->is_optional_by_default ? 'amber' : 'slate'">
                                        {{ $subject->is_optional_by_default ? __('academic.common.yes') : __('academic.common.no') }}
                                    </x-ui.badge>
                                    @if ($subject->is_conduct)
                                        <x-ui.badge tone="violet">{{ __('academic.subjects.index.stats.conduct') }}</x-ui.badge>
                                    @endif
                                </div>
                            </td>
                            <td class="table-body-cell">
                                <div class="flex flex-wrap gap-2">
                                    <x-ui.badge :tone="$subject->is_active ? 'emerald' : 'slate'">
                                        {{ $subject->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                                    </x-ui.badge>
                                    @if ($subject->coefficients_count > 0)
                                        <x-ui.badge tone="amber">{{ $subject->coefficients_count }} {{ __('academic.subjects.show.coefficients') }}</x-ui.badge>
                                    @endif
                                    @if ($subject->conduct_settings_count > 0)
                                        <x-ui.badge tone="violet">{{ $subject->conduct_settings_count }} {{ __('academic.subjects.show.conduct_settings') }}</x-ui.badge>
                                    @endif
                                </div>
                            </td>
                            <td class="table-body-cell">
                                <div class="flex justify-end gap-2">
                                    <x-ui.button :href="route('academic.subjects.show', $subject)" variant="info" size="sm">
                                        {{ __('core.common.actions.view') }}
                                    </x-ui.button>
                                    @can('update', $subject)
                                        <x-ui.button :href="route('academic.subjects.edit', $subject)" variant="warning" size="sm">
                                            {{ __('core.common.actions.edit') }}
                                        </x-ui.button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>

            <x-ui.pagination :paginator="$subjects" />
        @else
            <x-ui.empty-state :title="__('academic.subjects.index.empty.title')" :description="__('academic.subjects.index.empty.description')" icon="fa-book-open">
                <x-slot:actions>
                    @can('create', \App\Models\AcademicSubject::class)
                        <x-ui.button :href="route('academic.subjects.create')">
                            <i class="fa-solid fa-plus text-xs"></i>
                            {{ __('academic.subjects.index.create_action') }}
                        </x-ui.button>
                    @endcan
                </x-slot:actions>
            </x-ui.empty-state>
        @endif
    </div>
</x-app-layout>
