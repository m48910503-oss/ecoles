<section>
    <header>
        <h2 class="text-lg font-semibold text-slate-950">
            Mot de passe
        </h2>

        <p class="mt-2 text-sm leading-7 text-slate-600">
            Utilisez un mot de passe long et robuste pour renforcer la sécurité de votre compte.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6" x-data="{ passwordsVisible: false }">
        @csrf
        @method('put')

        <x-form.input
            name="current_password"
            type="password"
            label="Mot de passe actuel"
            autocomplete="current-password"
            error-bag="updatePassword"
            required
            :password-visible-model="'passwordsVisible'"
        />

        <x-form.input
            name="password"
            type="password"
            label="Nouveau mot de passe"
            autocomplete="new-password"
            error-bag="updatePassword"
            required
            :password-visible-model="'passwordsVisible'"
        />

        <x-form.input
            name="password_confirmation"
            type="password"
            label="Confirmation du mot de passe"
            autocomplete="new-password"
            error-bag="updatePassword"
            required
            :password-visible-model="'passwordsVisible'"
        />

        <div class="flex flex-wrap items-center justify-between gap-4">
            <x-ui.button
                type="button"
                variant="secondary"
                size="sm"
                x-on:click="passwordsVisible = ! passwordsVisible"
                x-bind:aria-label="passwordsVisible ? '{{ __('ui.form.passwords_hide') }}' : '{{ __('ui.form.passwords_show') }}'"
                x-bind:aria-pressed="passwordsVisible.toString()"
            >
                <x-ui.password-toggle-icon state="passwordsVisible" />
                <span x-text="passwordsVisible ? '{{ __('ui.form.passwords_hide') }}' : '{{ __('ui.form.passwords_show') }}'"></span>
            </x-ui.button>

            <button type="submit" class="btn-primary">Enregistrer</button>
        </div>
    </form>
</section>
