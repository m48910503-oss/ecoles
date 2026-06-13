<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('core.settings.edit.title')" :description="__('core.settings.edit.description')">
            <x-slot:actions>
                <x-ui.button type="submit" form="settings-form">
                    <i class="fa-solid fa-floppy-disk text-xs"></i>
                    {{ __('core.settings.edit.save_action') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    @php
        $fieldIcons = [
            'school_name' => 'fa-school',
            'school_phone' => 'fa-phone',
            'support_email' => 'fa-envelope',
            'session_timeout_minutes' => 'fa-stopwatch',
            'password_min_length' => 'fa-key',
            'allow_public_registration' => 'fa-user-plus',
            'minimum_initial_payment' => 'fa-wallet',
            'financial_blocking_enabled' => 'fa-file-invoice-dollar',
        ];
    @endphp

    <div class="space-y-6">
        <form id="settings-form" method="POST" action="{{ route('core.settings.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            @foreach ($settingGroups as $groupKey => $group)
                <x-ui.card as="section">
                    <div class="border-b border-gray-100 pb-4 dark:border-slate-700/70">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $group['title'] }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $group['description'] }}</p>
                    </div>

                    <div class="mt-5 grid gap-5 md:grid-cols-2">
                        @foreach ($group['fields'] as $field)
                            @php
                                $fieldIcon = $fieldIcons[$field['key']] ?? 'fa-sliders';
                            @endphp
                            <div class="{{ $field['type'] === 'boolean' ? 'md:col-span-2' : '' }}">
                                @if ($field['type'] === 'boolean')
                                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-slate-700/75 dark:bg-slate-950/75">
                                        <input
                                            type="checkbox"
                                            name="{{ $groupKey }}[{{ $field['key'] }}]"
                                            value="1"
                                            class="form-checkbox"
                                            @checked((bool) old($groupKey.'.'.$field['key'], $field['value']))
                                        >
                                        <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                                            <i class="fa-solid {{ $fieldIcon }} text-sky-500"></i>
                                            {{ $field['label'] }}
                                        </span>
                                    </label>
                                @else
                                    <x-form.input
                                        name="{{ $groupKey }}[{{ $field['key'] }}]"
                                        :label="$field['label']"
                                        :type="$field['type'] === 'integer' ? 'number' : 'text'"
                                        :value="$field['value']"
                                        :old-key="$groupKey.'.'.$field['key']"
                                        :error-key="$groupKey.'.'.$field['key']"
                                        :icon="$fieldIcon"
                                    />
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            @endforeach

            <div class="flex flex-wrap gap-3">
                <x-ui.button type="submit">
                    {{ __('core.settings.edit.save_action') }}
                </x-ui.button>
            </div>
        </form>

        <x-ui.card as="section" class="space-y-5 overflow-hidden">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.settings.organization.title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('core.settings.organization.description') }}</p>
                </div>

                @if ($activeAcademicYear)
                    <x-ui.badge tone="sky">
                        {{ __('core.settings.organization.active_year_badge') }} · {{ $activeAcademicYear->name }}
                    </x-ui.badge>
                @endif
            </div>

            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div class="min-h-[148px] rounded-[1.7rem] border border-gray-200 bg-gradient-to-b from-white to-slate-50 p-5 shadow-sm shadow-slate-900/5 dark:border-slate-700/85 dark:bg-slate-950/95 dark:shadow-none">
                    <p class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">{{ __('core.settings.organization.mode_label') }}</p>
                    <div class="mt-7">
                        <span class="inline-flex rounded-full bg-sky-100 px-4 py-2 text-sm font-semibold leading-tight text-sky-700 dark:bg-sky-500/15 dark:text-sky-300">
                            {{ $multiSchool['enabled'] ? __('core.settings.organization.modes.multi') : __('core.settings.organization.modes.mono') }}
                        </span>
                    </div>
                </div>

                <div class="min-h-[148px] rounded-[1.7rem] border border-gray-200 bg-gradient-to-b from-white to-slate-50 p-5 shadow-sm shadow-slate-900/5 dark:border-slate-700/85 dark:bg-slate-950/95 dark:shadow-none">
                    <p class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">{{ __('core.settings.organization.default_school_label') }}</p>
                    <p class="mt-7 text-[1.7rem] font-semibold leading-none tracking-tight text-gray-900 dark:text-white">{{ $multiSchool['default_school_name'] }}</p>
                    <p class="mt-4 text-sm uppercase tracking-[0.16em] text-gray-500 dark:text-slate-400">{{ $multiSchool['default_school_code'] }}</p>
                </div>

                <div class="min-h-[148px] rounded-[1.7rem] border border-gray-200 bg-gradient-to-b from-white to-slate-50 p-5 shadow-sm shadow-slate-900/5 dark:border-slate-700/85 dark:bg-slate-950/95 dark:shadow-none">
                    <p class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">{{ __('core.settings.organization.current_school_label') }}</p>
                    <p class="mt-7 text-[1.7rem] font-semibold leading-none tracking-tight text-gray-900 dark:text-white">{{ $multiSchool['current_school_name'] }}</p>
                    <p class="mt-4 text-sm uppercase tracking-[0.16em] text-gray-500 dark:text-slate-400">{{ $multiSchool['current_school_code'] }}</p>
                </div>

                <div class="min-h-[148px] rounded-[1.7rem] border border-gray-200 bg-gradient-to-b from-white to-slate-50 p-5 shadow-sm shadow-slate-900/5 dark:border-slate-700/85 dark:bg-slate-950/95 dark:shadow-none">
                    <p class="text-[0.72rem] font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">{{ __('core.settings.organization.school_count_label') }}</p>
                    <p class="mt-8 text-5xl font-semibold leading-none tracking-tight text-gray-900 dark:text-white">{{ $multiSchool['school_count'] }}</p>
                </div>
            </div>

            <div class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-gray-50/80 px-4 py-4 dark:border-slate-700/85 dark:bg-slate-950/80 md:flex-row md:items-center md:justify-between">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300">
                    {{ $activeAcademicYear ? __('core.settings.organization.driven_by_year', ['year' => $activeAcademicYear->name]) : __('core.settings.organization.no_active_year') }}
                </p>

                <div class="flex flex-wrap gap-3">
                    @can('viewAny', \App\Models\School::class)
                        <x-ui.button :href="route('core.schools.index')" variant="secondary" size="sm">
                            <i class="fa-solid fa-building-columns text-xs"></i>
                            {{ __('core.settings.organization.manage_schools_action') }}
                        </x-ui.button>
                    @endcan

                    @if ($activeAcademicYear)
                        @can('view', $activeAcademicYear)
                            <x-ui.button :href="route('core.academic-years.show', $activeAcademicYear)" variant="secondary" size="sm">
                                <i class="fa-solid fa-calendar-pen text-xs"></i>
                                {{ __('core.settings.organization.edit_active_year_action') }}
                            </x-ui.button>
                        @endcan
                    @else
                        @can('viewAny', \App\Models\AcademicYear::class)
                            <x-ui.button :href="route('core.academic-years.index')" variant="secondary" size="sm">
                                <i class="fa-solid fa-calendar-days text-xs"></i>
                                {{ __('core.settings.organization.view_years_action') }}
                            </x-ui.button>
                        @endcan
                    @endif
                </div>
            </div>
        </x-ui.card>
    </div>
</x-app-layout>
