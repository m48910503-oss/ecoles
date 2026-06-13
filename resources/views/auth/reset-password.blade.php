<x-guest-layout>
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4 space-y-4" x-data="{ passwordsVisible: false }">
            <div>
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input
                    id="password"
                    class="block mt-1 w-full"
                    type="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    :password-visible-model="'passwordsVisible'"
                />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

                <x-text-input
                    id="password_confirmation"
                    class="block mt-1 w-full"
                    type="password"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    :password-visible-model="'passwordsVisible'"
                />

                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div class="flex justify-end">
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
            </div>
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Reset Password') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
