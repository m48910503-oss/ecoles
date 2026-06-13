<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header
            :title="__('core.academic_years.wizard.title')"
            :description="__('core.academic_years.wizard.description')"
        >
            <x-slot:actions>
                <x-ui.button :href="route('core.academic-years.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>

                @if (! empty($wizardState))
                    <x-ui.button :href="route('core.academic-years.create', ['reset' => 1])" variant="ghost">
                        {{ __('core.academic_years.wizard.restart') }}
                    </x-ui.button>
                @endif
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        @include('core.academic-years.wizard.partials.stepper')
        @include('core.academic-years.wizard.steps.step-'.$step)
    </div>
</x-app-layout>
