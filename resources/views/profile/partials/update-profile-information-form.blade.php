<section>
    <header>
        <h2 class="text-lg font-semibold text-slate-950">
            Informations du compte
        </h2>

        <p class="mt-2 text-sm leading-7 text-slate-600">
            Mettez à jour le nom affiché et l’adresse email associée à votre compte.
        </p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="form-label">Nom complet</label>
            <input id="name" name="name" type="text" class="form-input" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" />
            <x-input-error class="mt-2 text-sm text-rose-600" :messages="$errors->get('name')" />
        </div>

        <div>
            <label for="email" class="form-label">Adresse email</label>
            <input id="email" name="email" type="email" class="form-input" value="{{ old('email', $user->email) }}" required autocomplete="username" />
            <x-input-error class="mt-2 text-sm text-rose-600" :messages="$errors->get('email')" />
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="btn-primary">Enregistrer</button>
        </div>
    </form>
</section>
