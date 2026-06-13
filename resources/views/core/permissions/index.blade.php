<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('core.permissions.index.title')" :description="__('core.permissions.index.description')">
            <x-slot:actions>
                @can('viewAny', \App\Models\Role::class)
                    <x-ui.button :href="route('core.roles.index')" variant="secondary">
                        <i class="fa-solid fa-user-shield text-xs"></i>
                        {{ __('core.permissions.index.manage_roles_action') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('core.permissions.index.stats.total')" :value="$stats['total']" icon="fa-key" tone="sky" />
            <x-ui.stats-card :label="__('core.permissions.index.stats.modules')" :value="$stats['modules']" icon="fa-layer-group" tone="violet" />
            <x-ui.stats-card :label="__('core.permissions.index.stats.linked')" :value="$stats['linked']" icon="fa-link" tone="emerald" />
            <x-ui.stats-card :label="__('core.permissions.index.stats.unlinked')" :value="$stats['unlinked']" icon="fa-unlink" tone="amber" />
        </section>

        <x-ui.card>
            <form method="GET" data-auto-submit class="grid gap-3 xl:grid-cols-[minmax(0,1.2fr)_220px_220px_auto_auto]">
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('core.permissions.index.search_placeholder') }}" class="form-input">

                <select name="module" class="form-input">
                    <option value="">{{ __('core.common.filters.all_modules') }}</option>
                    @foreach ($modules as $availableModule)
                        <option value="{{ $availableModule }}" @selected($module === $availableModule)>{{ strtoupper($availableModule) }}</option>
                    @endforeach
                </select>

                <select name="coverage" class="form-input">
                    <option value="">{{ __('core.permissions.index.all_coverages') }}</option>
                    <option value="linked" @selected($coverage === 'linked')>{{ __('core.permissions.index.linked_only') }}</option>
                    <option value="unlinked" @selected($coverage === 'unlinked')>{{ __('core.permissions.index.unlinked_only') }}</option>
                </select>

                <x-ui.button type="submit" variant="secondary">
                    <i class="fa-solid fa-filter text-xs"></i>
                    {{ __('core.common.actions.filter') }}
                </x-ui.button>

                <x-ui.button :href="route('core.permissions.index')" variant="ghost">
                    {{ __('core.common.actions.reset') }}
                </x-ui.button>
            </form>
        </x-ui.card>

        @if ($permissions->count() > 0)
            <x-ui.table>
                <thead>
                    <tr>
                        <th class="table-head-cell">{{ __('core.permissions.index.table.permission') }}</th>
                        <th class="table-head-cell">{{ __('core.permissions.index.table.module') }}</th>
                        <th class="table-head-cell">{{ __('core.permissions.index.table.description') }}</th>
                        <th class="table-head-cell">{{ __('core.permissions.index.table.roles') }}</th>
                        <th class="table-head-cell">{{ __('core.permissions.index.table.management') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($permissions as $permission)
                        <tr class="table-body-row">
                            <td class="table-body-cell">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $permission->name }}</p>
                            </td>
                            <td class="table-body-cell">
                                <x-ui.badge tone="slate">{{ strtoupper($permission->module) }}</x-ui.badge>
                            </td>
                            <td class="table-body-cell text-gray-500 dark:text-gray-400">
                                {{ $permission->description }}
                            </td>
                            <td class="table-body-cell">
                                <div class="flex flex-wrap gap-2">
                                    @forelse ($permission->roles->take(3) as $role)
                                        <x-ui.badge tone="sky">{{ $role->name }}</x-ui.badge>
                                    @empty
                                        <x-ui.badge tone="rose">{{ __('core.permissions.index.no_roles') }}</x-ui.badge>
                                    @endforelse

                                    @if ($permission->roles_count > 3)
                                        <x-ui.badge tone="slate">{{ __('core.permissions.index.more_roles', ['count' => $permission->roles_count - 3]) }}</x-ui.badge>
                                    @endif
                                </div>
                            </td>
                            <td class="table-body-cell">
                                <x-ui.badge tone="violet">{{ __('core.permissions.index.managed_through_roles') }}</x-ui.badge>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        @else
            <x-ui.empty-state :title="__('core.permissions.index.empty.title')" :description="__('core.permissions.index.empty.description')" icon="fa-key" />
        @endif

        <x-ui.pagination :paginator="$permissions" />
    </div>
</x-app-layout>
