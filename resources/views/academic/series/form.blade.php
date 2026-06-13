<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="$isEdit ? __('academic.series.form.edit_title') : __('academic.series.form.create_title')"
            :description="__('academic.series.form.description')"
        >
            <x-slot:actions>
                <x-ui.button :href="route('academic.series.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
                <x-ui.button type="submit" form="series-form">
                    {{ $isEdit ? __('academic.series.form.submit_edit') : __('academic.series.form.submit_create') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <form id="series-form" method="POST" action="{{ $isEdit ? route('academic.series.update', $series) : route('academic.series.store') }}" class="space-y-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <x-ui.card class="space-y-6">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.series.form.details_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.series.form.details_description') }}</p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <x-form.select name="cycle_id" :label="__('academic.series.form.cycle')" icon="fa-diagram-project">
                        <option value="">--</option>
                        @foreach ($cycles as $cycle)
                            <option value="{{ $cycle->id }}" @selected((int) old('cycle_id', $series->cycle_id) === $cycle->id)>{{ $cycle->name }}</option>
                        @endforeach
                    </x-form.select>

                    <x-form.input name="name" :label="__('academic.series.form.name')" :value="$series->name" icon="fa-signature" placeholder="Ex : Série A" required />
                    <x-form.input name="short_name" :label="__('academic.series.form.short_name')" :value="$series->short_name" icon="fa-font" placeholder="Ex : A" />
                    <x-form.textarea name="description" :label="__('academic.series.form.description_label')" :value="$series->description" icon="fa-align-left" class="md:col-span-2" />
                </div>
            </x-ui.card>

            <div class="space-y-6 xl:sticky xl:top-6 self-start">
                <x-ui.card variant="dense" padding="p-4" class="space-y-5">
                    <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.series.form.governance_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.series.form.governance_description') }}</p>
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-checkbox" @checked(old('is_active', $series->is_active ?? true))>
                        <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <i class="fa-solid fa-toggle-on text-sky-500"></i>
                            {{ __('academic.series.form.active_label') }}
                        </span>
                    </label>
                </x-ui.card>
            </div>
        </section>
    </form>
</x-app-layout>
