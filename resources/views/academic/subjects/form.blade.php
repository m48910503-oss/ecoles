<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="$isEdit ? __('academic.subjects.form.edit_title') : __('academic.subjects.form.create_title')"
            :description="__('academic.subjects.form.description')"
        >
            <x-slot:actions>
                <x-ui.button :href="route('academic.subjects.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
                <x-ui.button type="submit" form="subject-form">
                    {{ $isEdit ? __('academic.subjects.form.submit_edit') : __('academic.subjects.form.submit_create') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <form id="subject-form" method="POST" action="{{ $isEdit ? route('academic.subjects.update', $subject) : route('academic.subjects.store') }}" class="space-y-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <x-ui.card class="space-y-6">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.subjects.form.details_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.subjects.form.details_description') }}</p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    @if ($canChooseSchool)
                        <x-form.select name="school_id" :label="__('academic.subjects.form.school')" icon="fa-school" class="md:col-span-2">
                            @foreach ($schools as $school)
                                <option value="{{ $school->id }}" @selected((int) old('school_id', $currentSchoolId) === $school->id)>{{ $school->name }}</option>
                            @endforeach
                        </x-form.select>
                    @endif

                    <x-form.input name="name" :label="__('academic.subjects.form.name')" :value="$subject->name" icon="fa-book-open" class="md:col-span-2" required />
                    <x-form.input name="short_name" :label="__('academic.subjects.form.short_name')" :value="$subject->short_name" icon="fa-font" />

                    <x-form.select name="category" :label="__('academic.subjects.form.category')" icon="fa-tags" :placeholder="__('academic.subjects.form.category_placeholder')">
                        @foreach ($categoryOptions as $categoryOption)
                            <option value="{{ $categoryOption->value }}" @selected(old('category', $subject->category?->value) === $categoryOption->value)>{{ $categoryOption->label() }}</option>
                        @endforeach
                    </x-form.select>

                    <x-form.textarea name="description" :label="__('academic.subjects.form.description_label')" :value="$subject->description" icon="fa-align-left" class="md:col-span-2" />
                </div>
            </x-ui.card>

            <div class="space-y-6">
                <x-ui.card class="space-y-5">
                    <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.subjects.form.governance_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.subjects.form.governance_description') }}</p>
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <input type="hidden" name="is_optional_by_default" value="0">
                        <input type="checkbox" name="is_optional_by_default" value="1" class="form-checkbox" @checked(old('is_optional_by_default', $subject->is_optional_by_default))>
                        <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <i class="fa-solid fa-toggle-on text-amber-500"></i>
                            {{ __('academic.subjects.form.optional_label') }}
                        </span>
                    </label>

                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <input type="hidden" name="is_conduct" value="0">
                        <input type="checkbox" name="is_conduct" value="1" class="form-checkbox" @checked(old('is_conduct', $subject->is_conduct))>
                        <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <i class="fa-solid fa-scale-balanced text-violet-500"></i>
                            {{ __('academic.subjects.form.conduct_label') }}
                        </span>
                    </label>

                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-checkbox" @checked(old('is_active', $subject->is_active ?? true))>
                        <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <i class="fa-solid fa-toggle-on text-sky-500"></i>
                            {{ __('academic.subjects.form.active_label') }}
                        </span>
                    </label>
                </x-ui.card>

                <div class="flex flex-wrap gap-3">
                    <x-ui.button type="submit">
                        {{ $isEdit ? __('academic.subjects.form.submit_edit') : __('academic.subjects.form.submit_create') }}
                    </x-ui.button>
                    <x-ui.button :href="route('academic.subjects.index')" variant="secondary">
                        {{ __('core.common.actions.back') }}
                    </x-ui.button>
                </div>
            </div>
        </section>
    </form>
</x-app-layout>
