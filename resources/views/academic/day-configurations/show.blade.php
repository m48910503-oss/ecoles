<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('academic.day_config.show.title')" :description="__('academic.day_config.show.description')">
            <x-slot:actions>
                <x-ui.button :href="route('academic.day-configurations.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
                @can('update', $dayConfiguration)
                    <x-ui.button :href="route('academic.day-configurations.edit', $dayConfiguration)" variant="secondary">
                        {{ __('core.common.actions.edit') }}
                    </x-ui.button>
                @endcan
                @can('lock', $dayConfiguration)
                    <form method="POST" action="{{ route('academic.day-configurations.toggle-lock', $dayConfiguration) }}">
                        @csrf
                        @method('PATCH')
                        <x-ui.button type="submit" :variant="$dayConfiguration->is_locked ? 'secondary' : 'primary'">
                            <i class="fa-solid {{ $dayConfiguration->is_locked ? 'fa-lock-open' : 'fa-lock' }} text-xs"></i>
                            {{ $dayConfiguration->is_locked ? __('academic.day_config.show.unlock_action') : __('academic.day_config.show.lock_action') }}
                        </x-ui.button>
                    </form>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    @php
        $hours = $dayConfiguration->day_type?->value === 'continuous'
            ? trim(($dayConfiguration->start_time ?? '—').' - '.($dayConfiguration->end_time ?? '—'))
            : trim(($dayConfiguration->morning_start_time ?? '—').' - '.($dayConfiguration->morning_end_time ?? '—').' / '.($dayConfiguration->afternoon_start_time ?? '—').' - '.($dayConfiguration->afternoon_end_time ?? '—'));
    @endphp

    <div class="space-y-6">
        <section class="grid gap-6 xl:grid-cols-[1fr_0.92fr]">
            <x-ui.card class="space-y-5">
                <div class="flex flex-wrap gap-2">
                    <x-ui.badge tone="sky">{{ $dayConfiguration->scope_type->label() }}</x-ui.badge>
                    <x-ui.badge tone="emerald">{{ $dayConfiguration->day_type->label() }}</x-ui.badge>
                    <x-ui.badge :tone="$dayConfiguration->is_locked ? 'rose' : 'sky'">
                        {{ $dayConfiguration->is_locked ? __('academic.common.locked') : __('academic.common.unlocked') }}
                    </x-ui.badge>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.day_config.show.year') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $dayConfiguration->academicYear->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.day_config.show.school') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $dayConfiguration->school->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.day_config.show.hours') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $hours }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.day_config.show.break') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">
                            {{ $dayConfiguration->break_start_time && $dayConfiguration->break_end_time ? $dayConfiguration->break_start_time.' - '.$dayConfiguration->break_end_time : '—' }}
                        </p>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card class="space-y-4">
                @if ($dayConfiguration->cycle)
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.day_config.show.cycle') }}</p>
                        <p class="mt-2 font-medium text-gray-900 dark:text-white">{{ $dayConfiguration->cycle->name }}</p>
                    </div>
                @endif
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.day_config.show.created_by') }}</p>
                    <p class="mt-2 font-medium text-gray-900 dark:text-white">{{ $dayConfiguration->creator?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.day_config.show.updated_by') }}</p>
                    <p class="mt-2 font-medium text-gray-900 dark:text-white">{{ $dayConfiguration->updater?->name ?? '—' }}</p>
                </div>
            </x-ui.card>
        </section>
    </div>
</x-app-layout>
