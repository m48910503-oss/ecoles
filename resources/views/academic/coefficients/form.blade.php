<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="$isEdit ? __('academic.coefficients.form.edit_title') : __('academic.coefficients.form.create_title')"
            :description="__('academic.coefficients.form.description')"
        >
            <x-slot:actions>
                <x-ui.button :href="route('academic.coefficients.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
                <x-ui.button type="submit" form="coefficient-form">
                    {{ $isEdit ? __('academic.coefficients.form.submit_edit') : __('academic.coefficients.form.submit_create') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <form id="coefficient-form" method="POST" action="{{ $isEdit ? route('academic.coefficients.update', $coefficient) : route('academic.coefficients.store') }}" class="space-y-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <x-ui.card class="space-y-6">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.coefficients.form.details_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.coefficients.form.details_description') }}</p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    @if ($canChooseSchool)
                        <x-form.select name="school_id" :label="__('academic.coefficients.form.school')" icon="fa-school">
                            @foreach ($schools as $school)
                                <option value="{{ $school->id }}" @selected((int) old('school_id', $currentSchoolId) === $school->id)>{{ $school->name }}</option>
                            @endforeach
                        </x-form.select>
                    @endif

                    <x-form.select name="academic_year_id" :label="__('academic.coefficients.form.academic_year')" icon="fa-calendar-days">
                        @foreach ($academicYears as $academicYear)
                            <option value="{{ $academicYear->id }}" @selected((int) old('academic_year_id', $coefficient->academic_year_id) === $academicYear->id)>{{ $academicYear->name }}</option>
                        @endforeach
                    </x-form.select>

                    <x-form.select name="level_id" :label="__('academic.coefficients.form.level')" icon="fa-layer-group">
                        @foreach ($levels as $level)
                            <option value="{{ $level->id }}" @selected((int) old('level_id', $coefficient->level_id) === $level->id)>{{ $level->name }} - {{ $level->cycle->name }}</option>
                        @endforeach
                    </x-form.select>

                    <x-form.select name="series_id" :label="__('academic.coefficients.form.series')" icon="fa-sitemap">
                        <option value="">{{ __('academic.coefficients.form.series_hint') }}</option>
                        @foreach ($series as $serie)
                            <option value="{{ $serie->id }}" @selected((int) old('series_id', $coefficient->series_id) === $serie->id)>{{ $serie->name }} - {{ $serie->cycle->name }}</option>
                        @endforeach
                    </x-form.select>

                    <x-form.select name="academic_subject_id" :label="__('academic.coefficients.form.subject')" icon="fa-book-open">
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected((int) old('academic_subject_id', $coefficient->academic_subject_id) === $subject->id)>{{ $subject->name }}</option>
                        @endforeach
                    </x-form.select>
                </div>
            </x-ui.card>

            <div class="space-y-6">
                <x-ui.card class="space-y-5">
                    <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.coefficients.form.settings_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.coefficients.form.settings_description') }}</p>
                    </div>

                    <x-form.input name="coefficient" type="number" step="0.25" min="0.25" max="99.99" :label="__('academic.coefficients.form.coefficient')" :value="$coefficient->coefficient" icon="fa-square-root-variable" required />

                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <input type="hidden" name="is_optional" value="0">
                        <input type="checkbox" name="is_optional" value="1" class="form-checkbox" @checked(old('is_optional', $coefficient->is_optional))>
                        <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <i class="fa-solid fa-toggle-on text-amber-500"></i>
                            {{ __('academic.coefficients.form.optional_label') }}
                        </span>
                    </label>

                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-checkbox" @checked(old('is_active', $coefficient->is_active ?? true))>
                        <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <i class="fa-solid fa-toggle-on text-sky-500"></i>
                            {{ __('academic.coefficients.form.active_label') }}
                        </span>
                    </label>
                </x-ui.card>

                <div class="flex flex-wrap gap-3">
                    <x-ui.button type="submit">
                        {{ $isEdit ? __('academic.coefficients.form.submit_edit') : __('academic.coefficients.form.submit_create') }}
                    </x-ui.button>
                    <x-ui.button :href="route('academic.coefficients.index')" variant="secondary">
                        {{ __('core.common.actions.back') }}
                    </x-ui.button>
                </div>
            </div>
        </section>
    </form>
</x-app-layout>
