<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('core.users.index.title')" :description="__('core.users.index.description')">
            <x-slot:actions>
                @can('create', \App\Models\User::class)
                    <x-ui.button :href="route('core.users.create')">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('core.users.index.create_action') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    @php
        $localeLabels = config('app.available_locales', ['fr' => 'Français', 'en' => 'English']);
    @endphp

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('core.users.index.stats.total')" :value="$stats['total']" icon="fa-users" tone="sky" />
            <x-ui.stats-card :label="__('core.users.index.stats.active')" :value="$stats['active']" icon="fa-circle-check" tone="emerald" />
            <x-ui.stats-card :label="__('core.users.index.stats.suspended')" :value="$stats['suspended']" icon="fa-user-slash" tone="rose" />
            <x-ui.stats-card :label="__('core.users.index.stats.multi_role')" :value="$stats['multi_role']" icon="fa-shuffle" tone="violet" />
        </section>

        <x-ui.card>
            <form method="GET" data-auto-submit class="grid gap-3 xl:grid-cols-[minmax(0,1.3fr)_220px_220px_auto_auto]">
                <div class="xl:col-span-1">
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="{{ __('core.users.index.search_placeholder') }}"
                        class="form-input"
                    >
                </div>

                <div>
                    <select name="status" class="form-input">
                        <option value="">{{ __('core.common.filters.all_statuses') }}</option>
                        <option value="active" @selected($status === 'active')>{{ __('core.common.states.active') }}</option>
                        <option value="suspended" @selected($status === 'suspended')>{{ __('core.common.states.suspended') }}</option>
                    </select>
                </div>

                <div>
                    <select name="role_id" class="form-input">
                        <option value="">{{ __('core.users.index.all_roles') }}</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected((string) $roleId === (string) $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>

                <x-ui.button type="submit" variant="secondary">
                    <i class="fa-solid fa-filter text-xs"></i>
                    {{ __('core.common.actions.filter') }}
                </x-ui.button>

                <x-ui.button :href="route('core.users.index')" variant="ghost">
                    {{ __('core.common.actions.reset') }}
                </x-ui.button>
            </form>
        </x-ui.card>

        @if ($users->count() > 0)
            <x-ui.table>
                <thead>
                    <tr>
                        <th class="table-head-cell">{{ __('core.users.index.table.user') }}</th>
                        <th class="table-head-cell">{{ __('core.users.index.table.roles') }}</th>
                        <th class="table-head-cell">{{ __('core.users.index.table.status') }}</th>
                        <th class="table-head-cell">{{ __('core.users.index.table.locale') }}</th>
                        <th class="table-head-cell">{{ __('core.users.index.table.last_login') }}</th>
                        <th class="table-head-cell text-right">{{ __('core.users.index.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($users as $user)
                        <tr class="table-body-row">
                            <td class="table-body-cell">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $user->name }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                            </td>
                            <td class="table-body-cell">
                                <div class="flex flex-wrap gap-2">
                                    @forelse ($user->roles as $role)
                                        <x-ui.badge tone="slate">{{ $role->name }}</x-ui.badge>
                                    @empty
                                        <x-ui.badge tone="rose">{{ __('core.permissions.index.no_roles') }}</x-ui.badge>
                                    @endforelse
                                </div>
                            </td>
                            <td class="table-body-cell">
                                <x-ui.badge :tone="$user->is_active ? 'emerald' : 'rose'">
                                    {{ $user->is_active ? __('core.common.states.active') : __('core.common.states.suspended') }}
                                </x-ui.badge>
                            </td>
                            <td class="table-body-cell">
                                <x-ui.badge tone="sky">{{ $localeLabels[$user->preferred_locale ?? app()->currentLocale()] ?? strtoupper($user->preferred_locale ?? app()->currentLocale()) }}</x-ui.badge>
                            </td>
                            <td class="table-body-cell text-gray-500 dark:text-gray-400">
                                {{ $user->last_login_at?->translatedFormat('d M Y H:i') ?? __('core.common.states.never') }}
                            </td>
                            <td class="table-body-cell text-right">
                                @can('update', $user)
                                    <x-ui.button :href="route('core.users.edit', $user)" variant="ghost" size="sm">
                                        {{ __('core.common.actions.edit') }}
                                    </x-ui.button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        @else
            <x-ui.empty-state :title="__('core.users.index.empty.title')" :description="__('core.users.index.empty.description')" icon="fa-users">
                <x-slot:actions>
                    @can('create', \App\Models\User::class)
                        <x-ui.button :href="route('core.users.create')">
                            <i class="fa-solid fa-plus text-xs"></i>
                            {{ __('core.users.index.create_action') }}
                        </x-ui.button>
                    @endcan
                </x-slot:actions>
            </x-ui.empty-state>
        @endif

        <x-ui.pagination :paginator="$users" />
    </div>
</x-app-layout>
