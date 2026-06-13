<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="$isEdit ? __('core.schools.form.edit_title') : __('core.schools.form.create_title')"
            :description="__('core.schools.form.description')"
        >
            <x-slot:actions>
                <x-ui.button :href="route('core.schools.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
                <x-ui.button type="submit" form="school-form">
                    {{ $isEdit ? __('core.schools.form.submit_edit') : __('core.schools.form.submit_create') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <form id="school-form" method="POST" action="{{ $isEdit ? route('core.schools.update', $school) : route('core.schools.store') }}" class="space-y-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <x-ui.card as="section">
            <div class="border-b border-gray-100 pb-4 dark:border-slate-700/70">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.schools.form.identity_title') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('core.schools.form.identity_description') }}</p>
            </div>

            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <x-form.input name="name" :label="__('core.schools.form.name')" :value="$school->name" icon="fa-school" placeholder="Ex. Collège Les Merveilles" required />
                <x-form.input name="email" type="email" :label="__('core.schools.form.email')" :value="$school->email" icon="fa-envelope" placeholder="contact@ecole.test" />
                <x-form.input name="phone" :label="__('core.schools.form.phone')" :value="$school->phone" icon="fa-phone" placeholder="+229 01 00 00 00 00" />
                <x-form.input name="location" :label="__('core.schools.form.location')" :value="$school->address" icon="fa-location-dot" placeholder="Ex. Cotonou, Akpakpa" />
            </div>
        </x-ui.card>

        <x-ui.card as="section">
            <div class="border-b border-gray-100 pb-4 dark:border-slate-700/70">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.schools.form.details_title') }}</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('core.schools.form.details_description') }}</p>
            </div>

            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-slate-700/75 dark:bg-slate-950/75 md:col-span-2">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        class="form-checkbox"
                        @checked(old('is_active', $school->is_active))
                    >
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('core.schools.form.active_label') }}</span>
                </label>

                @if ($school->is_default)
                    <x-ui.alert tone="info" class="md:col-span-2">
                        {{ __('core.schools.form.default_hint') }}
                    </x-ui.alert>
                @endif
            </div>
        </x-ui.card>

        <div class="flex flex-wrap gap-3">
            <x-ui.button type="submit">
                {{ $isEdit ? __('core.schools.form.submit_edit') : __('core.schools.form.submit_create') }}
            </x-ui.button>
            <x-ui.button :href="route('core.schools.index')" variant="secondary">
                {{ __('core.common.actions.back') }}
            </x-ui.button>
        </div>
    </form>
</x-app-layout>
