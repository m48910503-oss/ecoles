<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="$isEdit ? __('core.roles.form.edit_title') : __('core.roles.form.create_title')"
            :description="__('core.roles.form.description')"
        >
            <x-slot:actions>
                <x-ui.button :href="route('core.roles.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
                <x-ui.button type="submit" form="role-form">
                    {{ $isEdit ? __('core.roles.form.submit_edit') : __('core.roles.form.submit_create') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <form id="role-form" method="POST" action="{{ $isEdit ? route('core.roles.update', $roleModel) : route('core.roles.store') }}" class="space-y-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <section class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
            <x-ui.card class="space-y-6">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.roles.form.identity_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('core.roles.form.identity_description') }}</p>
                </div>

                <x-form.input name="name" :label="__('core.roles.form.name')" :value="$roleModel->name" icon="fa-user-shield" required />
                <x-form.input name="slug" :label="__('core.roles.form.slug')" :value="$roleModel->slug" icon="fa-fingerprint" required />
                <x-form.textarea name="description" :label="__('core.roles.form.description_label')" :value="$roleModel->description" icon="fa-align-left" />

                <div class="grid gap-4 md:grid-cols-2">
                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <input type="checkbox" name="is_active" value="1" class="form-checkbox" @checked(old('is_active', $isEdit ? $roleModel->is_active : true))>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('core.roles.form.active_label') }}</span>
                    </label>
                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <input type="checkbox" name="is_optional" value="1" class="form-checkbox" @checked(old('is_optional', $roleModel->is_optional))>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('core.roles.form.optional_label') }}</span>
                    </label>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.roles.form.permissions_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('core.roles.form.permissions_description') }}</p>
                </div>

                <div class="mt-5 space-y-6">
                    @foreach ($permissions as $module => $modulePermissions)
                        <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                            <div class="flex items-center justify-between gap-3">
                                <x-ui.badge tone="slate">{{ strtoupper($module) }}</x-ui.badge>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $modulePermissions->count() }} permission(s)</span>
                            </div>

                            <div class="mt-4 grid gap-3">
                                @foreach ($modulePermissions as $permission)
                                    <label class="flex items-start gap-3 rounded-2xl border border-gray-200 bg-white p-4 transition-colors duration-200 hover:border-sky-200 hover:bg-sky-50/40 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-sky-800 dark:hover:bg-sky-950/20">
                                        <input
                                            type="checkbox"
                                            name="permission_ids[]"
                                            value="{{ $permission->id }}"
                                            class="form-checkbox mt-1"
                                            @checked(in_array($permission->id, old('permission_ids', $selectedPermissionIds), false))
                                        >
                                        <span>
                                            <span class="block text-sm font-semibold text-gray-900 dark:text-white">{{ $permission->name }}</span>
                                            <span class="mt-1 block text-sm text-gray-500 dark:text-gray-400">{{ $permission->description }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        </section>

        <div class="flex flex-wrap gap-3">
            <x-ui.button type="submit">
                {{ $isEdit ? __('core.roles.form.submit_edit') : __('core.roles.form.submit_create') }}
            </x-ui.button>
            <x-ui.button :href="route('core.roles.index')" variant="secondary">
                {{ __('core.common.actions.back') }}
            </x-ui.button>
        </div>
    </form>
</x-app-layout>
