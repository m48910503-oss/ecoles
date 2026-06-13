<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$coefficient->subject->name" :description="__('academic.coefficients.show.description')">
            <x-slot:actions>
                <x-ui.button :href="route('academic.coefficients.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
                @can('update', $coefficient)
                    <x-ui.button :href="route('academic.coefficients.edit', $coefficient)" variant="warning">
                        {{ __('core.common.actions.edit') }}
                    </x-ui.button>
                @endcan
                @can('disable', $coefficient)
                    <form method="POST" action="{{ route('academic.coefficients.toggle-active', $coefficient) }}">
                        @csrf
                        @method('PATCH')
                        <x-ui.button type="submit">
                            {{ $coefficient->is_active ? __('academic.coefficients.show.toggle_disable') : __('academic.coefficients.show.toggle_enable') }}
                        </x-ui.button>
                    </form>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('academic.common.academic_year')" :value="$coefficient->academicYear->name" icon="fa-calendar-days" tone="sky" />
            <x-ui.stats-card :label="__('academic.common.level')" :value="$coefficient->level->name" icon="fa-layer-group" tone="violet" />
            <x-ui.stats-card :label="__('academic.common.series')" :value="$coefficient->series?->short_name ?: '—'" icon="fa-sitemap" tone="amber" />
            <x-ui.stats-card :label="__('academic.common.coefficient')" :value="number_format((float) $coefficient->coefficient, 2, ',', ' ')" icon="fa-square-root-variable" tone="emerald" />
        </section>

        <x-ui.card class="space-y-5">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.common.school') }}</p>
                    <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $coefficient->school->name }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.common.subject') }}</p>
                    <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $coefficient->subject->name }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.common.optional') }}</p>
                    <div class="mt-2">
                        <x-ui.badge :tone="$coefficient->is_optional ? 'amber' : 'slate'">
                            {{ $coefficient->is_optional ? __('academic.common.yes') : __('academic.common.no') }}
                        </x-ui.badge>
                    </div>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.common.status') }}</p>
                    <div class="mt-2">
                        <x-ui.badge :tone="$coefficient->is_active ? 'emerald' : 'slate'">
                            {{ $coefficient->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                        </x-ui.badge>
                    </div>
                </div>
            </div>
        </x-ui.card>
    </div>
</x-app-layout>
