<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="$isEdit ? __('academic.periods.form.edit_title') : __('academic.periods.form.create_title')"
            :description="__('academic.periods.form.description')"
        >
            <x-slot:actions>
                <x-ui.button :href="route('academic.periods.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
                <x-ui.button type="submit" form="period-form">
                    {{ $isEdit ? __('academic.periods.form.submit_edit') : __('academic.periods.form.submit_create') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <form
        id="period-form"
        method="POST"
        action="{{ $isEdit ? route('academic.periods.update', $period) : route('academic.periods.store') }}"
        class="space-y-6"
    >
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <x-ui.card class="space-y-6">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.periods.form.details_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.periods.form.details_description') }}</p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <x-form.select name="academic_calendar_id" :label="__('academic.common.calendar')" icon="fa-calendar-week">
                        <option value="">--</option>
                        @foreach ($calendars as $calendar)
                            <option value="{{ $calendar->id }}" @selected((int) old('academic_calendar_id', $period->academic_calendar_id ?? $period->configuration?->calendar?->id) === $calendar->id)>
                                {{ $calendar->academicYear->name }} - {{ $calendar->name }} - {{ $calendar->scope_type->label() }}
                                @if ($calendar->scopeReferenceLabel())
                                    ({{ $calendar->scopeReferenceLabel() }})
                                @endif
                            </option>
                        @endforeach
                    </x-form.select>

                    <x-form.input name="name" :label="__('academic.periods.form.name')" :value="$period->name" icon="fa-signature" required />
                    <x-form.input name="position" type="number" :label="__('academic.periods.form.position')" :value="$period->position" icon="fa-hashtag" min="1" required />
                    <x-form.input name="start_date" type="date" :label="__('academic.periods.form.start_date')" :value="optional($period->start_date)->format('Y-m-d')" icon="fa-calendar-day" required />
                    <x-form.input name="end_date" type="date" :label="__('academic.periods.form.end_date')" :value="optional($period->end_date)->format('Y-m-d')" icon="fa-calendar-check" required />
                </div>
            </x-ui.card>

            <div class="space-y-6">
                <x-ui.card class="space-y-5">
                    <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.periods.form.settings_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.periods.form.settings_description') }}</p>
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-checkbox" @checked(old('is_active', $period->is_active))>
                        <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <i class="fa-solid fa-toggle-on text-emerald-500"></i>
                            {{ __('academic.periods.form.active_label') }}
                        </span>
                    </label>

                    <x-ui.alert tone="info">{{ __('academic.periods.form.lock_hint') }}</x-ui.alert>
                </x-ui.card>

                <div class="flex flex-wrap gap-3">
                    <x-ui.button type="submit">
                        {{ $isEdit ? __('academic.periods.form.submit_edit') : __('academic.periods.form.submit_create') }}
                    </x-ui.button>
                    <x-ui.button :href="route('academic.periods.index')" variant="secondary">
                        {{ __('core.common.actions.back') }}
                    </x-ui.button>
                </div>
            </div>
        </section>
    </form>
</x-app-layout>
