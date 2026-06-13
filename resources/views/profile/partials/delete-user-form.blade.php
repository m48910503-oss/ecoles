<section class="space-y-6">
    <header>
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
            Supprimer le compte
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Cette action supprime définitivement le compte et ses accès.
        </p>
    </header>

    <x-ui.button
        x-data=""
        type="button"
        variant="danger"
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >
        <i class="fa-solid fa-trash-can text-xs"></i>
        Supprimer le compte
    </x-ui.button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="rounded-[1.75rem] bg-white p-6 shadow-2xl shadow-slate-900/10 dark:bg-slate-900">
            @csrf
            @method('delete')

            <div class="flex items-start gap-4">
                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300">
                    <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                </span>

                <div class="min-w-0 flex-1">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Confirmer la suppression
                    </h2>

                    <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                        Cette action est irréversible. Saisissez votre mot de passe pour confirmer la suppression du compte.
                    </p>
                </div>
            </div>

            <div class="mt-6">
                <x-form.input
                    name="password"
                    type="password"
                    label="Mot de passe"
                    icon="fa-lock"
                    placeholder="Ex : Votre mot de passe"
                    error-bag="userDeletion"
                    required
                />
            </div>

            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <x-ui.button type="button" variant="secondary" x-on:click="$dispatch('close')">
                    <i class="fa-solid fa-xmark text-xs"></i>
                    Annuler
                </x-ui.button>

                <x-ui.button type="submit" variant="danger">
                    <i class="fa-solid fa-trash-can text-xs"></i>
                    Confirmer la suppression
                </x-ui.button>
            </div>
        </form>
    </x-modal>
</section>
