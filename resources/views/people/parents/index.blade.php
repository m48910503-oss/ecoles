<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('people.parents.index.title')" :description="__('people.parents.index.description')">
            <x-slot:actions>
                @can('create', \App\Models\Guardian::class)
                    <x-ui.button :href="route('people.parents.create')">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('people.parents.index.create_action') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    @php
        $genderLabels = \App\Models\Guardian::genderOptions();
        $statusLabels = \App\Models\Guardian::statusOptions();
        $accountLabels = [
            '' => __('people.parents.index.filters.all_accounts'),
            'linked' => __('people.parents.index.filters.linked_account'),
            'unlinked' => __('people.parents.index.filters.unlinked_account'),
        ];
        $childrenLabels = [
            '' => __('people.parents.index.filters.all_children'),
            'with' => __('people.parents.index.filters.with_children'),
            'without' => __('people.parents.index.filters.without_children'),
        ];
    @endphp

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('people.parents.index.stats.total')" :value="$stats['total']" icon="fa-people-roof" tone="sky" />
            <x-ui.stats-card :label="__('people.parents.index.stats.active')" :value="$stats['active']" icon="fa-circle-check" tone="emerald" />
            <x-ui.stats-card :label="__('people.parents.index.stats.linked_account')" :value="$stats['linked_account']" icon="fa-user-check" tone="amber" />
            <x-ui.stats-card :label="__('people.parents.index.stats.without_children')" :value="$stats['without_children']" icon="fa-user-xmark" tone="rose" />
        </section>

        <x-ui.card>
            <form method="GET" data-auto-submit class="grid gap-3 xl:grid-cols-[minmax(0,1.2fr)_180px_180px_180px_180px_auto_auto]">
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="{{ __('people.parents.index.search_placeholder') }}"
                    class="form-input"
                >

                <select name="gender" class="form-input">
                    <option value="">{{ __('people.parents.index.filters.all_genders') }}</option>
                    @foreach ($genderLabels as $value => $label)
                        <option value="{{ $value }}" @selected($gender === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="status" class="form-input">
                    <option value="">{{ __('people.parents.index.filters.all_statuses') }}</option>
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="account" class="form-input">
                    @foreach ($accountLabels as $value => $label)
                        <option value="{{ $value }}" @selected($account === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="children" class="form-input">
                    @foreach ($childrenLabels as $value => $label)
                        <option value="{{ $value }}" @selected($children === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <x-ui.button type="submit" variant="secondary">
                    <i class="fa-solid fa-filter text-xs"></i>
                    {{ __('core.common.actions.filter') }}
                </x-ui.button>

                <x-ui.button :href="route('people.parents.index')" variant="ghost">
                    {{ __('core.common.actions.reset') }}
                </x-ui.button>
            </form>
        </x-ui.card>

        @if ($parents->count() > 0)
            <x-ui.table>
                <thead>
                    <tr>
                        <th class="table-head-cell">{{ __('people.parents.index.table.parent') }}</th>
                        <th class="table-head-cell">{{ __('people.parents.index.table.contact') }}</th>
                        <th class="table-head-cell">{{ __('people.parents.index.table.children') }}</th>
                        <th class="table-head-cell">{{ __('people.parents.index.table.account') }}</th>
                        <th class="table-head-cell">{{ __('people.parents.index.table.status') }}</th>
                        <th class="table-head-cell text-right">{{ __('people.parents.index.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($parents as $parent)
                        <tr class="table-body-row">
                            <td class="table-body-cell">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-sm font-bold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                        {{ $parent->initials() }}
                                    </div>

                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $parent->fullName() }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $genderLabels[$parent->gender] ?? $parent->gender }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="table-body-cell">
                                <div class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                                    <p>{{ $parent->phone ?: '—' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $parent->email ?: '—' }}</p>
                                </div>
                            </td>
                            <td class="table-body-cell">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-ui.badge :tone="$parent->students_count > 0 ? 'emerald' : 'rose'">
                                        {{ $parent->students_count }}
                                    </x-ui.badge>

                                    @if ($parent->students_count === 0)
                                        <x-ui.badge tone="rose">{{ __('people.parents.index.filters.without_children') }}</x-ui.badge>
                                    @endif
                                </div>
                            </td>
                            <td class="table-body-cell">
                                @if ($parent->user)
                                    <div class="space-y-1">
                                        <x-ui.badge tone="{{ $parent->user->is_active ? 'emerald' : 'rose' }}">
                                            {{ $parent->user->is_active ? __('core.common.states.active') : __('core.common.states.suspended') }}
                                        </x-ui.badge>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $parent->user->email }}</p>
                                    </div>
                                @else
                                    <x-ui.badge tone="slate">{{ __('people.parents.show.no_account') }}</x-ui.badge>
                                @endif
                            </td>
                            <td class="table-body-cell">
                                <x-ui.badge :tone="\App\Models\Guardian::statusTone($parent->status)">
                                    {{ $statusLabels[$parent->status] ?? $parent->status }}
                                </x-ui.badge>
                            </td>
                            <td class="table-body-cell text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    @can('view', $parent)
                                        <x-ui.button :href="route('people.parents.show', $parent)" variant="ghost" size="sm">
                                            {{ __('people.parents.index.actions.view') }}
                                        </x-ui.button>
                                    @endcan

                                    @can('update', $parent)
                                        <x-ui.button :href="route('people.parents.edit', $parent)" variant="secondary" size="sm">
                                            {{ __('people.parents.index.actions.edit') }}
                                        </x-ui.button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        @else
            <x-ui.empty-state :title="__('people.parents.index.empty.title')" :description="__('people.parents.index.empty.description')" icon="fa-people-roof">
                <x-slot:actions>
                    @can('create', \App\Models\Guardian::class)
                        <x-ui.button :href="route('people.parents.create')">
                            <i class="fa-solid fa-plus text-xs"></i>
                            {{ __('people.parents.index.create_action') }}
                        </x-ui.button>
                    @endcan
                </x-slot:actions>
            </x-ui.empty-state>
        @endif

        <x-ui.pagination :paginator="$parents" />
    </div>
</x-app-layout>
