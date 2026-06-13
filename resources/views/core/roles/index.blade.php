<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('core.roles.index.title')" :description="__('core.roles.index.description')">
            <x-slot:actions>
                @can('viewAny', \App\Models\Permission::class)
                    <x-ui.button :href="route('core.permissions.index')" variant="secondary">
                        <i class="fa-solid fa-key text-xs"></i>
                        {{ __('core.roles.index.permissions_action') }}
                    </x-ui.button>
                @endcan

                @can('create', \App\Models\Role::class)
                    <x-ui.button :href="route('core.roles.create')">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('core.roles.index.create_action') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('core.roles.index.stats.total')" :value="$stats['total']" icon="fa-user-shield" tone="sky" />
            <x-ui.stats-card :label="__('core.roles.index.stats.active')" :value="$stats['active']" icon="fa-circle-check" tone="emerald" />
            <x-ui.stats-card :label="__('core.roles.index.stats.system')" :value="$stats['system']" icon="fa-shield-halved" tone="violet" />
            <x-ui.stats-card :label="__('core.roles.index.stats.optional')" :value="$stats['optional']" icon="fa-toggle-on" tone="amber" />
        </section>

        <x-ui.card>
            <form method="GET" data-auto-submit class="grid gap-3 xl:grid-cols-[minmax(0,1.3fr)_220px_auto_auto]">
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('core.roles.index.search_placeholder') }}" class="form-input">

                <select name="status" class="form-input">
                    <option value="">{{ __('core.common.filters.all_statuses') }}</option>
                    <option value="active" @selected($status === 'active')>{{ __('core.common.states.active') }}</option>
                    <option value="inactive" @selected($status === 'inactive')>{{ __('core.common.states.inactive') }}</option>
                </select>

                <x-ui.button type="submit" variant="secondary">
                    <i class="fa-solid fa-filter text-xs"></i>
                    {{ __('core.common.actions.filter') }}
                </x-ui.button>

                <x-ui.button :href="route('core.roles.index')" variant="ghost">
                    {{ __('core.common.actions.reset') }}
                </x-ui.button>
            </form>
        </x-ui.card>

        @if ($roles->count() > 0)
            <x-ui.table>
                <thead>
                    <tr>
                        <th class="table-head-cell">{{ __('core.roles.index.table.role') }}</th>
                        <th class="table-head-cell">{{ __('core.roles.index.table.slug') }}</th>
                        <th class="table-head-cell">{{ __('core.roles.index.table.status') }}</th>
                        <th class="table-head-cell">{{ __('core.roles.index.table.type') }}</th>
                        <th class="table-head-cell">{{ __('core.roles.index.table.users') }}</th>
                        <th class="table-head-cell">{{ __('core.roles.index.table.permissions') }}</th>
                        <th class="table-head-cell text-right">{{ __('core.roles.index.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($roles as $role)
                        <tr class="table-body-row">
                            <td class="table-body-cell">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $role->name }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $role->description }}</p>
                            </td>
                            <td class="table-body-cell">
                                <span class="font-medium text-gray-700 dark:text-gray-200">{{ $role->slug }}</span>
                            </td>
                            <td class="table-body-cell">
                                <x-ui.badge :tone="$role->is_active ? 'emerald' : 'slate'">
                                    {{ $role->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                                </x-ui.badge>
                            </td>
                            <td class="table-body-cell">
                                <div class="flex flex-wrap gap-2">
                                    @if ($role->is_system)
                                        <x-ui.badge tone="violet">{{ __('core.common.states.system') }}</x-ui.badge>
                                    @endif

                                    @if ($role->is_optional)
                                        <x-ui.badge tone="amber">{{ __('core.common.states.optional') }}</x-ui.badge>
                                    @endif

                                    @if (! $role->is_system && ! $role->is_optional)
                                        <x-ui.badge tone="slate">{{ __('core.common.states.standard') }}</x-ui.badge>
                                    @endif
                                </div>
                            </td>
                            <td class="table-body-cell">
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $role->users_count }}</span>
                            </td>
                            <td class="table-body-cell">
                                <span class="font-semibold text-gray-900 dark:text-white">{{ $role->permissions_count }}</span>
                            </td>
                            <td class="table-body-cell text-right">
                                @can('update', $role)
                                    <x-ui.button :href="route('core.roles.edit', $role)" variant="ghost" size="sm">
                                        {{ __('core.common.actions.edit') }}
                                    </x-ui.button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        @else
            <x-ui.empty-state :title="__('core.roles.index.empty.title')" :description="__('core.roles.index.empty.description')" icon="fa-user-shield">
                <x-slot:actions>
                    @can('create', \App\Models\Role::class)
                        <x-ui.button :href="route('core.roles.create')">
                            <i class="fa-solid fa-plus text-xs"></i>
                            {{ __('core.roles.index.create_action') }}
                        </x-ui.button>
                    @endcan
                </x-slot:actions>
            </x-ui.empty-state>
        @endif

        <x-ui.pagination :paginator="$roles" />
    </div>
</x-app-layout>
