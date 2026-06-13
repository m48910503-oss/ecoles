<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="$isEdit ? __('academic.conduct.form.edit_title') : __('academic.conduct.form.create_title')"
            :description="__('academic.conduct.form.description')"
        >
            <x-slot:actions>
                <x-ui.button :href="route('academic.conduct.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
                <x-ui.button type="submit" form="conduct-form">
                    {{ $isEdit ? __('academic.conduct.form.submit_edit') : __('academic.conduct.form.submit_create') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <form id="conduct-form" method="POST" action="{{ $isEdit ? route('academic.conduct.update', $conductSetting) : route('academic.conduct.store') }}" class="space-y-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <x-ui.card class="space-y-6">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.conduct.form.details_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.conduct.form.details_description') }}</p>
                </div>

                @if ($conductSubjects->isEmpty())
                    <x-ui.alert tone="info">{{ __('academic.conduct.form.empty_subjects') }}</x-ui.alert>
                @endif

                <div class="grid gap-5 md:grid-cols-2">
                    @if ($canChooseSchool)
                        <x-form.select name="school_id" :label="__('academic.conduct.form.school')" icon="fa-school">
                            @foreach ($schools as $school)
                                <option value="{{ $school->id }}" @selected((int) old('school_id', $currentSchoolId) === $school->id)>{{ $school->name }}</option>
                            @endforeach
                        </x-form.select>
                    @endif

                    <x-form.select name="academic_year_id" :label="__('academic.conduct.form.academic_year')" icon="fa-calendar-days">
                        @foreach ($academicYears as $academicYear)
                            <option value="{{ $academicYear->id }}" @selected((int) old('academic_year_id', $conductSetting->academic_year_id) === $academicYear->id)>{{ $academicYear->name }}</option>
                        @endforeach
                    </x-form.select>

                    <x-form.select name="level_id" :label="__('academic.conduct.form.level')" icon="fa-layer-group">
                        @foreach ($levels as $level)
                            <option value="{{ $level->id }}" @selected((int) old('level_id', $conductSetting->level_id) === $level->id)>{{ $level->name }} - {{ $level->cycle->name }}</option>
                        @endforeach
                    </x-form.select>

                    <x-form.select name="academic_subject_id" :label="__('academic.conduct.form.subject')" icon="fa-scale-balanced">
                        @foreach ($conductSubjects as $conductSubject)
                            <option value="{{ $conductSubject->id }}" @selected((int) old('academic_subject_id', $conductSetting->academic_subject_id) === $conductSubject->id)>{{ $conductSubject->name }}</option>
                        @endforeach
                    </x-form.select>
                </div>
            </x-ui.card>

            <div class="space-y-6">
                <x-ui.card class="space-y-5">
                    <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.conduct.form.settings_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.conduct.form.settings_description') }}</p>
                    </div>

                    <x-form.input name="base_score" type="number" step="0.25" min="0" max="100" :label="__('academic.conduct.form.base_score')" :value="$conductSetting->base_score ?? 20" icon="fa-hashtag" required />
                    <x-form.textarea name="notes" :label="__('academic.conduct.form.notes')" :value="$conductSetting->notes" icon="fa-align-left" />

                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <input type="hidden" name="deductions_from_sanctions_only" value="0">
                        <input type="checkbox" name="deductions_from_sanctions_only" value="1" class="form-checkbox" @checked(old('deductions_from_sanctions_only', $conductSetting->deductions_from_sanctions_only ?? true))>
                        <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <i class="fa-solid fa-gavel text-amber-500"></i>
                            {{ __('academic.conduct.form.deductions_only_label') }}
                        </span>
                    </label>

                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-checkbox" @checked(old('is_active', $conductSetting->is_active ?? true))>
                        <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <i class="fa-solid fa-toggle-on text-sky-500"></i>
                            {{ __('academic.conduct.form.active_label') }}
                        </span>
                    </label>
                </x-ui.card>

                <div class="flex flex-wrap gap-3">
                    <x-ui.button type="submit">
                        {{ $isEdit ? __('academic.conduct.form.submit_edit') : __('academic.conduct.form.submit_create') }}
                    </x-ui.button>
                    <x-ui.button :href="route('academic.conduct.index')" variant="secondary">
                        {{ __('core.common.actions.back') }}
                    </x-ui.button>
                </div>
            </div>
        </section>
    </form>
</x-app-layout>
