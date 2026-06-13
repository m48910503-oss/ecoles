<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$staff->fullName()" :description="__('people.staff.show.description')">
            <x-slot:actions>
                <x-ui.button :href="$backUrl" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>

                @can('update', $staff)
                    <x-ui.button :href="route('people.staff.edit', $staff)" variant="warning">
                        {{ __('core.common.actions.edit') }}
                    </x-ui.button>
                @endcan

                @can('viewAccount', $staff)
                    <x-ui.button href="#account" variant="secondary">
                        {{ __('people.accounts.actions.view_section') }}
                    </x-ui.button>
                @endcan

                @can('viewDocuments', $staff)
                    <x-ui.button href="#documents" variant="secondary">
                        {{ __('people.documents.show_action') }}
                    </x-ui.button>
                @endcan

                @can('history', $staff)
                    <x-ui.button href="#history" variant="secondary">
                        {{ __('people.staff.show.history_title') }}
                    </x-ui.button>
                @endcan

                @can('disable', $staff)
                    <x-ui.confirm-modal
                        :name="'toggle-staff-'.$staff->id"
                        :action="route('people.staff.toggle-status', $staff)"
                        method="PATCH"
                        :title="$staff->status === 'actif' ? __('people.staff.show.toggle_suspend') : __('people.staff.show.toggle_reactivate')"
                        :description="$staff->status === 'actif' ? __('people.staff.show.toggle_suspend_confirm') : __('people.staff.show.toggle_reactivate_confirm')"
                        :warning="$staff->status === 'actif' ? __('people.staff.show.toggle_suspend_warning') : __('people.staff.show.toggle_reactivate_warning')"
                        :confirmLabel="$staff->status === 'actif' ? __('people.staff.show.toggle_suspend') : __('people.staff.show.toggle_reactivate')"
                        :triggerLabel="$staff->status === 'actif' ? __('people.staff.show.toggle_suspend') : __('people.staff.show.toggle_reactivate')"
                        :trigger-variant="$staff->status === 'actif' ? 'warning' : 'primary'"
                        :icon="$staff->status === 'actif' ? 'fa-user-slash' : 'fa-circle-check'"
                    />
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    @php
        $genderLabels = \App\Models\Staff::genderOptions();
        $statusLabels = \App\Models\Staff::statusOptions();
        $contractLabels = \App\Models\Staff::contractOptions();
        $positionLabels = \App\Models\Staff::positionOptions();
        $systemRoleLabels = \App\Models\Staff::systemRoleOptions();
    @endphp

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('people.staff.form.matricule')" :value="$staff->matricule" icon="fa-hashtag" tone="sky" />
            <x-ui.stats-card :label="__('people.staff.show.position')" :value="$positionLabels[$staff->position] ?? $staff->positionLabel()" icon="fa-briefcase" tone="emerald" />
            <x-ui.stats-card :label="__('people.staff.show.system_role')" :value="$systemRoleLabels[$staff->system_role] ?? $staff->systemRoleLabel()" icon="fa-user-shield" tone="violet" />
            <x-ui.stats-card :label="__('people.staff.form.contract_type')" :value="$contractLabels[$staff->contract_type] ?? $staff->contract_type" icon="fa-file-contract" tone="amber" />
        </section>

        <section class="grid gap-6 xl:grid-cols-3">
            <x-ui.card class="space-y-4 xl:col-span-2">
                <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.staff.show.identity_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.staff.form.identity_description') }}</p>
                    </div>

                    <x-ui.badge :tone="\App\Models\Staff::statusTone($staff->status)">
                        {{ $statusLabels[$staff->status] ?? $staff->status }}
                    </x-ui.badge>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.staff.form.gender') }}</p>
                        <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ $genderLabels[$staff->gender] ?? $staff->gender }}</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.staff.show.position') }}</p>
                        <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ $positionLabels[$staff->position] ?? $staff->positionLabel() }}</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.staff.show.system_role') }}</p>
                        <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ $systemRoleLabels[$staff->system_role] ?? $staff->systemRoleLabel() }}</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.staff.show.service_start_date') }}</p>
                        <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ $staff->service_start_date?->translatedFormat('d M Y') ?? '—' }}</p>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card id="history" class="space-y-4">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.staff.show.contact_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.staff.form.contact_description') }}</p>
                </div>

                <div class="space-y-4 text-sm">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.staff.form.phone') }}</p>
                        <p class="mt-2 font-medium text-gray-900 dark:text-white">{{ $staff->phone ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.staff.form.email') }}</p>
                        <p class="mt-2 font-medium text-gray-900 dark:text-white">{{ $staff->email ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.staff.form.address') }}</p>
                        <p class="mt-2 font-medium text-gray-900 dark:text-white">{{ $staff->address ?: '—' }}</p>
                    </div>
                </div>
            </x-ui.card>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <x-ui.card class="space-y-4">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.staff.form.professional_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.staff.form.professional_description') }}</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.staff.show.position') }}</p>
                        <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ $positionLabels[$staff->position] ?? $staff->positionLabel() }}</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.staff.show.system_role') }}</p>
                        <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ $systemRoleLabels[$staff->system_role] ?? $staff->systemRoleLabel() }}</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.staff.form.contract_type') }}</p>
                        <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ $contractLabels[$staff->contract_type] ?? $staff->contract_type }}</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.staff.show.service_start_date') }}</p>
                        <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ $staff->service_start_date?->translatedFormat('d M Y') ?? '—' }}</p>
                    </div>
                </div>

                @if ($staff->notes)
                    <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.staff.form.notes') }}</p>
                        <p class="mt-2 whitespace-pre-line text-sm text-gray-700 dark:text-gray-200">{{ $staff->notes }}</p>
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card class="space-y-4">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.staff.show.history_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.staff.show.history_empty_description') }}</p>
                </div>

                @if ($canViewHistory && $historyLogs->count() > 0)
                    <div class="space-y-3">
                        @foreach ($historyLogs as $history)
                            <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $history->description ?: $history->action }}</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $history->user?->name ?? __('ui.dashboard.recent_activity.system') }}</p>
                                    </div>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $history->created_at?->translatedFormat('d M Y H:i') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state :title="__('people.staff.show.history_empty_title')" :description="__('people.staff.show.history_empty_description')" icon="fa-file-shield" />
                @endif
            </x-ui.card>
        </section>

        @include('people.accounts.section', ['documentable' => $staff])

        @include('people.documents.section', ['documentable' => $staff, 'documents' => $documents])
    </div>
</x-app-layout>
