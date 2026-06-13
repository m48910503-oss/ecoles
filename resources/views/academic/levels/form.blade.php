<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="$isEdit ? __('academic.levels.form.edit_title') : __('academic.levels.form.create_title')"
            :description="__('academic.levels.form.description')"
        >
            <x-slot:actions>
                <x-ui.button :href="route('academic.levels.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
                <x-ui.button type="submit" form="level-form">
                    {{ $isEdit ? __('academic.levels.form.submit_edit') : __('academic.levels.form.submit_create') }}
                </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>
    </x-slot>

    @php
        $selectedSeriesIds = collect(old('series_ids', $level->exists ? $level->series->pluck('id')->all() : []))
            ->map(fn ($seriesId) => (string) $seriesId)
            ->values()
            ->all();
    @endphp

    <form
        id="level-form"
        method="POST"
        action="{{ $isEdit ? route('academic.levels.update', $level) : route('academic.levels.store') }}"
        class="space-y-6"
        x-data="{
            cycleId: @js((string) old('cycle_id', $level->cycle_id ?? '')),
            usesSeries: @js((bool) old('uses_series', $level->uses_series)),
            selectedSeries: @js($selectedSeriesIds),
            seriesOptions: @js($seriesOptions->map(fn ($series) => [
                'id' => $series->id,
                'name' => $series->short_name ?: $series->name,
                'cycle_id' => $series->cycle_id,
                'cycle_name' => $series->cycle->name,
                'code' => $series->code,
            ])->values()),
            filteredSeries() {
                return this.seriesOptions.filter(series => String(series.cycle_id) === String(this.cycleId));
            },
        }"
    >
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <x-ui.card class="space-y-6">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.levels.form.details_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.levels.form.details_description') }}</p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <x-form.select name="cycle_id" :label="__('academic.levels.form.cycle')" icon="fa-diagram-project" x-model="cycleId">
                        <option value="">--</option>
                        @foreach ($cycles as $cycle)
                            <option value="{{ $cycle->id }}" @selected((int) old('cycle_id', $level->cycle_id) === $cycle->id)>{{ $cycle->name }}</option>
                        @endforeach
                    </x-form.select>

                    <x-form.input name="name" :label="__('academic.levels.form.name')" :value="$level->name" icon="fa-signature" required />
                    <x-form.input name="short_name" :label="__('academic.levels.form.short_name')" :value="$level->short_name" icon="fa-font" />
                    <x-form.textarea name="description" :label="__('academic.levels.form.description_label')" :value="$level->description" icon="fa-align-left" class="md:col-span-2" />
                </div>
            </x-ui.card>

            <div class="space-y-6">
                <x-ui.card class="space-y-5">
                    <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.levels.form.governance_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.levels.form.governance_description') }}</p>
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <input type="hidden" name="uses_series" value="0">
                        <input type="checkbox" name="uses_series" value="1" class="form-checkbox" x-model="usesSeries" @checked(old('uses_series', $level->uses_series))>
                        <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <i class="fa-solid fa-sitemap text-violet-500"></i>
                            {{ __('academic.levels.form.uses_series_label') }}
                        </span>
                    </label>

                    <x-ui.alert tone="info">{{ __('academic.levels.form.series_hint') }}</x-ui.alert>

                    <div x-show="usesSeries" x-cloak class="space-y-4 rounded-2xl border border-violet-100 bg-violet-50/50 p-4 dark:border-violet-900/40 dark:bg-violet-950/20 md:col-span-2">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('academic.levels.form.series_title') }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('academic.levels.form.series_description') }}</p>
                        </div>

                        <div x-show="!cycleId" x-cloak>
                            <x-ui.alert tone="info">{{ __('academic.levels.form.series_cycle_prompt') }}</x-ui.alert>
                        </div>

                        <div x-show="cycleId && filteredSeries().length === 0" x-cloak>
                            <x-ui.alert tone="warning">{{ __('academic.levels.form.series_empty') }}</x-ui.alert>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <template x-for="series in filteredSeries()" :key="series.id">
                                <label class="flex items-start gap-3 rounded-2xl border border-gray-200 bg-white/80 p-4 dark:border-gray-800 dark:bg-gray-950/60">
                                    <input type="checkbox" name="series_ids[]" :value="String(series.id)" class="form-checkbox mt-1" x-model="selectedSeries" :disabled="!usesSeries || !cycleId">
                                    <span>
                                        <span class="block text-sm font-medium text-gray-900 dark:text-white" x-text="series.name"></span>
                                        <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400" x-text="series.code"></span>
                                    </span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-checkbox" @checked(old('is_active', $level->is_active ?? true))>
                        <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <i class="fa-solid fa-toggle-on text-sky-500"></i>
                            {{ __('academic.levels.form.active_label') }}
                        </span>
                    </label>
                </x-ui.card>

                <div class="flex flex-wrap gap-3">
                    <x-ui.button type="submit">
                        {{ $isEdit ? __('academic.levels.form.submit_edit') : __('academic.levels.form.submit_create') }}
                    </x-ui.button>
                    <x-ui.button :href="route('academic.levels.index')" variant="secondary">
                        {{ __('core.common.actions.back') }}
                    </x-ui.button>
                </div>
            </div>
        </section>
    </form>
</x-app-layout>
