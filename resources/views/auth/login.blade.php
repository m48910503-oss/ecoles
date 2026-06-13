<x-guest-layout>
    <div>
        <p class="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-700">Connexion</p>
        <h1 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950">Accéder au Core System</h1>
        <p class="mt-3 text-sm leading-7 text-slate-600">
            Utilise ton compte pour administrer les utilisateurs, les rôles, les paramètres globaux et l’année académique active.
        </p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="email" class="form-label">Adresse email</label>
            <input id="email" class="form-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" />
            <p class="mt-2 text-xs leading-5 text-slate-500">
                Utilise l’adresse e-mail associée au compte. Certains comptes internes se terminent par <span class="font-semibold">.local</span>.
            </p>
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-sm text-rose-600" />
        </div>

        <div>
            <div class="flex items-center justify-between gap-3">
                <label for="password" class="form-label !mb-0">Mot de passe</label>
                @if (Route::has('password.request'))
                    <a class="text-sm font-medium text-emerald-700 hover:text-emerald-800" href="{{ route('password.request') }}">
                        Mot de passe oublié ?
                    </a>
                @endif
            </div>

            <x-form.input
                name="password"
                type="password"
                class="mt-2"
                required
                autocomplete="current-password"
            />
        </div>

        <label for="remember_me" class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            <input id="remember_me" type="checkbox" class="form-checkbox" name="remember">
            <span>Maintenir ma session ouverte sur cet appareil</span>
        </label>

        <button type="submit" class="btn-primary w-full">
            Se connecter
        </button>
    </form>
</x-guest-layout>
