<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('people.teachers.index.title')" :description="__('people.teachers.index.description')">
            <x-slot:actions>
                @can('create', \App\Models\Teacher::class)
                    <x-ui.button :href="route('people.teachers.create')">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('people.teachers.index.create_action') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    @php
        $teacherTabs = [
            ['track' => null, 'label' => __('people.teachers.index.tabs.all'), 'icon' => 'fa-users', 'count' => $stats['total']],
            ['track' => 'primary', 'label' => __('people.teachers.index.tabs.primary'), 'icon' => 'fa-school', 'count' => $stats['primary']],
            ['track' => 'secondary', 'label' => __('people.teachers.index.tabs.secondary'), 'icon' => 'fa-book-open', 'count' => $stats['secondary']],
            ['track' => 'unclassified', 'label' => __('people.teachers.index.tabs.unclassified'), 'icon' => 'fa-triangle-exclamation', 'count' => $stats['unclassified']],
        ];

        $baseTeacherQuery = array_filter(
            request()->except(['track', 'page']),
            static fn ($value) => $value !== null && $value !== ''
        );
    @endphp

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <x-ui.stats-card :label="__('people.teachers.index.stats.total')" :value="$stats['total']" icon="fa-chalkboard-user" tone="sky" />
            <x-ui.stats-card :label="__('people.teachers.index.stats.active')" :value="$stats['active']" icon="fa-circle-check" tone="emerald" />
            <x-ui.stats-card :label="__('people.teachers.index.stats.primary')" :value="$stats['primary']" icon="fa-school" tone="emerald" />
            <x-ui.stats-card :label="__('people.teachers.index.stats.secondary')" :value="$stats['secondary']" icon="fa-book-open" tone="sky" />
            <x-ui.stats-card :label="__('people.teachers.index.stats.linked_account')" :value="$stats['linked_account']" icon="fa-address-card" tone="amber" />
            <x-ui.stats-card :label="__('people.teachers.index.stats.without_assignments')" :value="$stats['without_assignments']" icon="fa-triangle-exclamation" tone="rose" />
        </section>

        <x-ui.card class="space-y-5">
            <div class="flex flex-wrap items-center gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                @foreach ($teacherTabs as $tab)
                    @php
                        $tabQuery = $baseTeacherQuery;

                        if ($tab['track'] !== null) {
                            $tabQuery['track'] = $tab['track'];
                        }

                        $isActive = ($track === '' && $tab['track'] === null) || ($track === $tab['track']);
                    @endphp

                    <x-ui.button
                        :href="route('people.teachers.index', $tabQuery)"
                        :variant="$isActive ? 'primary' : 'secondary'"
                        size="sm"
                        class="whitespace-nowrap"
                        :aria-current="$isActive ? 'page' : null"
                    >
                        <i class="fa-solid {{ $tab['icon'] }} text-xs"></i>
                        <span>{{ $tab['label'] }}</span>
                        <span class="{{ $isActive ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200' }} inline-flex min-w-8 items-center justify-center rounded-full px-2 py-0.5 text-xs font-semibold">
                            {{ $tab['count'] }}
                        </span>
                    </x-ui.button>
                @endforeach
            </div>

            <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.common.actions.filter') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.teachers.index.search_placeholder') }}</p>
            </div>

            <form method="GET" class="grid gap-4 xl:grid-cols-[1.6fr_1fr_1fr_1fr_1fr_1fr_auto]">
                <x-form.input name="search" type="search" :label="__('people.teachers.index.search_label')" :value="$search" icon="fa-magnifying-glass" :placeholder="__('people.teachers.index.search_placeholder')" />

                <input type="hidden" name="track" value="{{ $track }}">

                <x-form.select name="cycle_id" :label="__('people.teachers.index.filters.cycle')" icon="fa-diagram-project" :value="$cycleId" :placeholder="__('people.teachers.index.filters.all_cycles')">
                    @foreach ($cycleOptions as $cycle)
                        <option value="{{ $cycle->id }}" @selected((string) $cycleId === (string) $cycle->id)>
                            {{ $cycle->name }}
                            @if ($cycle->category)
                                · {{ $cycle->category->teacherTrackLabel() }}
                            @endif
                        </option>
                    @endforeach
                </x-form.select>

                <x-form.select name="status" :label="__('people.teachers.index.filters.status')" icon="fa-toggle-on" :value="$status" :placeholder="__('people.teachers.index.filters.all_statuses')">
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </x-form.select>

                <x-form.select name="contract" :label="__('people.teachers.index.filters.contract')" icon="fa-file-contract" :value="$contract" :placeholder="__('people.teachers.index.filters.all_contracts')">
                    @foreach ($contractOptions as $value => $label)
                        <option value="{{ $value }}" @selected($contract === $value)>{{ $label }}</option>
                    @endforeach
                </x-form.select>

                <x-form.select name="account" :label="__('people.teachers.index.filters.account')" icon="fa-address-card" :value="$account" :placeholder="__('people.teachers.index.filters.all_accounts')">
                    @foreach ($accountOptions as $value => $label)
                        <option value="{{ $value }}" @selected($account === $value)>{{ $label }}</option>
                    @endforeach
                </x-form.select>

                <x-form.select name="invitation" :label="__('people.teachers.index.filters.invitation')" icon="fa-paper-plane" :value="$invitation" :placeholder="__('people.teachers.index.filters.all_invitations')">
                    @foreach ($invitationOptions as $value => $label)
                        <option value="{{ $value }}" @selected($invitation === $value)>{{ $label }}</option>
                    @endforeach
                </x-form.select>

                <div class="flex flex-wrap items-end gap-3">
                    <x-ui.button type="submit">
                        {{ __('core.common.actions.filter') }}
                    </x-ui.button>
                    <x-ui.button :href="route('people.teachers.index')" variant="secondary">
                        {{ __('core.common.actions.reset') }}
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card class="space-y-4">
            <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.teachers.index.title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.teachers.index.description') }}</p>
                </div>

                <x-ui.badge tone="sky">{{ $teachers->total() }} {{ __('people.teachers.index.stats.total') }}</x-ui.badge>
            </div>

            @if ($teachers->count() > 0)
                <x-ui.table>
                    <thead>
                        <tr>
                            <th class="table-head-cell">{{ __('people.teachers.index.table.teacher') }}</th>
                            <th class="table-head-cell">{{ __('people.teachers.index.table.contact') }}</th>
                            <th class="table-head-cell">{{ __('people.teachers.index.table.cycle') }}</th>
                            <th class="table-head-cell">{{ __('people.teachers.index.table.placement') }}</th>
                            <th class="table-head-cell">{{ __('people.teachers.index.table.specialty') }}</th>
                            <th class="table-head-cell">{{ __('people.teachers.index.table.contract') }}</th>
                            <th class="table-head-cell">{{ __('people.teachers.index.table.assignments') }}</th>
                            <th class="table-head-cell">{{ __('people.teachers.index.table.status') }}</th>
                            <th class="table-head-cell">{{ __('people.teachers.index.table.account') }}</th>
                            <th class="table-head-cell text-right">{{ __('people.teachers.index.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($teachers as $teacher)
                            <tr class="table-body-row">
                                <td class="table-body-cell">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $teacher->fullName() }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $teacher->matricule }}</p>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <x-ui.badge :tone="$teacher->pedagogicTrackTone()">
                                                {{ $teacher->pedagogicTrackLabel() }}
                                            </x-ui.badge>
                                        </div>
                                    </div>
                                </td>
                                <td class="table-body-cell">
                                    <div class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                                        <p>{{ $teacher->phone ?: '—' }}</p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $teacher->email ?: '—' }}</p>
                                    </div>
                                </td>
                                <td class="table-body-cell text-gray-600 dark:text-gray-300">
                                    <div class="space-y-1">
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $teacher->academicCycle?->name ?: '—' }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $teacher->academicCycle?->code ?: '—' }}</p>
                                    </div>
                                </td>
                                <td class="table-body-cell text-gray-600 dark:text-gray-300">
                                    <div class="space-y-1">
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $teacher->pedagogicPlacementLabel() ?: ($teacher->specialty ?: '—') }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $teacher->pedagogicPlacementTypeLabel() ?: __('people.teachers.show.placement') }}</p>
                                    </div>
                                </td>
                                <td class="table-body-cell text-gray-600 dark:text-gray-300">
                                    {{ $teacher->specialty ?: '—' }}
                                </td>
                                <td class="table-body-cell">
                                    <x-ui.badge :tone="\App\Models\Teacher::contractTone($teacher->contract_type)">
                                        {{ $contractOptions[$teacher->contract_type] ?? $teacher->contract_type }}
                                    </x-ui.badge>
                                </td>
                                <td class="table-body-cell">
                                    <x-ui.badge tone="sky">{{ $teacher->assignments_count + (($teacher->mainClassroom || $teacher->mainSubject) ? 1 : 0) }}</x-ui.badge>
                                </td>
                                <td class="table-body-cell">
                                    <x-ui.badge :tone="\App\Models\Teacher::statusTone($teacher->status)">
                                        {{ $statusOptions[$teacher->status] ?? $teacher->status }}
                                    </x-ui.badge>
                                </td>
                                <td class="table-body-cell">
                                    <div class="space-y-2">
                                        @if ($teacher->user)
                                            <x-ui.badge :tone="$teacher->user->is_active ? 'emerald' : 'rose'">
                                                {{ $teacher->user->is_active ? __('people.accounts.badges.active') : __('people.accounts.badges.suspended') }}
                                            </x-ui.badge>
                                        @elseif ($teacher->latestInvitation)
                                            <x-ui.badge :tone="$teacher->latestInvitation->statusToneValue()">
                                                {{ $teacher->latestInvitation->statusLabel() }}
                                            </x-ui.badge>
                                        @else
                                            <x-ui.badge tone="slate">{{ __('people.accounts.badges.no_account') }}</x-ui.badge>
                                        @endif

                                        @if ($teacher->latestInvitation)
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $teacher->latestInvitation->stateTitle() }}</p>
                                        @endif
                                    </div>
                                </td>
                                <td class="table-body-cell text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @can('view', $teacher)
                                            <x-ui.button :href="route('people.teachers.show', $teacher)" variant="ghost" size="sm">
                                                {{ __('people.teachers.index.actions.view') }}
                                            </x-ui.button>
                                        @endcan

                                        @can('update', $teacher)
                                            <x-ui.button :href="route('people.teachers.edit', $teacher)" variant="secondary" size="sm">
                                                {{ __('people.teachers.index.actions.edit') }}
                                            </x-ui.button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-ui.table>

                <x-ui.pagination :paginator="$teachers" />
            @else
                <x-ui.empty-state
                    :title="__('people.teachers.index.empty.title')"
                    :description="__('people.teachers.index.empty.description')"
                    icon="fa-chalkboard-user"
                />
            @endif
        </x-ui.card>
    </div>
</x-app-layout>
