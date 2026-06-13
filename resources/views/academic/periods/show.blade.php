<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$period->name" :description="__('academic.periods.show.description')">
            <x-slot:actions>
                <x-ui.button :href="route('academic.periods.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
                @can('update', $period)
                    <x-ui.button :href="route('academic.periods.edit', $period)" variant="secondary">
                        {{ __('core.common.actions.edit') }}
                    </x-ui.button>
                @endcan
                @can('lock', $period)
                    <form method="POST" action="{{ route('academic.periods.toggle-lock', $period) }}">
                        @csrf
                        @method('PATCH')
                        <x-ui.button type="submit" :variant="$period->is_locked ? 'secondary' : 'primary'">
                            <i class="fa-solid {{ $period->is_locked ? 'fa-lock-open' : 'fa-lock' }} text-xs"></i>
                            {{ $period->is_locked ? __('academic.periods.show.unlock_action') : __('academic.periods.show.lock_action') }}
                        </x-ui.button>
                    </form>
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-6 xl:grid-cols-[1fr_0.92fr]">
            <x-ui.card class="space-y-5">
                <div class="flex flex-wrap gap-2">
                    <x-ui.badge tone="sky">#{{ $period->position }}</x-ui.badge>
                    <x-ui.badge :tone="$period->is_active ? 'emerald' : 'slate'">
                        {{ $period->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                    </x-ui.badge>
                    <x-ui.badge :tone="$period->is_locked ? 'rose' : 'sky'">
                        {{ $period->is_locked ? __('academic.common.locked') : __('academic.common.unlocked') }}
                    </x-ui.badge>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.common.calendar') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $period->academicCalendar?->name ?? $period->configuration?->calendar?->name ?? __('academic.common.not_configured') }}</p>
                        @if ($period->academicCalendar?->scope_type ?? $period->configuration?->calendar?->scope_type)
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ ($period->academicCalendar?->scope_type ?? $period->configuration?->calendar?->scope_type)->label() }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.periods.show.configuration') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $period->configuration?->academicYear?->name ?? __('academic.common.not_configured') }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $period->configuration?->school?->name ?? __('academic.common.not_configured') }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            @if ($period->configuration?->scope_type)
                                <x-ui.badge tone="sky">{{ $period->configuration->scope_type->label() }}</x-ui.badge>
                            @endif
                            @if ($period->configuration?->scopeReferenceLabel())
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $period->configuration->scopeReferenceLabel() }}</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.periods.show.dates') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $period->start_date?->translatedFormat('d M Y') }}</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $period->end_date?->translatedFormat('d M Y') }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.periods.form.position') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">#{{ $period->position }}</p>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card class="space-y-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.periods.show.created_by') }}</p>
                    <p class="mt-2 font-medium text-gray-900 dark:text-white">{{ $period->creator?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.periods.show.updated_by') }}</p>
                    <p class="mt-2 font-medium text-gray-900 dark:text-white">{{ $period->updater?->name ?? '—' }}</p>
                </div>
            </x-ui.card>
        </section>
    </div>
</x-app-layout>
