<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$level->name" :description="__('academic.levels.show.description')">
        <x-slot:actions>
            <x-ui.button :href="route('academic.levels.index')" variant="secondary">
                {{ __('core.common.actions.back') }}
            </x-ui.button>
            @can('update', $level)
                    <x-ui.button :href="route('academic.levels.edit', $level)" variant="warning">
                        {{ __('core.common.actions.edit') }}
                    </x-ui.button>
                @endcan
            @can('disable', $level)
                <form method="POST" action="{{ route('academic.levels.toggle-active', $level) }}">
                    @csrf
                    @method('PATCH')
                    <x-ui.button type="submit">
                        {{ $level->is_active ? __('academic.levels.show.toggle_disable') : __('academic.levels.show.toggle_enable') }}
                    </x-ui.button>
                </form>
            @endcan
            @can('delete', $level)
                <x-ui.confirm-modal
                    :name="'delete-level-'.$level->id"
                    :action="route('academic.levels.destroy', $level)"
                    :description="__('academic.levels.index.delete_confirm')"
                />
            @endcan
        </x-slot:actions>
    </x-ui.page-header>
</x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <x-ui.stats-card :label="__('academic.levels.show.calendars')" :value="$level->calendars->count()" icon="fa-calendar-week" tone="sky" />
            <x-ui.stats-card :label="__('academic.levels.show.classrooms')" :value="$level->classrooms_count" icon="fa-chalkboard-user" tone="emerald" />
            <x-ui.stats-card :label="__('academic.levels.show.series')" :value="$level->series_count" icon="fa-diagram-project" tone="violet" />
            <x-ui.stats-card :label="__('academic.common.uses_series')" :value="$level->uses_series ? __('academic.common.yes') : __('academic.common.no')" icon="fa-sitemap" tone="violet" />
            <x-ui.stats-card :label="__('academic.common.status')" :value="$level->is_active ? __('core.common.states.active') : __('core.common.states.inactive')" icon="fa-toggle-on" tone="emerald" />
            <x-ui.stats-card :label="__('academic.levels.form.sort_order')" :value="$level->sort_order" icon="fa-arrow-down-1-9" tone="amber" />
        </section>

        <section class="grid gap-6 xl:grid-cols-[1fr_0.95fr]">
            <x-ui.card class="space-y-5">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.levels.show.cycle') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $level->cycle->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.common.code') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $level->code }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.common.short_name') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $level->short_name ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.common.uses_series') }}</p>
                        <div class="mt-2">
                            <x-ui.badge :tone="$level->uses_series ? 'violet' : 'slate'">
                                {{ $level->uses_series ? __('academic.common.yes') : __('academic.common.no') }}
                            </x-ui.badge>
                        </div>
                    </div>
                    @if ($level->description)
                        <div class="md:col-span-2">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.levels.form.description_label') }}</p>
                            <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-300">{{ $level->description }}</p>
                        </div>
                    @endif
                </div>
            </x-ui.card>

            <x-ui.card class="space-y-4">
                <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.levels.show.references_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.levels.show.references_description') }}</p>
                    </div>

                    @can('viewAny', \App\Models\AcademicClassroom::class)
                        <x-ui.button :href="route('academic.classes.index', ['level_id' => $level->id])" variant="ghost" size="sm">
                            {{ __('academic.levels.show.view_classes') }}
                        </x-ui.button>
                    @endcan
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-950/50">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">{{ __('academic.levels.show.calendars') }}</p>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $level->calendars->pluck('name')->take(4)->implode(', ') ?: '—' }}</p>
                    </div>

                    <div class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-950/50">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">{{ __('academic.levels.show.series') }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @forelse ($level->series as $serie)
                                <x-ui.badge tone="violet">{{ $serie->short_name ?: $serie->name }}</x-ui.badge>
                            @empty
                                <p class="text-sm text-gray-600 dark:text-gray-300">—</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </section>
    </div>
</x-app-layout>
