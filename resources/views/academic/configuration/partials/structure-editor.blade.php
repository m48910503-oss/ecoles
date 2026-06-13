@php
    use App\Enums\Academic\AcademicPeriodType;
    use App\Enums\Academic\SchoolDayType;

    $configurationModel = $configurationModel ?? null;
    $dayConfigurationModel = $dayConfigurationModel ?? null;
    $periodTypes = $periodTypes ?? AcademicPeriodType::cases();
    $schoolDayTypes = $schoolDayTypes ?? SchoolDayType::cases();
    $selectedPeriodType = old('period_type', $configurationModel?->period_type?->value ?? AcademicPeriodType::Trimester->value);
    $selectedDayType = old('school_day_type', $dayConfigurationModel?->day_type?->value ?? $configurationModel?->school_day_type?->value ?? SchoolDayType::Continuous->value);
@endphp

<div
    x-data="{
        dayType: @js($selectedDayType),
    }"
    class="space-y-6"
>
    <x-ui.card class="space-y-6">
        <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.configuration.form.details_title') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.configuration.form.details_description') }}</p>
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            <x-form.select name="period_type" :label="__('academic.configuration.form.period_type')" icon="fa-layer-group">
                @foreach ($periodTypes as $periodType)
                    <option value="{{ $periodType->value }}" @selected($selectedPeriodType === $periodType->value)>{{ $periodType->label() }}</option>
                @endforeach
            </x-form.select>

            <x-form.select name="school_day_type" :label="__('academic.configuration.form.school_day_type')" icon="fa-clock" x-model="dayType">
                @foreach ($schoolDayTypes as $schoolDayType)
                    <option value="{{ $schoolDayType->value }}" @selected($selectedDayType === $schoolDayType->value)>{{ $schoolDayType->label() }}</option>
                @endforeach
            </x-form.select>
        </div>

        <x-ui.alert tone="info">
            {{ __('academic.configuration.form.period_generation_hint') }}
        </x-ui.alert>
    </x-ui.card>

    <x-ui.card class="space-y-6">
        <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.configuration.form.day_schedule_title') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.configuration.form.day_schedule_description') }}</p>
        </div>

        <div x-show="dayType === 'continuous'" x-cloak class="grid gap-4 md:grid-cols-2">
            <x-form.input name="start_time" type="time" :label="__('academic.day_config.form.start_time')" :value="old('start_time', $dayConfigurationModel?->start_time)" icon="fa-clock" />
            <x-form.input name="end_time" type="time" :label="__('academic.day_config.form.end_time')" :value="old('end_time', $dayConfigurationModel?->end_time)" icon="fa-clock" />
        </div>

        <div x-show="dayType === 'discontinuous'" x-cloak class="grid gap-4 md:grid-cols-2">
            <x-form.input name="morning_start_time" type="time" :label="__('academic.day_config.form.morning_start_time')" :value="old('morning_start_time', $dayConfigurationModel?->morning_start_time)" icon="fa-sun" />
            <x-form.input name="morning_end_time" type="time" :label="__('academic.day_config.form.morning_end_time')" :value="old('morning_end_time', $dayConfigurationModel?->morning_end_time)" icon="fa-sun" />
            <x-form.input name="afternoon_start_time" type="time" :label="__('academic.day_config.form.afternoon_start_time')" :value="old('afternoon_start_time', $dayConfigurationModel?->afternoon_start_time)" icon="fa-cloud-sun" />
            <x-form.input name="afternoon_end_time" type="time" :label="__('academic.day_config.form.afternoon_end_time')" :value="old('afternoon_end_time', $dayConfigurationModel?->afternoon_end_time)" icon="fa-cloud-sun" />
        </div>

    </x-ui.card>
</div>
