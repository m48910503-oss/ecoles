@php
    $positionLabels = \App\Models\Staff::positionOptions();
    $systemRoleLabels = \App\Models\Staff::systemRoleOptions();
    $contractLabels = \App\Models\Staff::contractOptions();
    $statusLabels = \App\Models\Staff::statusOptions();
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$isEdit ? __('people.staff.form.edit_title') : __('people.staff.form.create_title')" :description="__('people.staff.form.description')">
            <x-slot:actions>
                <x-ui.button :href="$isEdit ? route('people.staff.show', $staff) : route('people.staff.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-6">
        <x-ui.alert tone="info">
            {{ __('people.staff.form.detail_hint') }}
        </x-ui.alert>

        <form method="POST" action="{{ $isEdit ? route('people.staff.update', $staff) : route('people.staff.store') }}" class="space-y-6">
            @csrf
            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="grid gap-6 xl:grid-cols-2">
                <x-ui.card class="space-y-5">
                    <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.staff.form.identity_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.staff.form.identity_description') }}</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-form.input name="matricule" :label="__('people.staff.form.matricule')" :value="$nextMatricule" icon="fa-hashtag" readonly hint="{{ __('people.staff.form.matricule_hint') }}" />
                        <x-form.select name="gender" :label="__('people.staff.form.gender')" icon="fa-venus-mars" :value="old('gender', $staff->gender)" :placeholder="__('people.staff.form.gender_placeholder')" required>
                            @foreach ($genderOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('gender', $staff->gender) === $value)>{{ $label }}</option>
                            @endforeach
                        </x-form.select>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-form.input name="last_name" :label="__('people.staff.form.last_name')" :value="old('last_name', $staff->last_name)" icon="fa-user" required />
                        <x-form.input name="first_names" :label="__('people.staff.form.first_names')" :value="old('first_names', $staff->first_names)" icon="fa-user" required />
                    </div>
                </x-ui.card>

                <x-ui.card class="space-y-5">
                    <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.staff.form.contact_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.staff.form.contact_description') }}</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-form.input name="phone" :label="__('people.staff.form.phone')" :value="old('phone', $staff->phone)" icon="fa-phone" />
                        <x-form.input type="email" name="email" :label="__('people.staff.form.email')" :value="old('email', $staff->email)" icon="fa-envelope" />
                    </div>

                    <x-form.textarea name="address" :label="__('people.staff.form.address')" icon="fa-location-dot" :value="old('address', $staff->address)" />
                </x-ui.card>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <x-ui.card class="space-y-5">
                    <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.staff.form.professional_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.staff.form.professional_description') }}</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-form.select name="position" :label="__('people.staff.form.position')" icon="fa-briefcase" :value="old('position', $staff->position)" :placeholder="__('people.staff.form.position_placeholder')" required>
                            @foreach ($positionLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('position', $staff->position) === $value)>{{ $label }}</option>
                            @endforeach
                        </x-form.select>

                        <x-form.select name="system_role" :label="__('people.staff.form.system_role')" icon="fa-user-shield" :value="old('system_role', $staff->system_role ?? 'autre')" :placeholder="__('people.staff.form.system_role_placeholder')" required>
                            @foreach ($systemRoleLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('system_role', $staff->system_role ?? 'autre') === $value)>{{ $label }}</option>
                            @endforeach
                        </x-form.select>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <x-form.select name="contract_type" :label="__('people.staff.form.contract_type')" icon="fa-file-contract" :value="old('contract_type', $staff->contract_type)" :placeholder="__('people.staff.form.contract_placeholder')" required>
                            @foreach ($contractLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('contract_type', $staff->contract_type) === $value)>{{ $label }}</option>
                            @endforeach
                        </x-form.select>

                        <x-form.input type="date" name="service_start_date" :label="__('people.staff.form.service_start_date')" :value="old('service_start_date', optional($staff->service_start_date)->format('Y-m-d'))" icon="fa-calendar-day" />
                    </div>
                </x-ui.card>

                <x-ui.card class="space-y-5">
                    <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.staff.form.administration_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.staff.form.administration_description') }}</p>
                    </div>

                    <x-form.select name="status" :label="__('people.staff.form.status')" icon="fa-toggle-on" :value="old('status', $staff->status)" :placeholder="__('people.staff.form.status_placeholder')" required>
                        @foreach ($statusLabels as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $staff->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </x-form.select>

                    <x-form.textarea name="notes" :label="__('people.staff.form.notes')" icon="fa-note-sticky" :value="old('notes', $staff->notes)" />
                </x-ui.card>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button type="submit">
                    {{ $isEdit ? __('people.staff.form.submit_edit') : __('people.staff.form.submit_create') }}
                </x-ui.button>

                <x-ui.button :href="$isEdit ? route('people.staff.show', $staff) : route('people.staff.index')" variant="secondary">
                    {{ __('core.common.actions.cancel') }}
                </x-ui.button>
            </div>
        </form>
    </div>
</x-app-layout>
