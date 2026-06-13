<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="$isEdit ? __('core.academic_years.form.edit_title') : __('core.academic_years.form.create_title')"
            :description="__('core.academic_years.form.description')"
        >
            <x-slot:actions>
                <x-ui.button type="submit" form="academic-year-form">
                    {{ $isEdit ? __('core.academic_years.form.submit_edit') : __('core.academic_years.form.submit_create') }}
                </x-ui.button>
                <x-ui.button :href="route('core.academic-years.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    @php
        $startPreview = old('start_date', optional($academicYear->start_date)->format('Y-m-d'));
        $endPreview = old('end_date', optional($academicYear->end_date)->format('Y-m-d'));
        $configureNow = old('configure_now', $configureNowDefault ?? false);
    @endphp

    <form
        id="academic-year-form"
        method="POST"
        action="{{ $isEdit ? route('core.academic-years.update', $academicYear) : route('core.academic-years.store') }}"
        class="space-y-6"
        x-data="{
            startDate: @js($startPreview),
            endDate: @js($endPreview),
            configureNow: @js((bool) $configureNow),
            get yearLabel() {
                if (!this.startDate || !this.endDate) {
                    return '—';
                }

                return `${this.startDate.slice(0, 4)}-${this.endDate.slice(0, 4)}`;
            },
        }"
    >
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <div class="space-y-6">
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
                            :value="optional($academicYear->start_date)->format('Y-m-d')"
                            icon="fa-calendar-day"
                            x-model="startDate"
                            required
                        />

                        <x-form.input
                            name="end_date"
                            type="date"
                            :label="__('core.academic_years.form.end_date')"
                            :value="optional($academicYear->end_date)->format('Y-m-d')"
                            icon="fa-calendar-check"
                            x-model="endDate"
                            required
                        />

                        <x-form.textarea name="notes" :label="__('core.academic_years.form.notes')" :value="$academicYear->notes" icon="fa-note-sticky" class="md:col-span-2" />
                        <div class="md:col-span-2">
                            <x-ui.alert tone="slate">
                                {{ __('core.academic_years.form.generated_year_label') }} <span class="font-semibold" x-text="yearLabel"></span>
                            </x-ui.alert>
                        </div>
                    </div>
                </x-ui.card>

                @include('core.academic-years.partials.cycle-selector', [
                    'cycles' => $cycles,
                    'selectedCycles' => $selectedCycles,
                ])

                <div class="space-y-4">
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

                    @if ($isEdit)
                        <x-ui.card class="space-y-5">
                            <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.academic_years.form.calendar_periods_title') }}</h2>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('core.academic_years.form.calendar_periods_description') }}</p>
                            </div>

                            <x-ui.alert tone="slate">{{ __('core.academic_years.form.calendar_periods_hint') }}</x-ui.alert>

                            <x-ui.button :href="route('academic.calendars.periods.index', array_filter([
                                'academic_year_id' => $academicYear->id,
                                'school_id' => $currentSchoolId ?? null,
                            ]))" variant="secondary">
                                {{ __('core.academic_years.form.calendar_periods_action') }}
                            </x-ui.button>
                        </x-ui.card>
                    @endif

                    <div x-show="configureNow" x-cloak>
                        @include('academic.configuration.partials.structure-editor', [
                            'configurationModel' => $configuration,
                            'dayConfigurationModel' => $dayConfiguration,
                            'periodTypes' => $periodTypes,
                            'schoolDayTypes' => $schoolDayTypes,
                        ])
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <x-ui.card>
                    <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.academic_years.form.activation_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('core.academic_years.form.activation_description') }}</p>
                    </div>

                    <div class="mt-5 space-y-4">
                        @if (! $isEdit && $hasActiveAcademicYear)
                            <x-ui.alert tone="slate">
                                {{ __('core.academic_years.form.active_blocked_hint') }}
                            </x-ui.alert>
                        @endif

                        <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                            <input type="checkbox" name="is_active" value="1" class="form-checkbox" @checked(old('is_active', $academicYear->is_active)) @disabled(! $isEdit && $hasActiveAcademicYear)>
                            <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                                <i class="fa-solid fa-toggle-on text-sky-500"></i>
                                {{ __('core.academic_years.form.active_label') }}
                            </span>
                        </label>

                        <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                            <input type="checkbox" name="enrollment_open" value="1" class="form-checkbox" @checked(old('enrollment_open', $academicYear->enrollment_open))>
                            <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                                <i class="fa-solid fa-door-open text-emerald-500"></i>
                                {{ __('core.academic_years.form.enrollment_label') }}
                            </span>
                        </label>

                        @can('manageMultiSchool', \App\Models\SystemSetting::class)
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700/80 dark:bg-slate-950/75">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">{{ __('core.settings.organization.mode_label') }}</p>

                                <input type="hidden" name="multi_school_enabled" value="0">
                                <label class="mt-4 flex items-start gap-3 rounded-2xl border border-slate-200 bg-white/85 p-4 dark:border-slate-700/80 dark:bg-slate-950/90">
                                    <input type="checkbox" name="multi_school_enabled" value="1" class="form-checkbox mt-1" @checked(old('multi_school_enabled', $academicYear->multi_school_enabled))>
                                    <span>
                                        <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                                            <i class="fa-solid fa-building-columns text-slate-500"></i>
                                            {{ __('core.academic_years.form.multi_school_label') }}
                                        </span>
                                        <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ __('core.academic_years.form.multi_school_help') }}</span>
                                    </span>
                                </label>
                            </div>
                        @else
                            <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">{{ __('core.settings.organization.mode_label') }}</p>
                                <div class="mt-3">
                                    <x-ui.badge :tone="$academicYear->multi_school_enabled ? 'sky' : 'slate'">
                                        {{ $academicYear->multi_school_enabled ? __('core.settings.organization.modes.multi') : __('core.settings.organization.modes.mono') }}
                                    </x-ui.badge>
                                </div>
                            </div>
                        @endcan
                    </div>
                </x-ui.card>

                <div class="flex flex-wrap gap-3">
                    <x-ui.button type="submit">
                        {{ $isEdit ? __('core.academic_years.form.submit_edit') : __('core.academic_years.form.submit_create') }}
                    </x-ui.button>
                    <x-ui.button :href="route('core.academic-years.index')" variant="secondary">
                        {{ __('core.common.actions.back') }}
                    </x-ui.button>
                </div>
            </div>
        </section>
    </form>
</x-app-layout>
