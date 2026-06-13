@php
    $startPreview = old('start_date', $wizardState['start_date'] ?? null);
    $endPreview = old('end_date', $wizardState['end_date'] ?? null);
@endphp

<form
    id="academic-year-step-1-form"
    method="POST"
    action="{{ route('core.academic-years.create.step1.store') }}"
    class="space-y-6"
    x-data="{
        startDate: @js($startPreview),
        endDate: @js($endPreview),
        get yearLabel() {
            if (!this.startDate || !this.endDate) {
                return '—';
            }

            return `${this.startDate.slice(0, 4)}-${this.endDate.slice(0, 4)}`;
        },
    }"
>
    @csrf

    <x-ui.card class="space-y-6">
        <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.academic_years.form.details_title') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('core.academic_years.form.details_description') }}</p>
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            <x-form.input
                name="start_date"
                type="date"
                :label="__('core.academic_years.form.start_date')"
                :value="$startPreview"
                icon="fa-calendar-day"
                x-model="startDate"
                required
            />

            <x-form.input
                name="end_date"
                type="date"
                :label="__('core.academic_years.form.end_date')"
                :value="$endPreview"
                icon="fa-calendar-check"
                x-model="endDate"
                required
            />

            <x-form.textarea name="notes" :label="__('core.academic_years.form.notes')" :value="old('notes', $wizardState['notes'] ?? null)" icon="fa-note-sticky" class="md:col-span-2" />

            <div class="md:col-span-2">
                <x-ui.alert tone="slate">
                    {{ __('core.academic_years.form.generated_year_label') }} <span class="font-semibold" x-text="yearLabel"></span>
                </x-ui.alert>
            </div>
        </div>
    </x-ui.card>

    <div class="flex flex-wrap gap-3">
        <x-ui.button type="submit">
            {{ __('core.common.actions.next') }}
        </x-ui.button>
        <x-ui.button :href="route('core.academic-years.index')" variant="secondary">
            {{ __('core.common.actions.back') }}
        </x-ui.button>
    </div>
</form>
