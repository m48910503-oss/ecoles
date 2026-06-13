@php
    $cycles = $cycles ?? collect();
    $selectedCycles = $selectedCycles ?? collect();

    $cycleMessages = collect($errors->getMessages())
        ->filter(fn (array $messages, string $key): bool => str_starts_with((string) $key, 'cycle_ids'))
        ->flatten()
        ->values();

    $submittedCycleIds = old('cycle_ids');

    if (is_array($submittedCycleIds)) {
        $selectedCycleIds = array_map('strval', $submittedCycleIds);
    } elseif ($cycleMessages->isNotEmpty()) {
        $selectedCycleIds = [];
    } else {
        $selectedCycleIds = $selectedCycles->pluck('id')->map(fn ($id) => (string) $id)->all();
    }
@endphp

<x-ui.card class="space-y-5">
    <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.academic_years.form.cycles_title') }}</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('core.academic_years.form.cycles_description') }}</p>
    </div>

    @if ($cycles->isNotEmpty())
        <div class="grid gap-3 md:grid-cols-2">
            @foreach ($cycles as $cycle)
                @php
                    $cycleId = (string) $cycle->id;
                    $isSelected = in_array($cycleId, $selectedCycleIds, true);
                    $tone = $cycle->category?->tone() ?? 'sky';
                @endphp

                <label @class([
                    'flex items-start gap-3 rounded-2xl border p-4 transition-all duration-200',
                    'border-slate-300 bg-white/90 ring-1 ring-sky-100/70 shadow-sm shadow-slate-900/5 dark:border-slate-700/80 dark:bg-slate-900/70 dark:ring-sky-900/30' => $isSelected,
                    'border-gray-200 bg-slate-50/80 dark:border-gray-800 dark:bg-gray-950/50' => ! $isSelected,
                ])>
                    <input
                        type="checkbox"
                        name="cycle_ids[]"
                        value="{{ $cycle->id }}"
                        class="form-checkbox mt-1"
                        @checked($isSelected)
                    >

                    <span class="min-w-0 flex-1 space-y-1">
                        <span class="flex flex-wrap items-center gap-2 text-sm font-semibold text-gray-800 dark:text-gray-100">
                            <span>{{ $cycle->name }}</span>

                            <x-ui.badge :tone="$cycle->is_active ? 'emerald' : 'slate'">
                                {{ $cycle->is_active ? __('core.common.states.active') : __('core.common.states.inactive') }}
                            </x-ui.badge>

                            @if ($cycle->category)
                                <x-ui.badge :tone="$tone">
                                    {{ $cycle->category->label() }}
                                </x-ui.badge>
                            @endif
                        </span>

                        @if ($cycle->description)
                            <span class="block text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                                {{ $cycle->description }}
                            </span>
                        @endif
                    </span>
                </label>
            @endforeach
        </div>

        @if ($cycleMessages->isNotEmpty())
            <x-ui.alert tone="error">
                {{ $cycleMessages->first() }}
            </x-ui.alert>
        @endif

        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('core.academic_years.form.cycles_hint') }}</p>
    @else
        <x-ui.alert tone="slate">
            {{ __('core.academic_years.form.cycles_empty') }}

            @if ($selectedCycles->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($selectedCycles as $cycle)
                        <x-ui.badge :tone="$cycle->category?->tone() ?? 'sky'">
                            {{ $cycle->name }}
                        </x-ui.badge>
                    @endforeach
                </div>
            @endif
        </x-ui.alert>
    @endif
</x-ui.card>
