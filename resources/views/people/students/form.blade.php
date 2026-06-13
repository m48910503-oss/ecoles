<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="$isEdit ? __('people.students.form.edit_title') : __('people.students.form.create_title')"
            :description="__('people.students.form.description')"
        >
            <x-slot:actions>
                <x-ui.button :href="route('people.students.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>

                <x-ui.button type="submit" form="student-form">
                    {{ $isEdit ? __('people.students.form.submit_edit') : __('people.students.form.submit_create') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <form
        id="student-form"
        method="POST"
        action="{{ $isEdit ? route('people.students.update', $student) : route('people.students.store') }}"
        enctype="multipart/form-data"
        class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]"
        x-data="{
            useExistingGuardian: @js((bool) old('existing_guardian_id', $isEdit ? false : false)),
            status: @js(old('status', $student->status ?? 'preinscrit')),
        }"
    >
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <section class="space-y-6">
            <x-ui.card as="section" class="space-y-6">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.students.form.identity_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.students.form.identity_description') }}</p>
                </div>

                <x-form.input
                    name="matricule_preview"
                    :label="__('people.students.form.matricule')"
                    :value="$nextMatricule"
                    icon="fa-hashtag"
                    readonly
                    disabled
                    :hint="__('people.students.form.description')"
                />

                <div class="space-y-3">
                    <label class="form-label">{{ __('people.students.form.photo') }}</label>

                    @if ($isEdit && $student->photo_path)
                        <div class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($student->photo_path) }}" alt="{{ $student->fullName() }}" class="h-16 w-16 rounded-2xl object-cover ring-1 ring-gray-200 dark:ring-gray-700">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('people.students.form.current_photo') }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('people.students.form.replace_photo') }}</p>
                            </div>
                        </div>
                    @else
                        <div class="rounded-2xl border border-dashed border-gray-200 bg-gray-50/70 p-4 text-sm text-gray-500 dark:border-gray-800 dark:bg-gray-950/40 dark:text-gray-400">
                            {{ __('people.students.form.no_photo') }}
                        </div>
                    @endif

                    <input type="file" name="photo" class="form-input">
                    @error('photo')
                        <p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('people.students.form.photo_hint') }}</p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <x-form.input name="last_name" :label="__('people.students.form.last_name')" :value="$student->last_name" icon="fa-user" required />
                    <x-form.input name="first_names" :label="__('people.students.form.first_names')" :value="$student->first_names" icon="fa-user" required />
                </div>

                <div class="grid gap-5 md:grid-cols-3">
                    <x-form.select name="gender" :label="__('people.students.form.gender')" icon="fa-venus-mars" required>
                        <option value="">{{ __('people.students.index.filters.all_genders') }}</option>
                        @foreach ($genderOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('gender', $student->gender) === $value)>{{ $label }}</option>
                        @endforeach
                    </x-form.select>

                    <x-form.input name="date_of_birth" type="date" :label="__('people.students.form.date_of_birth')" :value="$student->date_of_birth?->format('Y-m-d')" icon="fa-cake-candles" />

                    <x-form.input name="place_of_birth" :label="__('people.students.form.place_of_birth')" :value="$student->place_of_birth" icon="fa-location-dot" />
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <x-form.input name="nationality" :label="__('people.students.form.nationality')" :value="$student->nationality" icon="fa-passport" />
                    <x-form.textarea name="notes" :label="__('people.students.form.notes')" :value="$student->notes" icon="fa-note-sticky" />
                </div>
            </x-ui.card>
        </section>

        <section class="space-y-6">
            <x-ui.card class="space-y-6">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.students.form.schooling_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.students.form.schooling_description') }}</p>
                </div>

                <x-form.select name="entry_academic_year_id" :label="__('people.students.form.academic_year')" icon="fa-calendar-days" required>
                    @foreach ($academicYears as $academicYear)
                        <option value="{{ $academicYear->id }}" @selected((string) old('entry_academic_year_id', $student->entry_academic_year_id ?: $activeYearId) === (string) $academicYear->id)>
                            {{ $academicYear->name }}
                        </option>
                    @endforeach
                </x-form.select>

                <x-form.select name="academic_classroom_id" :label="__('people.students.form.classroom')" icon="fa-school">
                    <option value="">{{ __('people.students.form.no_class') }}</option>
                    @foreach ($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" @selected((string) old('academic_classroom_id', $student->academic_classroom_id) === (string) $classroom->id)>
                            {{ $classroom->name }} @if($classroom->level?->cycle) - {{ $classroom->level->cycle->name }} / {{ $classroom->level->name }} @endif
                        </option>
                    @endforeach
                </x-form.select>

                <x-form.select name="status" :label="__('people.students.form.status')" icon="fa-toggle-on" required x-model="status">
                    @foreach ($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $student->status) === $value)>{{ $label }}</option>
                    @endforeach
                </x-form.select>

                <div x-show="['suspendu', 'transfere', 'radie'].includes(status)" x-cloak>
                    <x-form.textarea
                        name="status_reason"
                        :label="__('people.students.form.status_reason')"
                        :value="$student->status_reason"
                        icon="fa-circle-info"
                    />
                </div>
            </x-ui.card>

            @if (! $isEdit)
                <x-ui.card class="space-y-6">
                    <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.students.form.guardian_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.students.form.guardian_description') }}</p>
                    </div>

                    @if ($existingGuardians->count() > 0)
                        <x-form.select name="existing_guardian_id" :label="__('people.students.form.existing_guardian')" icon="fa-people-roof" x-model="useExistingGuardian">
                            <option value="">{{ __('people.students.form.new_guardian') }}</option>
                            @foreach ($existingGuardians as $guardian)
                                <option value="{{ $guardian->id }}" @selected((string) old('existing_guardian_id') === (string) $guardian->id)>
                                    {{ $guardian->fullName() }} @if($guardian->phone) - {{ $guardian->phone }} @endif
                                </option>
                            @endforeach
                        </x-form.select>
                    @endif

                    <div x-show="!useExistingGuardian" x-cloak class="space-y-5">
                        <div class="grid gap-5 md:grid-cols-2">
                            <x-form.input name="guardian_last_name" :label="__('people.students.form.guardian_last_name')" icon="fa-user" />
                            <x-form.input name="guardian_first_names" :label="__('people.students.form.guardian_first_names')" icon="fa-user" />
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <x-form.select name="guardian_gender" :label="__('people.students.form.guardian_gender')" icon="fa-venus-mars">
                                <option value="">{{ __('people.students.index.filters.all_genders') }}</option>
                                @foreach ($guardianGenderOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('guardian_gender') === $value)>{{ $label }}</option>
                                @endforeach
                            </x-form.select>
                            <x-form.select name="guardian_relationship_type" :label="__('people.students.form.relationship_type')" icon="fa-link" required>
                                <option value="">{{ __('people.students.form.relationship_type') }}</option>
                                @foreach ($relationshipOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('guardian_relationship_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </x-form.select>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <x-form.input name="guardian_phone" :label="__('people.students.form.guardian_phone')" icon="fa-phone" />
                            <x-form.input name="guardian_email" type="email" :label="__('people.students.form.guardian_email')" icon="fa-envelope" />
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <x-form.input name="guardian_address" :label="__('people.students.form.guardian_address')" icon="fa-location-dot" />
                            <x-form.input name="guardian_profession" :label="__('people.students.form.guardian_profession')" icon="fa-briefcase" />
                        </div>

                        <div class="space-y-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                            <label class="flex items-center gap-3">
                                <input type="checkbox" name="guardian_is_primary_contact" value="1" class="form-checkbox" @checked(old('guardian_is_primary_contact', true))>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('people.students.form.primary_contact') }}</span>
                            </label>

                            <label class="flex items-center gap-3">
                                <input type="checkbox" name="guardian_is_financial_responsible" value="1" class="form-checkbox" @checked(old('guardian_is_financial_responsible'))>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('people.students.form.financial_responsible') }}</span>
                            </label>

                            <label class="flex items-center gap-3">
                                <input type="checkbox" name="guardian_can_receive_notifications" value="1" class="form-checkbox" @checked(old('guardian_can_receive_notifications', true))>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('people.students.form.notifications') }}</span>
                            </label>

                            <label class="flex items-center gap-3">
                                <input type="checkbox" name="guardian_can_pickup_child" value="1" class="form-checkbox" @checked(old('guardian_can_pickup_child', true))>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('people.students.form.pickup') }}</span>
                            </label>
                        </div>

                        <x-form.textarea name="guardian_notes" :label="__('people.students.form.guardian_notes')" icon="fa-note-sticky" />
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('people.students.form.primary_hint') }}</p>
                    </div>
                </x-ui.card>
            @else
                <x-ui.card class="space-y-4">
                    <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('people.students.form.guardian_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('people.students.form.guardian_description') }}</p>
                    </div>

                    <x-ui.alert tone="info" :title="__('people.students.form.guardian_title')">
                        {{ __('people.students.show.attach_action') }} depuis la fiche élève pour ajouter ou modifier les responsables liés.
                    </x-ui.alert>
                </x-ui.card>
            @endif

            <div class="flex flex-wrap gap-3">
                <x-ui.button type="submit">
                    {{ $isEdit ? __('people.students.form.submit_edit') : __('people.students.form.submit_create') }}
                </x-ui.button>
                <x-ui.button :href="route('people.students.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
            </div>
        </section>
    </form>
</x-app-layout>
