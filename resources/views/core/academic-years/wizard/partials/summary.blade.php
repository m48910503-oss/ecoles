@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Str;

    $selectedCycles = $wizardSummary['selected_cycles'] ?? collect();
    $startDate = $wizardSummary['start_date'] ?? null;
    $endDate = $wizardSummary['end_date'] ?? null;
@endphp

<x-ui.card class="space-y-5">
    <div class="border-b border-gray-100 pb-4 dark:border-gray-800">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('core.academic_years.wizard.summary_title') }}</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('core.academic_years.wizard.summary_description') }}</p>
    </div>

    <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-gray-200 bg-white/80 p-4 dark:border-gray-800 dark:bg-gray-950/50">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">{{ __('core.academic_years.wizard.summary.year') }}</p>
            <p class="mt-2 text-xl font-semibold text-gray-900 dark:text-white">{{ $wizardSummary['year_label'] ?? '—' }}</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                @if ($startDate && $endDate)
                    {{ Carbon::parse($startDate)->format('d/m/Y') }} - {{ Carbon::parse($endDate)->format('d/m/Y') }}
                @else
                    —
                @endif
            </p>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white/80 p-4 dark:border-gray-800 dark:bg-gray-950/50">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">{{ __('core.academic_years.wizard.summary.cycles') }}</p>

            @if ($selectedCycles->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach ($selectedCycles as $cycle)
                        <x-ui.badge :tone="$cycle->category?->tone() ?? 'sky'">
                            {{ $cycle->name }}
                        </x-ui.badge>
                    @endforeach
                </div>
            @else
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('core.academic_years.wizard.summary.none') }}</p>
            @endif
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white/80 p-4 dark:border-gray-800 dark:bg-gray-950/50">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">{{ __('core.academic_years.wizard.summary.activation') }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <x-ui.badge :tone="$wizardSummary['is_active'] ? 'emerald' : 'slate'">
                    {{ $wizardSummary['is_active'] ? __('core.common.states.active') : __('core.common.states.inactive') }}
                </x-ui.badge>
                <x-ui.badge :tone="$wizardSummary['enrollment_open'] ? 'sky' : 'slate'">
                    {{ $wizardSummary['enrollment_open'] ? __('core.common.states.open') : __('core.common.states.closed') }}
                </x-ui.badge>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white/80 p-4 dark:border-gray-800 dark:bg-gray-950/50">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">{{ __('core.academic_years.wizard.summary.mode') }}</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <x-ui.badge :tone="$wizardSummary['multi_school_enabled'] ? 'sky' : 'slate'">
                    {{ $wizardSummary['multi_school_enabled'] ? __('core.settings.organization.modes.multi') : __('core.settings.organization.modes.mono') }}
                </x-ui.badge>
            </div>

            @if (! empty($wizardSummary['notes']))
                <p class="mt-3 line-clamp-3 text-sm text-gray-500 dark:text-gray-400">
                    {{ Str::limit($wizardSummary['notes'], 140) }}
                </p>
            @endif
        </div>
    </div>
</x-ui.card>
