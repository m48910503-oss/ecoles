<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$series->name" :description="__('academic.series.show.description')">
            <x-slot:actions>
                <x-ui.button :href="route('academic.series.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
                @can('update', $series)
                    <x-ui.button :href="route('academic.series.edit', $series)" variant="warning">
                        {{ __('core.common.actions.edit') }}
                    </x-ui.button>
                @endcan
                @can('disable', $series)
                    <form method="POST" action="{{ route('academic.series.toggle-active', $series) }}">
                        @csrf
                        @method('PATCH')
                        <x-ui.button type="submit">
                            {{ $series->is_active ? __('academic.series.show.toggle_disable') : __('academic.series.show.toggle_enable') }}
                        </x-ui.button>
                    </form>
                @endcan
                @can('delete', $series)
                    <x-ui.confirm-modal
                        :name="'delete-series-'.$series->id"
                        :action="route('academic.series.destroy', $series)"
                        :description="__('academic.series.index.delete_confirm')"
                    />
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card size="compact" :label="__('academic.series.show.cycle')" :value="$series->cycle->name" icon="fa-diagram-project" tone="sky" />
            <x-ui.stats-card size="compact" :label="__('academic.common.short_name')" :value="$series->short_name ?: '—'" icon="fa-font" tone="violet" />
            <x-ui.stats-card size="compact" :label="__('academic.series.show.classes_linked')" :value="$series->classrooms_count" icon="fa-chalkboard-user" tone="emerald" />
            <x-ui.stats-card size="compact" :label="__('academic.series.show.coefficients_linked')" :value="$series->coefficients_count" icon="fa-square-root-variable" tone="amber" />
        </section>

        <x-ui.card class="space-y-5">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.series.show.cycle') }}</p>
                    <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $series->cycle->name }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.common.short_name') }}</p>
                    <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $series->short_name ?: '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.common.status') }}</p>
                    <div class="mt-2">
                        <x-ui.badge :tone="$series->is_active ? 'emerald' : 'slate'">
                            {{ $series->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                        </x-ui.badge>
                    </div>
                </div>
                @if ($series->description)
                    <div class="md:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.series.form.description_label') }}</p>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-300">{{ $series->description }}</p>
                    </div>
                @endif
            </div>
        </x-ui.card>
    </div>
</x-app-layout>
