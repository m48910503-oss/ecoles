<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('core.academic_years.index.title')" :description="__('core.academic_years.index.description')">
            <x-slot:actions>
                @can('create', \App\Models\AcademicYear::class)
                    <x-ui.button :href="route('core.academic-years.create')">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('core.academic_years.index.create_action') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('core.academic_years.index.stats.total')" :value="$stats['total']" icon="fa-calendar-days" tone="sky" />
            <x-ui.stats-card :label="__('core.academic_years.index.stats.active')" :value="$stats['active']" icon="fa-calendar-check" tone="emerald" />
            <x-ui.stats-card :label="__('core.academic_years.index.stats.open')" :value="$stats['open']" icon="fa-door-open" tone="amber" />
            <x-ui.stats-card :label="__('core.academic_years.index.stats.closed')" :value="$stats['closed']" icon="fa-lock" tone="rose" />
        </section>

        <x-ui.card>
            <form method="GET" data-auto-submit class="grid gap-3 xl:grid-cols-[minmax(0,1.2fr)_220px_220px_auto_auto]">
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('core.academic_years.index.search_placeholder') }}" class="form-input">

                <select name="status" class="form-input">
                    <option value="">{{ __('core.common.filters.all_statuses') }}</option>
                    <option value="active" @selected($status === 'active')>{{ __('core.common.states.active') }}</option>
                    <option value="inactive" @selected($status === 'inactive')>{{ __('core.common.states.inactive') }}</option>
                </select>

                <select name="enrollment" class="form-input">
                    <option value="">{{ __('core.academic_years.index.all_enrollment_states') }}</option>
                    <option value="open" @selected($enrollment === 'open')>{{ __('core.common.states.open') }}</option>
                    <option value="closed" @selected($enrollment === 'closed')>{{ __('core.common.states.closed') }}</option>
                </select>

                <x-ui.button type="submit" variant="secondary">
                    <i class="fa-solid fa-filter text-xs"></i>
                    {{ __('core.common.actions.filter') }}
                </x-ui.button>

                <x-ui.button :href="route('core.academic-years.index')" variant="ghost">
                    {{ __('core.common.actions.reset') }}
                </x-ui.button>
            </form>
        </x-ui.card>

        @if ($academicYears->count() > 0)
            <x-ui.table>
                <thead>
                    <tr>
                        <th class="table-head-cell">{{ __('core.academic_years.index.table.year') }}</th>
                        <th class="table-head-cell">{{ __('core.academic_years.index.table.period') }}</th>
                        <th class="table-head-cell">{{ __('core.academic_years.index.table.status') }}</th>
                        <th class="table-head-cell">{{ __('core.academic_years.index.table.enrollment') }}</th>
                        <th class="table-head-cell text-right">{{ __('core.academic_years.index.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($academicYears as $academicYear)
                        <tr class="table-body-row">
                            <td class="table-body-cell">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $academicYear->name }}</p>
                                <div class="mt-2">
                                    <x-ui.badge :tone="$academicYear->multi_school_enabled ? 'sky' : 'slate'">
                                        {{ $academicYear->multi_school_enabled ? __('core.settings.organization.modes.multi') : __('core.settings.organization.modes.mono') }}
                                    </x-ui.badge>
                                </div>
                            </td>
                            <td class="table-body-cell text-gray-500 dark:text-gray-400">
                                {{ $academicYear->start_date?->translatedFormat('d M Y') }} - {{ $academicYear->end_date?->translatedFormat('d M Y') }}
                            </td>
                            <td class="table-body-cell">
                                <x-ui.badge :tone="$academicYear->is_active ? 'emerald' : 'slate'">
                                    {{ $academicYear->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                                </x-ui.badge>
                            </td>
                            <td class="table-body-cell">
                                <x-ui.badge :tone="$academicYear->enrollment_open ? 'sky' : 'amber'">
                                    {{ $academicYear->enrollment_open ? __('core.common.states.open') : __('core.common.states.closed') }}
                                </x-ui.badge>
                            </td>
                            <td class="table-body-cell text-right">
                                <div class="flex justify-end gap-2">
                                    @can('view', $academicYear)
                                        <x-ui.button
                                            :href="route('core.academic-years.show', $academicYear)"
                                            variant="ghost"
                                            size="sm"
                                        >
                                            {{ __('core.academic_years.index.configure_action') }}
                                        </x-ui.button>
                                    @endcan

                                    @can('update', $academicYear)
                                        <x-ui.button :href="route('core.academic-years.edit', $academicYear)" variant="ghost" size="sm">
                                            {{ __('core.common.actions.edit') }}
                                        </x-ui.button>
                                    @endcan

                                    @can('delete', $academicYear)
                                        <x-ui.confirm-modal
                                            :name="'delete-academic-year-'.$academicYear->id"
                                            :action="route('core.academic-years.destroy', $academicYear)"
                                            :description="__('core.academic_years.index.delete_confirm')"
                                            :warning="$academicYear->is_active ? __('core.academic_years.delete_active_warning') : null"
                                            :trigger-label="__('core.common.actions.delete')"
                                        />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        @else
            <x-ui.empty-state :title="__('core.academic_years.index.empty.title')" :description="__('core.academic_years.index.empty.description')" icon="fa-calendar-days">
                <x-slot:actions>
                    @can('create', \App\Models\AcademicYear::class)
                        <x-ui.button :href="route('core.academic-years.create')">
                            <i class="fa-solid fa-plus text-xs"></i>
                            {{ __('core.academic_years.index.create_action') }}
                        </x-ui.button>
                    @endcan
                </x-slot:actions>
            </x-ui.empty-state>
        @endif

        <x-ui.pagination :paginator="$academicYears" />
    </div>
</x-app-layout>
