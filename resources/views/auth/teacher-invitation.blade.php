<x-guest-layout>
    @php
        $teacher = $invitation?->teacher;
        $cycleLabel = $teacher?->academicCycle?->name;
        $trackLabel = $teacher?->pedagogicTrackLabel();
        $placementLabel = $teacher?->pedagogicPlacementLabel();
        $expiresAt = $invitation?->expires_at?->translatedFormat('d M Y H:i');
        $stateTone = match ($state) {
            'pending' => 'info',
            'completed' => 'warning',
            'validated' => 'success',
            'revoked', 'expired', 'invalid' => 'error',
            default => 'info',
        };
    @endphp

    <div class="space-y-8">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-700">
                {{ __('people.teachers.invitations.form.kicker') }}
            </p>
            <h1 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950">
                {{ __('people.teachers.invitations.form.title') }}
            </h1>
            <p class="mt-3 text-sm leading-7 text-slate-600">
                {{ __('people.teachers.invitations.form.description') }}
            </p>
        </div>

        @if ($teacher)
            <section class="grid gap-4 rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-2">
                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">{{ __('people.teachers.invitations.fields.teacher') }}</p>
                    <p class="mt-2 text-sm font-semibold text-slate-950">{{ $teacher->fullName() }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $teacher->matricule }}</p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">{{ __('people.teachers.invitations.fields.cycle') }}</p>
                    <p class="mt-2 text-sm font-semibold text-slate-950">{{ $cycleLabel ?: '—' }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $trackLabel }}</p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">{{ __('people.teachers.invitations.fields.placement') }}</p>
                    <p class="mt-2 text-sm font-semibold text-slate-950">{{ $placementLabel ?: '—' }}</p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">{{ __('people.teachers.invitations.fields.expires_at') }}</p>
                    <p class="mt-2 text-sm font-semibold text-slate-950">{{ $expiresAt ?: '—' }}</p>
                </div>
            </section>
        @endif

        @if ($state === 'pending' && $invitation)
            <x-ui.alert tone="info" :title="$invitation->stateTitle()">
                {{ $invitation->stateDescription() }}
            </x-ui.alert>

            <form method="POST" action="{{ route('teacher.invitations.store', $token) }}" class="space-y-5 rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm">
                @csrf

                <div class="grid gap-5">
                    <x-form.input
                        name="email"
                        type="email"
                        :label="__('people.teachers.invitations.fields.email')"
                        :value="old('email', $invitation->recipient_email ?? $teacher?->email)"
                        icon="fa-envelope"
                        required
                    />

                    <x-form.input
                        name="password"
                        type="password"
                        :label="__('people.teachers.invitations.fields.password')"
                        icon="fa-lock"
                        required
                    />

                    <x-form.input
                        name="password_confirmation"
                        type="password"
                        :label="__('people.teachers.invitations.fields.password_confirmation')"
                        icon="fa-lock"
                        required
                    />
                </div>

                <x-ui.alert tone="warning" :title="__('people.teachers.invitations.form.note_title')">
                    {{ __('people.teachers.invitations.form.note_description') }}
                </x-ui.alert>

                <div class="flex flex-wrap gap-3">
                    <x-ui.button type="submit">
                        <i class="fa-solid fa-user-check text-xs"></i>
                        {{ __('people.teachers.invitations.form.submit') }}
                    </x-ui.button>

                    <x-ui.button :href="route('login')" variant="secondary">
                        {{ __('people.teachers.invitations.form.back_to_login') }}
                    </x-ui.button>
                </div>
            </form>
        @else
            <x-ui.alert :tone="$stateTone" :title="$invitation?->stateTitle() ?? __('people.teachers.invitations.title')">
                <div class="space-y-3">
                    <p>{{ $invitation?->stateDescription() ?? __('people.teachers.invitations.messages.invalid') }}</p>

                    @if (in_array($state, ['completed', 'validated'], true))
                        <p>{{ __('people.teachers.invitations.form.completed_help') }}</p>
                    @endif
                </div>
            </x-ui.alert>

            <div class="flex flex-wrap gap-3">
                <x-ui.button :href="route('login')">
                    {{ __('people.teachers.invitations.form.back_to_login') }}
                </x-ui.button>

                @if ($teacher)
                    <x-ui.button :href="'mailto:'.config('mail.from.address')" variant="secondary">
                        {{ __('people.teachers.invitations.form.contact_office') }}
                    </x-ui.button>
                @endif
            </div>
        @endif
    </div>
</x-guest-layout>
