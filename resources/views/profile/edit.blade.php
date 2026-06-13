<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="page-title">Mon profil</h1>
            <p class="page-subtitle">Mettre à jour vos informations personnelles et votre mot de passe.</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="page-card">
            <div class="max-w-2xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="page-card">
            <div class="max-w-2xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>
    </div>
</x-app-layout>
