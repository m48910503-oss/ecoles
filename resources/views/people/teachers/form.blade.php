<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="$isEdit ? __('people.teachers.form.edit_title') : __('people.teachers.form.create_title')"
            :description="__('people.teachers.form.description')"
        >
            <x-slot:actions>
                <x-ui.button :href="route('people.teachers.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>

                <x-ui.button type="submit" form="teacher-form">
                    {{ $isEdit ? __('people.teachers.form.submit_edit') : __('people.teachers.form.submit_create') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <form
        id="teacher-form"
        method="POST"
        action="{{ $isEdit ? route('people.teachers.update', $teacher) : route('people.teachers.store') }}"
        class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]"
        x-data="{
            selectedCycleId: @js(old('academic_cycle_id', $teacher->academic_cycle_id ? (string) $teacher->academic_cycle_id : '')),
            cycleTrackMap: @js($cycleTrackMap),
            isPrimaryTrack() {
                return this.cycleTrackMap[this.selectedCycleId] === 'primary';
            },
            isSecondaryTrack() {
                return this.cycleTrackMap[this.selectedCycleId] === 'secondary';
            },
        }"
    >
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <section class="space-y-6">
            <x-ui.card class="space-y-6">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.teachers.form.identity_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.teachers.form.identity_description') }}</p>
                </div>

                <x-form.input
                    name="matricule_preview"
                    :label="__('people.teachers.form.matricule')"
                    :value="$nextMatricule"
                    icon="fa-hashtag"
                    readonly
                    disabled
                    :hint="__('people.teachers.form.matricule_hint')"
                />

                <div class="grid gap-5 md:grid-cols-2">
                    <x-form.input name="last_name" :label="__('people.teachers.form.last_name')" :value="old('last_name', $teacher->last_name)" icon="fa-user" required />
                    <x-form.input name="first_names" :label="__('people.teachers.form.first_names')" :value="old('first_names', $teacher->first_names)" icon="fa-user" required />
                </div>

                <x-form.select name="gender" :label="__('people.teachers.form.gender')" icon="fa-venus-mars" required :placeholder="__('people.teachers.form.gender_placeholder')">
                    @foreach ($genderOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('gender', $teacher->gender) === $value)>{{ $label }}</option>
                    @endforeach
                </x-form.select>
            </x-ui.card>

            <x-ui.card class="space-y-6">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.teachers.form.contact_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.teachers.form.contact_description') }}</p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <x-form.input name="phone" type="tel" :label="__('people.teachers.form.phone')" :value="old('phone', $teacher->phone)" icon="fa-phone" />
                    <x-form.input name="email" type="email" :label="__('people.teachers.form.email')" :value="old('email', $teacher->email)" icon="fa-envelope" />
                </div>

                <x-form.input name="address" :label="__('people.teachers.form.address')" :value="old('address', $teacher->address)" icon="fa-location-dot" />

                <x-ui.alert tone="info" :title="__('people.teachers.form.contact_title')">
                    {{ __('people.teachers.form.detail_hint') }}
                </x-ui.alert>
            </x-ui.card>
        </section>

        <section class="space-y-6">
            <x-ui.card class="space-y-6">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.teachers.form.professional_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.teachers.form.professional_description') }}</p>
                </div>

                <x-form.input name="specialty" :label="__('people.teachers.form.specialty')" :value="old('specialty', $teacher->specialty)" icon="fa-book-open" />
                <x-form.input name="diploma" :label="__('people.teachers.form.diploma')" :value="old('diploma', $teacher->diploma)" icon="fa-graduation-cap" />

                <x-form.select name="contract_type" :label="__('people.teachers.form.contract_type')" icon="fa-file-contract" required :placeholder="__('people.teachers.form.contract_placeholder')">
                    @foreach ($contractOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('contract_type', $teacher->contract_type) === $value)>{{ $label }}</option>
                    @endforeach
                </x-form.select>
            </x-ui.card>

            <x-ui.card class="space-y-6">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.teachers.form.pedagogic_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.teachers.form.pedagogic_description') }}</p>
                </div>

                <x-form.select
                    name="academic_cycle_id"
                    :label="__('people.teachers.form.academic_cycle')"
                    icon="fa-layer-group"
                    required
                    x-model="selectedCycleId"
                    :placeholder="__('people.teachers.form.academic_cycle_placeholder')"
                >
                    @foreach ($cycles as $cycle)
                        <option value="{{ $cycle->id }}" @selected((string) old('academic_cycle_id', $teacher->academic_cycle_id) === (string) $cycle->id)>
                            {{ $cycle->name }}@if ($cycle->category) · {{ $cycle->category->teacherTrackLabel() }}@endif
                        </option>
                    @endforeach
                </x-form.select>

                <x-ui.alert tone="info">
                    {{ __('people.teachers.form.academic_cycle_hint') }}
                </x-ui.alert>

                <div x-show="isPrimaryTrack()" x-cloak class="space-y-4">
                    <x-ui.alert tone="emerald">
                        {{ __('people.teachers.form.primary_hint') }}
                    </x-ui.alert>

                    <x-form.select
                        name="main_classroom_id"
                        :label="__('people.teachers.form.main_classroom')"
                        icon="fa-school"
                        :placeholder="__('people.teachers.form.main_classroom_placeholder')"
                        x-bind:disabled="!isPrimaryTrack()"
                        x-bind:required="isPrimaryTrack()"
                    >
                        @foreach ($primaryClassrooms as $classroom)
                            <option value="{{ $classroom->id }}" @selected((string) old('main_classroom_id', $teacher->main_classroom_id) === (string) $classroom->id)>
                                {{ $classroom->name }}
                                @if ($classroom->level?->cycle)
                                    · {{ $classroom->level->cycle->name }} / {{ $classroom->level->name }}
                                @endif
                            </option>
                        @endforeach
                    </x-form.select>
                </div>

                <div x-show="isSecondaryTrack()" x-cloak class="space-y-4">
                    <x-ui.alert tone="sky">
                        {{ __('people.teachers.form.secondary_hint') }}
                    </x-ui.alert>

                    <x-form.select
                        name="main_subject_id"
                        :label="__('people.teachers.form.main_subject')"
                        icon="fa-book-open"
                        :placeholder="__('people.teachers.form.main_subject_placeholder')"
                        x-bind:disabled="!isSecondaryTrack()"
                        x-bind:required="isSecondaryTrack()"
                    >
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected((string) old('main_subject_id', $teacher->main_subject_id) === (string) $subject->id)>
                                {{ $subject->name }}{{ $subject->code ? ' · '.$subject->code : '' }}
                            </option>
                        @endforeach
                    </x-form.select>
                </div>
            </x-ui.card>

            <x-ui.card class="space-y-6">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.teachers.form.administration_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.teachers.form.administration_description') }}</p>
                </div>

                <x-form.select name="status" :label="__('people.teachers.form.status')" icon="fa-toggle-on" required :placeholder="__('people.teachers.form.status_placeholder')">
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $teacher->status) === $value)>{{ $label }}</option>
                    @endforeach
                </x-form.select>

                <x-form.textarea name="notes" :label="__('people.teachers.form.notes')" :value="old('notes', $teacher->notes)" icon="fa-note-sticky" />
            </x-ui.card>

            <div class="flex flex-wrap gap-3">
                <x-ui.button type="submit">
                    {{ $isEdit ? __('people.teachers.form.submit_edit') : __('people.teachers.form.submit_create') }}
                </x-ui.button>
                <x-ui.button :href="route('people.teachers.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
            </div>
        </section>
    </form>
</x-app-layout>
