<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$dashboard['title']" :description="$dashboard['description']">
            <x-slot:actions>
                @can('viewAny', \App\Models\Student::class)
                    <x-ui.button :href="route('people.students.index')" variant="secondary">
                        <i class="fa-solid fa-list text-xs"></i>
                        {{ __('people.dashboard.quick_actions.view_students') }}
                    </x-ui.button>
                @endcan

                @can('viewAny', \App\Models\Guardian::class)
                    <x-ui.button :href="route('people.parents.index')" variant="secondary">
                        <i class="fa-solid fa-people-roof text-xs"></i>
                        {{ __('people.dashboard.quick_actions.view_parents') }}
                    </x-ui.button>
                @endcan

                @can('viewAny', \App\Models\Teacher::class)
                    <x-ui.button :href="route('people.teachers.index')" variant="secondary">
                        <i class="fa-solid fa-chalkboard-user text-xs"></i>
                        {{ __('people.dashboard.quick_actions.view_teachers') }}
                    </x-ui.button>
                @endcan

                @can('viewAny', \App\Models\Staff::class)
                    <x-ui.button :href="route('people.staff.index')" variant="secondary">
                        <i class="fa-solid fa-people-carry-box text-xs"></i>
                        {{ __('people.dashboard.quick_actions.view_staff') }}
                    </x-ui.button>
                @endcan

                @can('create', \App\Models\Student::class)
                    <x-ui.button :href="route('people.students.create')">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('people.dashboard.quick_actions.create_student') }}
                    </x-ui.button>
                @endcan

                @can('create', \App\Models\Teacher::class)
                    <x-ui.button :href="route('people.teachers.create')" variant="secondary">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('people.dashboard.quick_actions.create_teacher') }}
                    </x-ui.button>
                @endcan

                @can('create', \App\Models\Guardian::class)
                    <x-ui.button :href="route('people.parents.create')" variant="secondary">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('people.dashboard.quick_actions.create_parent') }}
                    </x-ui.button>
                @endcan

                @can('create', \App\Models\Staff::class)
                    <x-ui.button :href="route('people.staff.create')" variant="secondary">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('people.dashboard.quick_actions.create_staff') }}
                    </x-ui.button>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($dashboard['stats'] as $stat)
                <x-ui.stats-card
                    :label="$stat['label']"
                    :value="$stat['value']"
                    :icon="$stat['icon']"
                    :tone="$stat['tone']"
                />
            @endforeach
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <x-ui.card class="space-y-4">
                <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.dashboard.alerts_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.dashboard.alerts_description') }}</p>
                    </div>
                </div>

                <div class="space-y-3">
                    @foreach ($dashboard['alerts'] as $alert)
                        <x-ui.alert :tone="$alert['tone']" :title="$alert['title']" :icon="$alert['icon']">
                            <div class="space-y-3">
                                <p>{{ $alert['description'] }}</p>

                                @if (! empty($alert['action']))
                                    <x-ui.button :href="$alert['action']['route']" variant="secondary" size="sm">
                                        <i class="fa-solid fa-arrow-right text-xs"></i>
                                        {{ $alert['action']['label'] }}
                                    </x-ui.button>
                                @endif
                            </div>
                        </x-ui.alert>
                    @endforeach
                </div>
            </x-ui.card>

            <x-ui.card class="space-y-4">
                <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.dashboard.quick_actions_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.dashboard.quick_actions_description') }}</p>
                    </div>
                </div>

                @if ($dashboard['quick_actions'] !== [])
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($dashboard['quick_actions'] as $action)
                            <x-ui.button :href="$action['route']" :variant="$action['variant'] ?? 'secondary'" class="w-full justify-start">
                                <i class="fa-solid {{ $action['icon'] }} text-xs"></i>
                                <span>{{ $action['label'] }}</span>
                            </x-ui.button>
                        @endforeach
                    </div>
                @else
                    <x-ui.empty-state
                        :title="__('people.dashboard.quick_actions_empty_title')"
                        :description="__('people.dashboard.quick_actions_empty_description')"
                        icon="fa-compass"
                    />
                @endif
            </x-ui.card>
        </section>

        @if ($dashboard['teacher_cards'] !== [])
            <section class="space-y-4">
                <x-ui.card class="space-y-4">
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.dashboard.teachers_title') }}</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.dashboard.teachers_description') }}</p>
                        </div>

                        @can('viewAny', \App\Models\Teacher::class)
                            <x-ui.button :href="route('people.teachers.index')" variant="secondary" size="sm">
                                {{ __('people.dashboard.quick_actions.view_teachers') }}
                            </x-ui.button>
                        @endcan
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        @foreach ($dashboard['teacher_cards'] as $card)
                            <x-ui.card as="a" :href="$card['route']" variant="metric" class="group block h-full transition hover:-translate-y-0.5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500 text-[11px]">{{ $card['label'] }}</p>
                                        <p class="mt-3 break-words font-semibold tracking-tight text-gray-900 dark:text-white text-[1.7rem] leading-none">{{ $card['value'] }}</p>
                                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $card['button_label'] }}</p>
                                    </div>

                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300">
                                        <i class="fa-solid {{ $card['icon'] }} text-sm"></i>
                                    </span>
                                </div>
                            </x-ui.card>
                        @endforeach
                    </div>
                </x-ui.card>
            </section>
        @endif

        @can('viewAny', \App\Models\Teacher::class)
            <section class="space-y-4">
                <x-ui.card class="space-y-4">
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.dashboard.teacher_invitations.title') }}</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.dashboard.teacher_invitations.description') }}</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            @can('viewAny', \App\Models\Teacher::class)
                                <x-ui.button :href="route('people.teachers.index', ['invitation' => 'pending'])" variant="secondary" size="sm">
                                    <i class="fa-solid fa-filter text-xs"></i>
                                    {{ __('people.dashboard.teacher_invitations.actions.view_pending_teachers') }}
                                </x-ui.button>
                            @endcan

                            <x-ui.badge tone="sky">{{ $dashboard['teacher_invitations_stats']['total'] }}</x-ui.badge>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                        @foreach ($dashboard['teacher_invitation_cards'] as $card)
                            <x-dashboard.metric-card
                                :label="$card['label']"
                                :subtitle="$card['subtitle']"
                                :value="$card['value']"
                                value-class="text-xl font-bold leading-tight tracking-tight text-gray-900 dark:text-white sm:text-2xl"
                                :trend="$card['trend']"
                                :icon="$card['icon']"
                                :tone="$card['tone']"
                            />
                        @endforeach
                    </div>
                </x-ui.card>

                <x-ui.card class="space-y-4">
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.dashboard.teacher_invitations.recent.title') }}</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.dashboard.teacher_invitations.recent.description') }}</p>
                        </div>

                        @can('viewAny', \App\Models\Teacher::class)
                            <x-ui.button :href="route('people.teachers.index')" variant="secondary" size="sm">
                                <i class="fa-solid fa-chalkboard-user text-xs"></i>
                                {{ __('people.dashboard.teacher_invitations.actions.view_teachers') }}
                            </x-ui.button>
                        @endcan
                    </div>

                    @if ($dashboard['recent_teacher_invitations']->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                                <thead>
                                    <tr>
                                        <th class="table-head-cell">{{ __('people.dashboard.teacher_invitations.recent.table.teacher') }}</th>
                                        <th class="table-head-cell">{{ __('people.dashboard.teacher_invitations.recent.table.recipient') }}</th>
                                        <th class="table-head-cell">{{ __('people.dashboard.teacher_invitations.recent.table.status') }}</th>
                                        <th class="table-head-cell">{{ __('people.dashboard.teacher_invitations.recent.table.details') }}</th>
                                        <th class="table-head-cell">{{ __('people.dashboard.teacher_invitations.recent.table.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($dashboard['recent_teacher_invitations'] as $invitation)
                                        <tr class="table-body-row align-top">
                                            <td class="table-body-cell">
                                                <div class="space-y-1">
                                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $invitation->teacher?->fullName() ?? '—' }}</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $invitation->teacher?->matricule ?? '—' }}</p>
                                                    <div class="mt-2 flex flex-wrap gap-2">
                                                        <x-ui.badge :tone="$invitation->teacher?->pedagogicTrackTone() ?? 'slate'">
                                                            {{ $invitation->teacher?->pedagogicTrackLabel() ?? __('people.teachers.options.tracks.unclassified') }}
                                                        </x-ui.badge>

                                                        @if ($invitation->teacher?->pedagogicPlacementLabel())
                                                            <x-ui.badge tone="slate">
                                                                {{ $invitation->teacher?->pedagogicPlacementLabel() }}
                                                            </x-ui.badge>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="table-body-cell text-gray-600 dark:text-gray-300">
                                                <div class="space-y-1">
                                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $invitation->recipient_email ?: '—' }}</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $invitation->sent_at?->translatedFormat('d M Y H:i') ?: '—' }}
                                                    </p>
                                                </div>
                                            </td>

                                            <td class="table-body-cell">
                                                <x-ui.badge :tone="$invitation->statusToneValue()">
                                                    {{ $invitation->statusLabel() }}
                                                </x-ui.badge>
                                            </td>

                                            <td class="table-body-cell">
                                                <div class="space-y-1">
                                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $invitation->stateTitle() }}</p>
                                                    <p class="text-xs leading-relaxed text-gray-500 dark:text-gray-400">{{ $invitation->stateDescription() }}</p>
                                                </div>
                                            </td>

                                            <td class="table-body-cell">
                                                <div class="flex flex-wrap justify-end gap-2">
                                                    @if ($invitation->teacher)
                                                        @can('view', $invitation->teacher)
                                                            <x-ui.button :href="route('people.teachers.show', $invitation->teacher)" variant="secondary" size="sm">
                                                                <i class="fa-solid fa-arrow-right text-xs"></i>
                                                                {{ __('people.dashboard.teacher_invitations.recent.actions.view_teacher') }}
                                                            </x-ui.button>
                                                        @endcan

                                                        @if (! $invitation->teacher->user_id && in_array($invitation->displayStatus(), ['pending', 'expired', 'revoked'], true))
                                                            @can('inviteAccount', $invitation->teacher)
                                                                <x-ui.confirm-modal
                                                                    :name="'dashboard-resend-invitation-'.$invitation->id"
                                                                    :action="route('people.teachers.invitation.store', $invitation->teacher)"
                                                                    method="POST"
                                                                    :title="__('people.teachers.invitations.confirmations.send_title')"
                                                                    :description="__('people.teachers.invitations.confirmations.send_description')"
                                                                    :warning="__('people.teachers.invitations.confirmations.send_warning')"
                                                                    :confirmLabel="__('people.dashboard.teacher_invitations.recent.actions.resend_link')"
                                                                    :triggerLabel="__('people.dashboard.teacher_invitations.recent.actions.resend_link')"
                                                                    trigger-variant="secondary"
                                                                    icon="fa-paper-plane"
                                                                />
                                                            @endcan
                                                        @endif

                                                        @if ($invitation->teacher->user && ! $invitation->teacher->user->is_active && in_array($invitation->displayStatus(), ['completed', 'validated'], true))
                                                            @can('validateInvitation', $invitation->teacher)
                                                                <x-ui.confirm-modal
                                                                    :name="'dashboard-validate-invitation-'.$invitation->id"
                                                                    :action="route('people.teachers.invitation.validate', $invitation->teacher)"
                                                                    method="PATCH"
                                                                    :title="__('people.teachers.invitations.confirmations.validate_title')"
                                                                    :description="__('people.teachers.invitations.confirmations.validate_description')"
                                                                    :warning="__('people.teachers.invitations.confirmations.validate_warning')"
                                                                    :confirmLabel="__('people.dashboard.teacher_invitations.recent.actions.validate_signup')"
                                                                    :triggerLabel="__('people.dashboard.teacher_invitations.recent.actions.validate_signup')"
                                                                    trigger-variant="primary"
                                                                    icon="fa-circle-check"
                                                                />
                                                            @endcan
                                                        @endif
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-ui.empty-state
                            :title="__('people.dashboard.teacher_invitations.recent.empty_title')"
                            :description="__('people.dashboard.teacher_invitations.recent.empty_description')"
                            icon="fa-paper-plane"
                        />
                    @endif
                </x-ui.card>
            </section>
        @endcan

        <section class="grid gap-6 xl:grid-cols-3 2xl:grid-cols-4">
            @can('viewAny', \App\Models\Student::class)
                <x-ui.card class="space-y-4">
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.dashboard.recent_students.title') }}</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.dashboard.recent_students.description') }}</p>
                        </div>
                    </div>

                    @if ($dashboard['recent_students']->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                                <thead>
                                    <tr>
                                        <th class="table-head-cell">{{ __('people.students.index.table.student') }}</th>
                                        <th class="table-head-cell">{{ __('people.students.index.table.classroom') }}</th>
                                        <th class="table-head-cell">{{ __('people.students.index.table.parents') }}</th>
                                        <th class="table-head-cell">{{ __('people.students.index.table.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($dashboard['recent_students'] as $student)
                                        <tr class="table-body-row">
                                            <td class="table-body-cell">
                                                <p class="font-semibold text-gray-900 dark:text-white">{{ $student->fullName() }}</p>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $student->matricule }}</p>
                                            </td>
                                            <td class="table-body-cell text-gray-600 dark:text-gray-300">
                                                {{ $student->currentClassroom?->name ?? '—' }}
                                            </td>
                                            <td class="table-body-cell">
                                                <x-ui.badge tone="{{ $student->parents_count > 0 ? 'emerald' : 'rose' }}">
                                                    {{ $student->parents_count }}
                                                </x-ui.badge>
                                            </td>
                                            <td class="table-body-cell">
                                                @php($statusLabels = \App\Models\Student::statusOptions())
                                                <x-ui.badge :tone="\App\Models\Student::statusTone($student->status)">
                                                    {{ $statusLabels[$student->status] ?? $student->status }}
                                                </x-ui.badge>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-ui.empty-state :title="__('people.dashboard.recent_students.empty_title')" :description="__('people.dashboard.recent_students.empty_description')" icon="fa-user-graduate" />
                    @endif
                </x-ui.card>
            @endcan

            @can('viewAny', \App\Models\Guardian::class)
                <x-ui.card class="space-y-4">
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.dashboard.recent_parents.title') }}</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.dashboard.recent_parents.description') }}</p>
                        </div>
                    </div>

                    @if ($dashboard['recent_parents']->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                                <thead>
                                    <tr>
                                        <th class="table-head-cell">{{ __('people.parents.index.table.parent') }}</th>
                                        <th class="table-head-cell">{{ __('people.parents.index.table.contact') }}</th>
                                        <th class="table-head-cell">{{ __('people.parents.index.table.children') }}</th>
                                        <th class="table-head-cell">{{ __('people.parents.index.table.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($dashboard['recent_parents'] as $parent)
                                        <tr class="table-body-row">
                                            <td class="table-body-cell">
                                                <p class="font-semibold text-gray-900 dark:text-white">{{ $parent->fullName() }}</p>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $parent->email ?: '—' }}</p>
                                            </td>
                                            <td class="table-body-cell text-gray-600 dark:text-gray-300">
                                                {{ $parent->phone ?: '—' }}
                                            </td>
                                            <td class="table-body-cell">
                                                <x-ui.badge tone="{{ $parent->students_count > 0 ? 'emerald' : 'rose' }}">
                                                    {{ $parent->students_count }}
                                                </x-ui.badge>
                                            </td>
                                            <td class="table-body-cell">
                                                <x-ui.badge :tone="\App\Models\Guardian::statusTone($parent->status)">
                                                    {{ \App\Models\Guardian::statusOptions()[$parent->status] ?? $parent->status }}
                                                </x-ui.badge>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-ui.empty-state :title="__('people.dashboard.recent_parents.empty_title')" :description="__('people.dashboard.recent_parents.empty_description')" icon="fa-people-roof" />
                    @endif
                </x-ui.card>
            @endcan

            @can('viewAny', \App\Models\Teacher::class)
                <x-ui.card class="space-y-4">
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.dashboard.recent_teachers.title') }}</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.dashboard.recent_teachers.description') }}</p>
                        </div>
                    </div>

                    @if ($dashboard['recent_teachers']->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                                <thead>
                                    <tr>
                                        <th class="table-head-cell">{{ __('people.teachers.index.table.teacher') }}</th>
                                        <th class="table-head-cell">{{ __('people.teachers.index.table.cycle') }}</th>
                                        <th class="table-head-cell">{{ __('people.teachers.index.table.placement') }}</th>
                                        <th class="table-head-cell">{{ __('people.teachers.index.table.assignments') }}</th>
                                        <th class="table-head-cell">{{ __('people.teachers.index.table.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($dashboard['recent_teachers'] as $teacher)
                                        <tr class="table-body-row">
                                            <td class="table-body-cell">
                                                <p class="font-semibold text-gray-900 dark:text-white">{{ $teacher->fullName() }}</p>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $teacher->matricule }}</p>
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    <x-ui.badge :tone="$teacher->pedagogicTrackTone()">
                                                        {{ $teacher->pedagogicTrackLabel() }}
                                                    </x-ui.badge>
                                                    @if ($teacher->latestInvitation)
                                                        <x-ui.badge :tone="$teacher->latestInvitation->statusToneValue()">
                                                            {{ $teacher->latestInvitation->statusLabel() }}
                                                        </x-ui.badge>
                                                    @endif
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
                                            <td class="table-body-cell">
                                                <x-ui.badge tone="sky">{{ $teacher->assignments_count + (($teacher->mainClassroom || $teacher->mainSubject) ? 1 : 0) }}</x-ui.badge>
                                            </td>
                                            <td class="table-body-cell">
                                                <x-ui.badge :tone="\App\Models\Teacher::statusTone($teacher->status)">
                                                    {{ \App\Models\Teacher::statusOptions()[$teacher->status] ?? $teacher->status }}
                                                </x-ui.badge>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-ui.empty-state :title="__('people.dashboard.recent_teachers.empty_title')" :description="__('people.dashboard.recent_teachers.empty_description')" icon="fa-chalkboard-user" />
                    @endif
                </x-ui.card>
            @endcan

            @can('viewAny', \App\Models\Staff::class)
                <x-ui.card class="space-y-4">
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.dashboard.recent_staff.title') }}</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.dashboard.recent_staff.description') }}</p>
                        </div>
                    </div>

                    @if ($dashboard['recent_staff']->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                                <thead>
                                    <tr>
                                        <th class="table-head-cell">{{ __('people.staff.index.table.staff') }}</th>
                                        <th class="table-head-cell">{{ __('people.staff.index.table.position') }}</th>
                                        <th class="table-head-cell">{{ __('people.staff.index.table.account') }}</th>
                                        <th class="table-head-cell">{{ __('people.staff.index.table.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($dashboard['recent_staff'] as $staff)
                                        <tr class="table-body-row">
                                            <td class="table-body-cell">
                                                <p class="font-semibold text-gray-900 dark:text-white">{{ $staff->fullName() }}</p>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $staff->matricule }}</p>
                                            </td>
                                            <td class="table-body-cell text-gray-600 dark:text-gray-300">
                                                {{ $staff->positionLabel() }}
                                            </td>
                                            <td class="table-body-cell">
                                                <x-ui.badge tone="{{ $staff->user_id ? 'emerald' : 'rose' }}">
                                                    {{ $staff->user_id ? __('people.staff.show.linked_account') : __('people.staff.show.no_account') }}
                                                </x-ui.badge>
                                            </td>
                                            <td class="table-body-cell">
                                                <x-ui.badge :tone="\App\Models\Staff::statusTone($staff->status)">
                                                    {{ \App\Models\Staff::statusOptions()[$staff->status] ?? $staff->status }}
                                                </x-ui.badge>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-ui.empty-state :title="__('people.dashboard.recent_staff.empty_title')" :description="__('people.dashboard.recent_staff.empty_description')" icon="fa-people-carry-box" />
                    @endif
                </x-ui.card>
            @endcan
        </section>
    </div>
</x-app-layout>
