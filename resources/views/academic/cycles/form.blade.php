<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="$isEdit ? __('academic.cycles.form.edit_title') : __('academic.cycles.form.create_title')"
            :description="__('academic.cycles.form.description')"
        >
            <x-slot:actions>
                <x-ui.button :href="route('academic.cycles.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
                <x-ui.button type="submit" form="cycle-form">
                    {{ $isEdit ? __('academic.cycles.form.submit_edit') : __('academic.cycles.form.submit_create') }}
                </x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <form id="cycle-form" method="POST" action="{{ $isEdit ? route('academic.cycles.update', $cycle) : route('academic.cycles.store') }}" class="space-y-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <section class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <x-ui.card class="space-y-6">
                <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.cycles.form.details_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.cycles.form.details_description') }}</p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <x-form.input
                        name="name"
                        :label="__('academic.cycles.form.name')"
                        :value="$cycle->name"
                        icon="fa-signature"
                        placeholder="Ex : Cycle fondamental"
                        class="md:col-span-2"
                        required
                    />

                    <x-form.textarea
                        name="description"
                        :label="__('academic.cycles.form.description_label')"
                        :value="$cycle->description"
                        icon="fa-align-left"
                        placeholder="Ex : Cycle destiné au primaire"
                        class="md:col-span-2"
                    />
                </div>
            </x-ui.card>

            <div class="space-y-6 xl:sticky xl:top-6 self-start">
                <x-ui.card variant="dense" padding="p-4" class="space-y-5">
                    <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.cycles.form.governance_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('academic.cycles.form.governance_description') }}</p>
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-950/40">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" class="form-checkbox" @checked(old('is_active', $cycle->is_active ?? true))>
                        <span class="inline-flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                            <i class="fa-solid fa-toggle-on text-sky-500"></i>
                            {{ __('academic.cycles.form.active_label') }}
                        </span>
                    </label>
                </x-ui.card>
            </div>
        </section>
    </form>
</x-app-layout>
