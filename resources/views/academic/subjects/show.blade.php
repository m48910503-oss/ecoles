<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$subject->name" :description="__('academic.subjects.show.description')">
            <x-slot:actions>
                <x-ui.button :href="route('academic.subjects.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
                @can('viewAny', \App\Models\AcademicCoefficient::class)
                    <x-ui.button :href="route('academic.coefficients.index', ['academic_subject_id' => $subject->id])" variant="soft-slate">
                        <i class="fa-solid fa-square-root-variable text-xs"></i>
                        {{ __('academic.subjects.show.view_coefficients_action') }}
                    </x-ui.button>
                @endcan
                @can('create', \App\Models\AcademicCoefficient::class)
                    <x-ui.button :href="route('academic.coefficients.create', ['academic_subject_id' => $subject->id, 'school_id' => $subject->school_id])" variant="soft-sky">
                        <i class="fa-solid fa-plus text-xs"></i>
                        {{ __('academic.subjects.show.create_coefficient_action') }}
                    </x-ui.button>
                @endcan
                @can('update', $subject)
                    <x-ui.button :href="route('academic.subjects.edit', $subject)" variant="warning">
                        {{ __('core.common.actions.edit') }}
                    </x-ui.button>
                @endcan
                @can('disable', $subject)
                    <form method="POST" action="{{ route('academic.subjects.toggle-active', $subject) }}">
                        @csrf
                        @method('PATCH')
                        <x-ui.button type="submit">
                            {{ $subject->is_active ? __('academic.subjects.show.toggle_disable') : __('academic.subjects.show.toggle_enable') }}
                        </x-ui.button>
                    </form>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('academic.common.category')" :value="$subject->category?->label() ?? '—'" icon="fa-tags" tone="sky" />
            <x-ui.stats-card :label="__('academic.common.short_name')" :value="$subject->short_name ?: '—'" icon="fa-font" tone="violet" />
            <x-ui.stats-card :label="__('academic.subjects.show.coefficients')" :value="$subject->coefficients->count()" icon="fa-square-root-variable" tone="amber" />
            <x-ui.stats-card :label="__('academic.subjects.show.conduct_settings')" :value="$subject->conductSettings->count()" icon="fa-scale-balanced" tone="emerald" />
        </section>

        <x-ui.card class="space-y-5">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.common.school') }}</p>
                    <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $subject->school->name }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.common.code') }}</p>
                    <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $subject->code }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.subjects.form.category') }}</p>
                    <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $subject->category?->label() ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.common.status') }}</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <x-ui.badge :tone="$subject->is_active ? 'emerald' : 'slate'">
                            {{ $subject->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                        </x-ui.badge>
                        <x-ui.badge :tone="$subject->is_optional_by_default ? 'amber' : 'slate'">
                            {{ $subject->is_optional_by_default ? __('academic.common.optional') : __('academic.common.no') }}
                        </x-ui.badge>
                        @if ($subject->is_conduct)
                            <x-ui.badge tone="violet">{{ __('academic.subjects.form.conduct_label') }}</x-ui.badge>
                        @endif
                    </div>
                </div>
                @if ($subject->description)
                    <div class="md:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.subjects.form.description_label') }}</p>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-300">{{ $subject->description }}</p>
                    </div>
                @endif
            </div>
        </x-ui.card>
    </div>
</x-app-layout>
