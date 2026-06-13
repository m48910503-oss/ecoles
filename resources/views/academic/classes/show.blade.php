<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$classroom->name" :description="__('academic.classes.show.description')">
        <x-slot:actions>
            <x-ui.button :href="route('academic.classes.index')" variant="secondary">
                {{ __('core.common.actions.back') }}
            </x-ui.button>
            @can('view', $classroom->level)
                <x-ui.button :href="route('academic.levels.show', $classroom->level)" variant="ghost">
                    {{ __('academic.classes.show.view_level') }}
                </x-ui.button>
            @endcan
            @can('update', $classroom)
                <x-ui.button :href="route('academic.classes.edit', $classroom)" variant="warning">
                    {{ __('core.common.actions.edit') }}
                </x-ui.button>
            @endcan
                @can('disable', $classroom)
                    <form method="POST" action="{{ route('academic.classes.toggle-active', $classroom) }}">
                        @csrf
                        @method('PATCH')
                        <x-ui.button type="submit">
                            {{ $classroom->is_active ? __('academic.classes.show.toggle_disable') : __('academic.classes.show.toggle_enable') }}
                    </x-ui.button>
                </form>
            @endcan
            @can('delete', $classroom)
                <x-ui.confirm-modal
                    :name="'delete-classroom-'.$classroom->id"
                    :action="route('academic.classes.destroy', $classroom)"
                    :description="__('academic.classes.index.delete_confirm')"
                />
            @endcan
        </x-slot:actions>
    </x-ui.page-header>
</x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('academic.common.academic_year')" :value="$classroom->academicYear->name" icon="fa-calendar-days" tone="sky" />
            <x-ui.stats-card :label="__('academic.common.level')" :value="$classroom->level->name" icon="fa-layer-group" tone="violet" />
            <x-ui.stats-card :label="__('academic.common.series')" :value="$classroom->series?->short_name ?: '—'" icon="fa-sitemap" tone="amber" />
            <x-ui.stats-card :label="__('academic.classes.form.capacity')" :value="$classroom->capacity ?: '—'" icon="fa-users" tone="emerald" />
        </section>

        <x-ui.card class="space-y-5">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.common.school') }}</p>
                    <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $classroom->school->name }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.common.code') }}</p>
                    <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $classroom->code }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.common.cycle') }}</p>
                    <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $classroom->level->cycle->name }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.common.status') }}</p>
                    <div class="mt-2">
                        <x-ui.badge :tone="$classroom->is_active ? 'emerald' : 'slate'">
                            {{ $classroom->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                        </x-ui.badge>
                    </div>
                </div>
                @if ($classroom->description)
                    <div class="md:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.classes.form.description_label') }}</p>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-300">{{ $classroom->description }}</p>
                    </div>
                @endif
            </div>
        </x-ui.card>

        <x-ui.card class="space-y-4">
            <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.classes.show.references_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.classes.show.description') }}</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <x-ui.badge tone="amber">{{ $classroom->calendars->count() }} {{ __('academic.classes.show.calendars') }}</x-ui.badge>
            </div>
        </x-ui.card>
    </div>
</x-app-layout>
