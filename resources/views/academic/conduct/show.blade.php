<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$conductSetting->level->name" :description="__('academic.conduct.show.description')">
            <x-slot:actions>
                <x-ui.button :href="route('academic.conduct.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
                @can('update', $conductSetting)
                    <x-ui.button :href="route('academic.conduct.edit', $conductSetting)" variant="secondary">
                        {{ __('core.common.actions.edit') }}
                    </x-ui.button>
                @endcan
                @can('disable', $conductSetting)
                    <form method="POST" action="{{ route('academic.conduct.toggle-active', $conductSetting) }}">
                        @csrf
                        @method('PATCH')
                        <x-ui.button type="submit">
                            {{ $conductSetting->is_active ? __('academic.conduct.show.toggle_disable') : __('academic.conduct.show.toggle_enable') }}
                        </x-ui.button>
                    </form>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('academic.common.academic_year')" :value="$conductSetting->academicYear->name" icon="fa-calendar-days" tone="sky" />
            <x-ui.stats-card :label="__('academic.common.subject')" :value="$conductSetting->subject->name" icon="fa-scale-balanced" tone="violet" />
            <x-ui.stats-card :label="__('academic.common.base_score')" :value="number_format((float) $conductSetting->base_score, 2, ',', ' ')" icon="fa-hashtag" tone="amber" />
            <x-ui.stats-card :label="__('academic.common.status')" :value="$conductSetting->is_active ? __('core.common.states.active') : __('core.common.states.inactive')" icon="fa-toggle-on" tone="emerald" />
        </section>

        <x-ui.card class="space-y-5">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.common.school') }}</p>
                    <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $conductSetting->school->name }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.common.cycle') }}</p>
                    <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $conductSetting->level->cycle->name }}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.conduct.form.deductions_only_label') }}</p>
                    <div class="mt-2">
                        <x-ui.badge :tone="$conductSetting->deductions_from_sanctions_only ? 'amber' : 'slate'">
                            {{ $conductSetting->deductions_from_sanctions_only ? __('academic.common.yes') : __('academic.common.no') }}
                        </x-ui.badge>
                    </div>
                </div>
                @if ($conductSetting->notes)
                    <div class="md:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.conduct.form.notes') }}</p>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-300">{{ $conductSetting->notes }}</p>
                    </div>
                @endif
            </div>
        </x-ui.card>
    </div>
</x-app-layout>
