<form
    id="academic-year-step-3-form"
    method="POST"
    action="{{ route('core.academic-years.create.step3.store') }}"
    class="space-y-6"
>
    @csrf

    <x-ui.card>
        <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.academic_years.form.activation_title') }}</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('core.academic_years.form.activation_description') }}</p>
        </div>

        <div class="mt-5 space-y-4">
            @if ($hasActiveAcademicYear)
                <x-ui.alert tone="error">
                    {{ __('core.academic_years.form.active_blocked_hint') }}
                </x-ui.alert>
            @endif

            <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="form-checkbox" @checked(old('is_active', $wizardState['is_active'] ?? false)) @disabled($hasActiveAcademicYear)>
                <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                    <i class="fa-solid fa-toggle-on text-sky-500"></i>
                    {{ __('core.academic_years.form.active_label') }}
                </span>
            </label>

            <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                <input type="hidden" name="enrollment_open" value="0">
                <input type="checkbox" name="enrollment_open" value="1" class="form-checkbox" @checked(old('enrollment_open', $wizardState['enrollment_open'] ?? false))>
                <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                    <i class="fa-solid fa-door-open text-emerald-500"></i>
                    {{ __('core.academic_years.form.enrollment_label') }}
                </span>
            </label>

            @if ($canManageMultiSchool)
                <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700/80 dark:bg-slate-950/75">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">{{ __('core.settings.organization.mode_label') }}</p>

                    <label class="mt-4 flex items-start gap-3 rounded-2xl border border-gray-200 bg-white/80 p-4 dark:border-slate-700/80 dark:bg-slate-950/90">
                        <input type="checkbox" name="multi_school_enabled" value="1" class="form-checkbox mt-1" @checked(old('multi_school_enabled', $wizardState['multi_school_enabled'] ?? false))>
                        <span>
                            <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                                <i class="fa-solid fa-building-columns text-slate-500"></i>
                                {{ __('core.academic_years.form.multi_school_label') }}
                            </span>
                            <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ __('core.academic_years.form.multi_school_help') }}</span>
                        </span>
                    </label>
                </div>
            @endif
        </div>
    </x-ui.card>

    <div class="flex flex-wrap gap-3">
        <x-ui.button :href="route('core.academic-years.create.step2')" variant="secondary">
            {{ __('core.common.actions.previous') }}
        </x-ui.button>
        <x-ui.button type="submit">
            {{ __('core.common.actions.next') }}
        </x-ui.button>
    </div>
</form>
