@php
    $accountUser = $documentable->user;
    $isStudent = $documentable instanceof \App\Models\Student;
    $isParent = $documentable instanceof \App\Models\Guardian;
    $isStaff = $documentable instanceof \App\Models\Staff;
    $isTeacher = $documentable instanceof \App\Models\Teacher;
    $latestInvitation = $isTeacher ? $documentable->latestInvitation : null;
    $teacherInvitationUrl = $isTeacher ? session('teacher_invitation_url') : null;
    $title = $isStudent
        ? __('people.accounts.student_title')
        : ($isParent ? __('people.accounts.parent_title') : ($isStaff ? __('people.accounts.staff_title') : __('people.accounts.teacher_title')));
    $routePrefix = $isStudent
        ? 'people.students'
        : ($isParent ? 'people.parents' : ($isStaff ? 'people.staff' : 'people.teachers'));
    $showGeneratedEmailBadge = $accountUser && str_ends_with(mb_strtolower((string) $accountUser->email), '.local');
@endphp

@can('viewAccount', $documentable)
    <x-ui.card id="account" class="space-y-6">
        <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.accounts.description') }}</p>
            </div>

            @if ($accountUser)
                <div class="flex flex-wrap gap-2">
                    <x-ui.badge :tone="$accountUser->is_active ? 'emerald' : 'rose'">
                        {{ $accountUser->is_active ? __('people.accounts.badges.active') : __('people.accounts.badges.suspended') }}
                    </x-ui.badge>

                    <x-ui.badge :tone="$accountUser->email_verified_at ? 'emerald' : 'amber'">
                        {{ $accountUser->email_verified_at ? __('people.accounts.badges.verified') : __('people.accounts.badges.unverified') }}
                    </x-ui.badge>

                    @if ($showGeneratedEmailBadge)
                        <x-ui.badge tone="violet">{{ __('people.accounts.badges.generated_email') }}</x-ui.badge>
                    @endif
                </div>
            @else
                <x-ui.badge tone="slate">{{ __('people.accounts.badges.no_account') }}</x-ui.badge>
            @endif
        </div>

        @if ($isTeacher && $latestInvitation)
            <x-ui.alert :tone="$latestInvitation->statusToneValue()" :title="$latestInvitation->stateTitle()">
                <div class="space-y-3">
                    <p>{{ $latestInvitation->stateDescription() }}</p>

                    @if ($latestInvitation->recipient_email)
                        <p class="text-xs font-medium uppercase tracking-[0.18em] text-current/70">
                            {{ __('people.teachers.invitations.fields.recipient_email') }} : {{ $latestInvitation->recipient_email }}
                        </p>
                    @endif

                    @if ($latestInvitation->expires_at)
                        <p class="text-xs font-medium uppercase tracking-[0.18em] text-current/70">
                            {{ __('people.teachers.invitations.fields.expires_at') }} : {{ $latestInvitation->expires_at->translatedFormat('d M Y H:i') }}
                        </p>
                    @endif
                </div>
            </x-ui.alert>
        @endif

        @if ($accountUser)
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.accounts.fields.name') }}</p>
                    <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ $accountUser->name }}</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.accounts.fields.email') }}</p>
                    <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ $accountUser->email }}</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.accounts.fields.roles') }}</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @forelse ($accountUser->roles as $role)
                            <x-ui.badge tone="sky">{{ $role->name }}</x-ui.badge>
                        @empty
                            <x-ui.badge tone="rose">{{ __('people.accounts.badges.no_role') }}</x-ui.badge>
                        @endforelse
                    </div>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('people.accounts.fields.last_login') }}</p>
                    <p class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ $accountUser->last_login_at?->translatedFormat('d M Y H:i') ?? __('core.common.states.never') }}</p>
                </div>
            </div>

            @if (! $accountUser->is_active)
                <x-ui.alert tone="warning" :title="__('people.accounts.alerts.suspended_title')">
                    {{ __('people.accounts.alerts.suspended_description') }}
                </x-ui.alert>
            @endif

            <div class="flex flex-wrap gap-3">
                @can('update', $accountUser)
                    <x-ui.button :href="route('core.users.edit', $accountUser)" variant="secondary">
                        {{ __('people.accounts.actions.open_core') }}
                    </x-ui.button>
                @endcan

                @can('resetAccount', $documentable)
                    <x-ui.confirm-modal
                        :name="'reset-account-'.$documentable->id"
                        :action="route($routePrefix.'.account.reset', $documentable)"
                        method="POST"
                        :title="__('people.accounts.confirmations.reset_title')"
                        :description="__('people.accounts.confirmations.reset_description')"
                        :warning="__('people.accounts.confirmations.reset_warning')"
                        :confirmLabel="__('people.accounts.actions.reset')"
                        :triggerLabel="__('people.accounts.actions.reset')"
                        trigger-variant="warning"
                        icon="fa-key"
                    />
                @endcan

                @if ($isTeacher && $latestInvitation && in_array($latestInvitation->displayStatus(), ['completed', 'validated'], true) && ! $accountUser->is_active)
                    @can('validateInvitation', $documentable)
                        <x-ui.confirm-modal
                            :name="'validate-invitation-'.$documentable->id"
                            :action="route('people.teachers.invitation.validate', $documentable)"
                            method="PATCH"
                            :title="__('people.teachers.invitations.confirmations.validate_title')"
                            :description="__('people.teachers.invitations.confirmations.validate_description')"
                            :warning="__('people.teachers.invitations.confirmations.validate_warning')"
                            :confirmLabel="__('people.teachers.invitations.actions.validate')"
                            :triggerLabel="__('people.teachers.invitations.actions.validate')"
                            trigger-variant="primary"
                            icon="fa-circle-check"
                        />
                    @endcan
                @else
                    @can('toggleAccount', $documentable)
                        <x-ui.confirm-modal
                            :name="'toggle-account-'.$documentable->id"
                            :action="route($routePrefix.'.account.toggle', $documentable)"
                            method="PATCH"
                            :title="$accountUser->is_active ? __('people.accounts.confirmations.suspend_title') : __('people.accounts.confirmations.reactivate_title')"
                            :description="$accountUser->is_active ? __('people.accounts.confirmations.suspend_description') : __('people.accounts.confirmations.reactivate_description')"
                            :warning="$accountUser->is_active ? __('people.accounts.confirmations.suspend_warning') : __('people.accounts.confirmations.reactivate_warning')"
                            :confirmLabel="$accountUser->is_active ? __('people.accounts.actions.suspend') : __('people.accounts.actions.reactivate')"
                            :triggerLabel="$accountUser->is_active ? __('people.accounts.actions.suspend') : __('people.accounts.actions.reactivate')"
                            :trigger-variant="$accountUser->is_active ? 'warning' : 'primary'"
                            :icon="$accountUser->is_active ? 'fa-user-slash' : 'fa-circle-check'"
                        />
                    @endcan
                @endif

                @can('unlinkAccount', $documentable)
                    <x-ui.confirm-modal
                        :name="'unlink-account-'.$documentable->id"
                        :action="route($routePrefix.'.account.destroy', $documentable)"
                        method="DELETE"
                        :title="__('people.accounts.confirmations.unlink_title')"
                        :description="__('people.accounts.confirmations.unlink_description')"
                        :warning="__('people.accounts.confirmations.unlink_warning')"
                        :confirmLabel="__('people.accounts.actions.unlink')"
                        :triggerLabel="__('people.accounts.actions.unlink')"
                        trigger-variant="danger"
                        icon="fa-link-slash"
                    />
                @endcan
            </div>
        @else
            <x-ui.empty-state
                :title="__('people.accounts.empty.title')"
                :description="__('people.accounts.empty.description')"
                icon="fa-address-card"
            >
                <x-slot:actions>
                    @can('createAccount', $documentable)
                        <x-ui.confirm-modal
                            :name="'create-account-'.$documentable->id"
                            :action="route($routePrefix.'.account.store', $documentable)"
                            method="POST"
                            :title="__('people.accounts.confirmations.create_title')"
                            :description="__('people.accounts.confirmations.create_description')"
                            :warning="__('people.accounts.confirmations.create_warning')"
                            :confirmLabel="__('people.accounts.actions.create')"
                            :triggerLabel="__('people.accounts.actions.create')"
                            trigger-variant="primary"
                            icon="fa-user-plus"
                        />
                    @endcan

                    @if ($isTeacher)
                        @can('inviteAccount', $documentable)
                            <x-ui.confirm-modal
                                :name="'invite-teacher-'.$documentable->id"
                                :action="route('people.teachers.invitation.store', $documentable)"
                                method="POST"
                                :title="__('people.teachers.invitations.confirmations.send_title')"
                                :description="__('people.teachers.invitations.confirmations.send_description')"
                                :warning="__('people.teachers.invitations.confirmations.send_warning')"
                                :confirmLabel="$latestInvitation && $latestInvitation->isPending() ? __('people.teachers.invitations.actions.resend') : __('people.teachers.invitations.actions.send')"
                                :triggerLabel="$latestInvitation && $latestInvitation->isPending() ? __('people.teachers.invitations.actions.resend') : __('people.teachers.invitations.actions.send')"
                                trigger-variant="secondary"
                                icon="fa-paper-plane"
                            />
                        @endcan
                    @endif
                </x-slot:actions>
            </x-ui.empty-state>

            @if ($isTeacher && $teacherInvitationUrl)
                <x-ui.alert tone="info" :title="__('people.teachers.invitations.actions.open_link')">
                    <div class="space-y-4">
                        <p>{{ __('people.teachers.invitations.messages.link_ready') }}</p>

                        <x-form.input
                            name="teacher_invitation_url"
                            :label="__('people.teachers.invitations.fields.invitation_url')"
                            :value="$teacherInvitationUrl"
                            readonly
                            icon="fa-link"
                        />

                        <x-ui.button :href="$teacherInvitationUrl" variant="secondary" target="_blank" rel="noopener">
                            <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                            {{ __('people.teachers.invitations.actions.open_link') }}
                        </x-ui.button>
                    </div>
                </x-ui.alert>
            @endif
        @endif
    </x-ui.card>
@endcan
