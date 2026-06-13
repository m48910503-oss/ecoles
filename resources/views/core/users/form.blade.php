<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="$isEdit ? __('core.users.form.edit_title') : __('core.users.form.create_title')"
            :description="__('core.users.form.description')"
        >
            <x-slot:actions>
                <x-ui.button :href="route('core.users.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
                <x-ui.button type="submit" form="user-form">
                    {{ $isEdit ? __('core.users.form.submit_edit') : __('core.users.form.submit_create') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <form
        id="user-form"
        method="POST"
        action="{{ $isEdit ? route('core.users.update', $userModel) : route('core.users.store') }}"
        class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]"
        x-data="{ active: @js((bool) old('is_active', $isEdit ? $userModel->is_active : true)) }"
    >
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <x-ui.card as="section" class="space-y-6">
            <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.users.form.identity_title') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('core.users.form.identity_description') }}</p>
            </div>

            <x-form.input name="name" :label="__('core.users.form.name')" :value="$userModel->name" icon="fa-user" required />
            <x-form.input name="email" type="email" :label="__('core.users.form.email')" :value="$userModel->email" icon="fa-envelope" required />

            <div class="grid gap-5 md:grid-cols-2" x-data="{ passwordsVisible: false }">
                <x-form.input
                    name="password"
                    type="password"
                    :label="$isEdit ? __('core.users.form.new_password') : __('core.users.form.password')"
                    :hint="$isEdit ? __('core.users.form.password_hint') : null"
                    icon="fa-lock"
                    :required="! $isEdit"
                    :password-visible-model="'passwordsVisible'"
                />

                <x-form.input
                    name="password_confirmation"
                    type="password"
                    :label="__('core.users.form.password_confirmation')"
                    icon="fa-lock-keyhole"
                    :required="! $isEdit"
                    :password-visible-model="'passwordsVisible'"
                />

                <div class="md:col-span-2 flex justify-end pt-1">
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

            <div class="border-t border-gray-100 pt-5 dark:border-gray-800">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ __('core.users.form.access_title') }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('core.users.form.access_description') }}</p>
                </div>

                <label class="mt-5 flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                    <input id="is_active" name="is_active" type="checkbox" value="1" class="form-checkbox" x-model="active" @checked(old('is_active', $isEdit ? $userModel->is_active : true))>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('core.users.form.active_label') }}</span>
                </label>

                <div class="mt-4" x-show="!active" x-cloak>
                    <x-form.textarea
                        name="suspended_reason"
                        :label="__('core.users.form.suspension_reason')"
                        :value="$userModel->suspended_reason"
                        :hint="__('core.users.form.suspension_hint')"
                        icon="fa-ban"
                    />
                </div>
            </div>
        </x-ui.card>

        <section class="space-y-6">
            <x-ui.card>
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.users.form.roles_title') }}</h2>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach ($roles as $role)
                        <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 px-4 py-3 transition-colors duration-200 hover:border-sky-200 hover:bg-sky-50/40 dark:border-gray-800 dark:bg-gray-950/40 dark:hover:border-sky-800 dark:hover:bg-sky-950/20">
                            <input
                                type="checkbox"
                                name="role_ids[]"
                                value="{{ $role->id }}"
                                class="form-checkbox shrink-0"
                                @checked(in_array($role->id, old('role_ids', $selectedRoleIds), false))
                            >
                            <span class="block text-sm font-semibold text-gray-900 dark:text-white">{{ $role->name }}</span>
                        </label>
                    @endforeach
                </div>

                @error('role_ids')
                    <p class="mt-3 text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>
                @enderror
            </x-ui.card>

            <div class="flex flex-wrap gap-3">
                <x-ui.button type="submit">
                    {{ $isEdit ? __('core.users.form.submit_edit') : __('core.users.form.submit_create') }}
                </x-ui.button>
                <x-ui.button :href="route('core.users.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
            </div>
        </section>
    </form>
</x-app-layout>
