<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="$isEdit ? __('academic.day_config.form.edit_title') : __('academic.day_config.form.create_title')"
            :description="__('academic.day_config.form.description')"
        >
            <x-slot:actions>
                <x-ui.button :href="route('academic.day-configurations.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
                <x-ui.button type="submit" form="day-configuration-form">
                    {{ $isEdit ? __('academic.day_config.form.submit_edit') : __('academic.day_config.form.submit_create') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <form
        id="day-configuration-form"
        method="POST"
        action="{{ $isEdit ? route('academic.day-configurations.update', $dayConfiguration) : route('academic.day-configurations.store') }}"
        class="space-y-6"
        x-data="{
            scopeType: '{{ old('scope_type', $dayConfiguration->scope_type?->value ?? \App\Enums\Academic\CalendarScopeType::Global->value) }}',
            dayType: '{{ old('day_type', $dayConfiguration->day_type?->value ?? \App\Enums\Academic\SchoolDayType::Continuous->value) }}'
        }"
    >
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <section class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
            <x-ui.card class="space-y-6">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.day_config.form.details_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.day_config.form.details_description') }}</p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    @if ($canChooseSchool)
                        <x-form.select name="school_id" :label="__('academic.day_config.form.school')" icon="fa-school">
                            @foreach ($schools as $school)
                                <option value="{{ $school->id }}" @selected((int) old('school_id', $currentSchoolId) === $school->id)>{{ $school->name }}</option>
                            @endforeach
                        </x-form.select>
                    @endif

                    <x-form.select name="academic_year_id" :label="__('academic.day_config.form.academic_year')" icon="fa-calendar-days">
                        @foreach ($academicYears as $academicYear)
                            <option value="{{ $academicYear->id }}" @selected((int) old('academic_year_id', $dayConfiguration->academic_year_id) === $academicYear->id)>{{ $academicYear->name }}</option>
                        @endforeach
                    </x-form.select>

                    <x-form.select name="scope_type" :label="__('academic.day_config.form.scope_type')" icon="fa-crosshairs" x-model="scopeType">
                        @foreach ($scopeOptions as $scopeOption)
                            <option value="{{ $scopeOption->value }}">{{ $scopeOption->label() }}</option>
                        @endforeach
                    </x-form.select>

                    <div x-show="scopeType === 'cycle'" x-cloak>
                        <x-form.select name="cycle_id" :label="__('academic.day_config.form.cycle')" icon="fa-diagram-project">
                            <option value="">--</option>
                            @foreach ($cycles as $cycle)
                                <option value="{{ $cycle->id }}" @selected((int) old('cycle_id', $dayConfiguration->cycle_id) === $cycle->id)>{{ $cycle->name }}</option>
                            @endforeach
                        </x-form.select>
                    </div>

                    <x-form.select name="day_type" :label="__('academic.day_config.form.day_type')" icon="fa-clock" x-model="dayType">
                        @foreach ($dayTypeOptions as $dayTypeOption)
                            <option value="{{ $dayTypeOption->value }}">{{ $dayTypeOption->label() }}</option>
                        @endforeach
                    </x-form.select>
                </div>
            </x-ui.card>

            <div class="space-y-6">
                <x-ui.card class="space-y-5">
                    <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.day_config.form.hours_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.day_config.form.hours_description') }}</p>
                    </div>

                    <div x-show="dayType === 'continuous'" x-cloak class="grid gap-5 md:grid-cols-2">
                        <x-form.input name="start_time" type="time" :label="__('academic.day_config.form.start_time')" :value="$dayConfiguration->start_time" icon="fa-hourglass-start" />
                        <x-form.input name="end_time" type="time" :label="__('academic.day_config.form.end_time')" :value="$dayConfiguration->end_time" icon="fa-hourglass-end" />
                    </div>

                    <div x-show="dayType === 'discontinuous'" x-cloak class="grid gap-5 md:grid-cols-2">
                        <x-form.input name="morning_start_time" type="time" :label="__('academic.day_config.form.morning_start_time')" :value="$dayConfiguration->morning_start_time" icon="fa-sun" />
                        <x-form.input name="morning_end_time" type="time" :label="__('academic.day_config.form.morning_end_time')" :value="$dayConfiguration->morning_end_time" icon="fa-sun" />
                        <x-form.input name="afternoon_start_time" type="time" :label="__('academic.day_config.form.afternoon_start_time')" :value="$dayConfiguration->afternoon_start_time" icon="fa-cloud-sun" />
                        <x-form.input name="afternoon_end_time" type="time" :label="__('academic.day_config.form.afternoon_end_time')" :value="$dayConfiguration->afternoon_end_time" icon="fa-cloud-sun" />
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-checkbox" @checked(old('is_active', $dayConfiguration->is_active ?? true))>
                        <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <i class="fa-solid fa-toggle-on text-emerald-500"></i>
                            {{ __('academic.day_config.form.active_label') }}
                        </span>
                    </label>
                </x-ui.card>

                <div class="flex flex-wrap gap-3">
                    <x-ui.button type="submit">
                        {{ $isEdit ? __('academic.day_config.form.submit_edit') : __('academic.day_config.form.submit_create') }}
                    </x-ui.button>
                    <x-ui.button :href="route('academic.day-configurations.index')" variant="secondary">
                        {{ __('core.common.actions.back') }}
                    </x-ui.button>
                </div>
            </div>
        </section>
    </form>
</x-app-layout>
