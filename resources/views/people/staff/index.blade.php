<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('people.staff.index.title')" :description="__('people.staff.index.description')">
            <x-slot:actions>
                @can('create', \App\Models\Staff::class)
                    <x-ui.button :href="route('people.staff.create')">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('people.staff.index.create_action') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('people.staff.index.stats.total')" :value="$stats['total']" icon="fa-people-carry-box" tone="sky" />
            <x-ui.stats-card :label="__('people.staff.index.stats.active')" :value="$stats['active']" icon="fa-circle-check" tone="emerald" />
            <x-ui.stats-card :label="__('people.staff.index.stats.linked_account')" :value="$stats['linked_account']" icon="fa-address-card" tone="amber" />
            <x-ui.stats-card :label="__('people.staff.index.stats.without_account')" :value="$stats['without_account']" icon="fa-triangle-exclamation" tone="rose" />
        </section>

        <x-ui.card class="space-y-6">
            <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.staff.index.title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.staff.index.search_placeholder') }}</p>
                </div>

                <x-ui.button :href="route('people.staff.index')" variant="secondary">
                    {{ __('core.common.actions.reset_filters') }}
                </x-ui.button>
            </div>

            <form method="GET" action="{{ route('people.staff.index') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-form.input name="search" type="search" :label="__('people.staff.index.search_label')" :value="$search" icon="fa-magnifying-glass" :placeholder="__('people.staff.index.search_placeholder')" />

                <x-form.select name="status" :label="__('people.staff.index.filters.status')" icon="fa-toggle-on" :value="$status" :placeholder="__('people.staff.index.filters.all_statuses')">
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </x-form.select>

                <x-form.select name="contract" :label="__('people.staff.index.filters.contract')" icon="fa-file-contract" :value="$contract" :placeholder="__('people.staff.index.filters.all_contracts')">
                    @foreach ($contractOptions as $value => $label)
                        <option value="{{ $value }}" @selected($contract === $value)>{{ $label }}</option>
                    @endforeach
                </x-form.select>

                <x-form.select name="account" :label="__('people.staff.index.filters.account')" icon="fa-address-card" :value="$account" :placeholder="__('people.staff.index.filters.all_accounts')">
                    <option value="linked" @selected($account === 'linked')>{{ __('people.staff.index.filters.linked_account') }}</option>
                    <option value="unlinked" @selected($account === 'unlinked')>{{ __('people.staff.index.filters.unlinked_account') }}</option>
                </x-form.select>

                <div class="flex flex-wrap gap-3 md:col-span-2 xl:col-span-4">
                    <x-ui.button type="submit">
                        <i class="fa-solid fa-filter text-xs"></i>
                        {{ __('core.common.actions.apply_filters') }}
                    </x-ui.button>

                    <x-ui.button :href="route('people.staff.index')" variant="secondary">
                        {{ __('core.common.actions.reset_filters') }}
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card class="space-y-4">
            @if ($staffMembers->count() > 0)
                <x-ui.table>
                    <thead>
                        <tr>
                            <th class="table-head-cell">{{ __('people.staff.index.table.staff') }}</th>
                            <th class="table-head-cell">{{ __('people.staff.index.table.contact') }}</th>
                            <th class="table-head-cell">{{ __('people.staff.index.table.position') }}</th>
                            <th class="table-head-cell">{{ __('people.staff.index.table.system_role') }}</th>
                            <th class="table-head-cell">{{ __('people.staff.index.table.contract') }}</th>
                            <th class="table-head-cell">{{ __('people.staff.index.table.status') }}</th>
                            <th class="table-head-cell">{{ __('people.staff.index.table.account') }}</th>
                            <th class="table-head-cell text-right">{{ __('people.staff.index.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($staffMembers as $staff)
                            @php
                                $statusLabels = \App\Models\Staff::statusOptions();
                                $contractLabels = \App\Models\Staff::contractOptions();
                                $positionLabels = \App\Models\Staff::positionOptions();
                                $systemRoleLabels = \App\Models\Staff::systemRoleOptions();
                            @endphp
                            <tr class="table-body-row">
                                <td class="table-body-cell">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $staff->fullName() }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $staff->matricule }}</p>
                                    </div>
                                </td>
                                <td class="table-body-cell">
                                    <div class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                                        <p>{{ $staff->phone ?: '—' }}</p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $staff->email ?: '—' }}</p>
                                    </div>
                                </td>
                                <td class="table-body-cell">
                                    <x-ui.badge tone="sky">
                                        {{ $positionLabels[$staff->position] ?? $staff->positionLabel() }}
                                    </x-ui.badge>
                                </td>
                                <td class="table-body-cell">
                                    <x-ui.badge tone="violet">
                                        {{ $systemRoleLabels[$staff->system_role] ?? $staff->systemRoleLabel() }}
                                    </x-ui.badge>
                                </td>
                                <td class="table-body-cell">
                                    <x-ui.badge :tone="\App\Models\Staff::contractTone($staff->contract_type)">
                                        {{ $contractLabels[$staff->contract_type] ?? $staff->contract_type }}
                                    </x-ui.badge>
                                </td>
                                <td class="table-body-cell">
                                    <x-ui.badge :tone="\App\Models\Staff::statusTone($staff->status)">
                                        {{ $statusLabels[$staff->status] ?? $staff->status }}
                                    </x-ui.badge>
                                </td>
                                <td class="table-body-cell">
                                    <x-ui.badge :tone="$staff->user_id ? 'emerald' : 'slate'">
                                        {{ $staff->user_id ? __('people.staff.index.filters.linked_account') : __('people.staff.index.filters.unlinked_account') }}
                                    </x-ui.badge>
                                </td>
                                <td class="table-body-cell text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @can('view', $staff)
                                            <x-ui.button :href="route('people.staff.show', $staff)" variant="secondary" size="sm">
                                                {{ __('people.staff.index.actions.view') }}
                                            </x-ui.button>
                                        @endcan

                                        @can('update', $staff)
                                            <x-ui.button :href="route('people.staff.edit', $staff)" variant="warning" size="sm">
                                                {{ __('people.staff.index.actions.edit') }}
                                            </x-ui.button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>

                {{ $staffMembers->links('components.ui.pagination') }}
            @else
                <x-ui.empty-state
                    :title="__('people.staff.index.empty.title')"
                    :description="__('people.staff.index.empty.description')"
                    icon="fa-people-carry-box"
                />
            @endif
        </x-ui.card>
    </div>
</x-app-layout>
