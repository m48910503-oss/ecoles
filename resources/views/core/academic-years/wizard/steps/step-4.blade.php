@php
    $configureNow = old('configure_now', false);
@endphp

<form
    id="academic-year-step-4-form"
    method="POST"
    action="{{ route('core.academic-years.store') }}"
    class="space-y-6"
    x-data="{
        configureNow: @js((bool) $configureNow),
    }"
>
    @csrf

    @include('core.academic-years.wizard.partials.summary')

    <x-ui.card class="space-y-5">
        <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.academic_years.form.configuration_title') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('core.academic_years.form.configuration_description') }}</p>
        </div>

        <input type="hidden" name="configure_now" value="0">
        <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
            <input type="checkbox" name="configure_now" value="1" class="form-checkbox" x-model="configureNow" @checked($configureNow)>
            <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                <i class="fa-solid fa-sliders text-sky-500"></i>
                {{ __('core.academic_years.form.configure_now_label') }}
            </span>
        </label>

        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('core.academic_years.form.configure_now_hint') }}</p>
    </x-ui.card>

    <div x-show="configureNow" x-cloak>
        <fieldset x-bind:disabled="!configureNow" class="space-y-6">
            @include('academic.configuration.partials.structure-editor', [
                'configurationModel' => $configuration,
                'dayConfigurationModel' => $dayConfiguration,
                'periodTypes' => $periodTypes,
                'schoolDayTypes' => $schoolDayTypes,
            ])
        </fieldset>
    </div>

    <div class="flex flex-wrap gap-3">
        <x-ui.button :href="route('core.academic-years.create.step3')" variant="secondary">
            {{ __('core.common.actions.previous') }}
        </x-ui.button>
        <x-ui.button type="submit">
            {{ __('core.academic_years.form.submit_create') }}
        </x-ui.button>
    </div>
</form>
