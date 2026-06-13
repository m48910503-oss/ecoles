<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$cycle->name" :description="__('academic.cycles.show.description')">
            <x-slot:actions>
                <x-ui.button :href="route('academic.cycles.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
                @can('update', $cycle)
                    <x-ui.button :href="route('academic.cycles.edit', $cycle)" variant="warning">
                        {{ __('core.common.actions.edit') }}
                    </x-ui.button>
                @endcan
                @can('disable', $cycle)
                    <form method="POST" action="{{ route('academic.cycles.toggle-active', $cycle) }}">
                        @csrf
                        @method('PATCH')
                        <x-ui.button type="submit">
                            {{ $cycle->is_active ? __('academic.cycles.show.toggle_disable') : __('academic.cycles.show.toggle_enable') }}
                        </x-ui.button>
                    </form>
                @endcan
                @can('delete', $cycle)
                    <x-ui.confirm-modal
                        :name="'delete-cycle-'.$cycle->id"
                        :action="route('academic.cycles.destroy', $cycle)"
                        :description="__('academic.cycles.index.delete_confirm')"
                    />
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card size="compact" :label="__('academic.cycles.show.levels')" :value="$cycle->levels->count()" icon="fa-layer-group" tone="sky" />
            <x-ui.stats-card size="compact" :label="__('academic.cycles.show.series')" :value="$cycle->series->count()" icon="fa-sitemap" tone="violet" />
            <x-ui.stats-card size="compact" :label="__('academic.cycles.show.calendars')" :value="$cycle->calendars->count()" icon="fa-calendar-week" tone="emerald" />
            <x-ui.stats-card size="compact" :label="__('academic.cycles.show.day_configurations')" :value="$cycle->schoolDayConfigurations->count()" icon="fa-clock" tone="amber" />
        </section>

        <section>
            <x-ui.card class="space-y-5">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.cycles.show.summary_title') }}</h2>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.common.reference_id') }}</p>
                        <p class="mt-2 font-mono text-base font-semibold text-gray-900 dark:text-white">{{ $cycle->code }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.common.status') }}</p>
                        <div class="mt-2">
                            <x-ui.badge :tone="$cycle->is_active ? 'emerald' : 'slate'">
                                {{ $cycle->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                            </x-ui.badge>
                        </div>
                    </div>
                    @if ($cycle->description)
                        <div class="md:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.cycles.form.description_label') }}</p>
                            <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-300">{{ $cycle->description }}</p>
                        </div>
                    @endif
                </div>
            </x-ui.card>
        </section>
    </div>
</x-app-layout>
