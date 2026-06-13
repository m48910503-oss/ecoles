<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="$isEdit ? __('people.parents.form.edit_title') : __('people.parents.form.create_title')"
            :description="__('people.parents.form.description')"
        >
            <x-slot:actions>
                <x-ui.button :href="route('people.parents.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>

                <x-ui.button type="submit" form="parent-form">
                    {{ $isEdit ? __('people.parents.form.submit_edit') : __('people.parents.form.submit_create') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    @php
        $statusLabels = \App\Models\Guardian::statusOptions();
    @endphp

    <form
        id="parent-form"
        method="POST"
        action="{{ $isEdit ? route('people.parents.update', $parent) : route('people.parents.store') }}"
        class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]"
    >
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <section class="space-y-6">
            <x-ui.card class="space-y-6">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.parents.form.identity_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.parents.form.identity_description') }}</p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <x-form.input name="last_name" :label="__('people.parents.form.last_name')" :value="old('last_name', $parent->last_name)" icon="fa-user" required />
                    <x-form.input name="first_names" :label="__('people.parents.form.first_names')" :value="old('first_names', $parent->first_names)" icon="fa-user" required />
                </div>

                <x-form.select name="gender" :label="__('people.parents.form.gender')" icon="fa-venus-mars" required>
                    <option value="">{{ __('people.parents.index.filters.all_genders') }}</option>
                    @foreach ($genderOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('gender', $parent->gender) === $value)>{{ $label }}</option>
                    @endforeach
                </x-form.select>
            </x-ui.card>

            <x-ui.card class="space-y-6">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.parents.form.contact_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.parents.form.contact_description') }}</p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <x-form.input name="phone" type="tel" :label="__('people.parents.form.phone')" :value="old('phone', $parent->phone)" icon="fa-phone" />
                    <x-form.input name="email" type="email" :label="__('people.parents.form.email')" :value="old('email', $parent->email)" icon="fa-envelope" />
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <x-form.input name="address" :label="__('people.parents.form.address')" :value="old('address', $parent->address)" icon="fa-location-dot" />
                    <x-form.input name="profession" :label="__('people.parents.form.profession')" :value="old('profession', $parent->profession)" icon="fa-briefcase" />
                </div>

                <x-ui.alert tone="info" :title="__('people.parents.form.contact_hint')">
                    {{ __('people.parents.form.contact_hint') }}
                </x-ui.alert>
            </x-ui.card>
        </section>

        <section class="space-y-6">
            <x-ui.card class="space-y-6">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.parents.form.administration_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.parents.form.administration_description') }}</p>
                </div>

                <x-form.select name="status" :label="__('people.parents.form.status')" icon="fa-toggle-on" required>
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $parent->status) === $value)>{{ $label }}</option>
                    @endforeach
                </x-form.select>

                <x-form.textarea name="notes" :label="__('people.parents.form.notes')" :value="old('notes', $parent->notes)" icon="fa-note-sticky" />
            </x-ui.card>

            <x-ui.card class="space-y-4">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.parents.form.links_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.parents.form.links_description') }}</p>
                </div>

                <x-ui.alert tone="info" :title="__('people.parents.form.links_title')">
                    {{ __('people.parents.form.links_description') }}
                </x-ui.alert>
            </x-ui.card>

            <div class="flex flex-wrap gap-3">
                <x-ui.button type="submit">
                    {{ $isEdit ? __('people.parents.form.submit_edit') : __('people.parents.form.submit_create') }}
                </x-ui.button>
                <x-ui.button :href="route('people.parents.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
            </div>
        </section>
    </form>
</x-app-layout>
