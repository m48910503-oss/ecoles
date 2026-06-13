<form
    id="academic-year-step-2-form"
    method="POST"
    action="{{ route('core.academic-years.create.step2.store') }}"
    class="space-y-6"
>
    @csrf

    @include('core.academic-years.partials.cycle-selector', [
        'cycles' => $cycles,
        'selectedCycles' => $selectedCycles,
    ])

    <div class="flex flex-wrap gap-3">
        <x-ui.button :href="route('core.academic-years.create', ['wizard_step' => 1])" variant="secondary">
            {{ __('core.common.actions.previous') }}
        </x-ui.button>
        <x-ui.button type="submit">
            {{ __('core.common.actions.next') }}
        </x-ui.button>
    </div>
</form>
