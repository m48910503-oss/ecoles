@php
    $totalSteps = count($wizardSteps);
    $currentStep = collect($wizardSteps)->firstWhere('status', 'current');
    $currentStepNumber = is_array($currentStep)
        ? (int) ($currentStep['number'] ?? 1)
        : (int) (collect($wizardSteps)->where('status', 'completed')->max('number') ?? 1);
    $progressRatio = $totalSteps > 1 ? max(0, min(1, ($currentStepNumber - 1) / ($totalSteps - 1))) : 1;
    $progressWidth = 'calc((100% - 6rem) * '.$progressRatio.')';

    $statusConfig = [
        'current' => [
            'card' => 'border-slate-200 bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04),0_10px_22px_rgba(15,23,42,0.05)] dark:border-slate-700/70 dark:bg-slate-900/80 dark:shadow-[0_1px_0_rgba(148,163,184,0.05),0_16px_30px_rgba(2,6,23,0.28)]',
            'bubble' => 'border-sky-200 bg-sky-50 text-sky-700 shadow-sm shadow-sky-500/10 dark:border-sky-700/50 dark:bg-sky-500/15 dark:text-sky-200',
            'label' => 'text-slate-500 dark:text-slate-400',
            'title' => 'text-slate-900 dark:text-white',
        ],
        'completed' => [
            'card' => 'border-transparent bg-transparent',
            'bubble' => 'border-emerald-200 bg-emerald-50 text-emerald-700 shadow-sm shadow-emerald-500/10 dark:border-emerald-700/50 dark:bg-emerald-500/15 dark:text-emerald-200',
            'label' => 'text-slate-500 dark:text-slate-400',
            'title' => 'text-slate-800 dark:text-slate-100',
        ],
        'pending' => [
            'card' => 'border-transparent bg-transparent',
            'bubble' => 'border-slate-200 bg-white text-slate-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-400',
            'label' => 'text-slate-400 dark:text-slate-500',
            'title' => 'text-slate-600 dark:text-slate-400',
        ],
    ];
@endphp

<nav
    aria-label="{{ __('core.academic_years.wizard.description') }}"
    class="page-card page-card--surface rounded-[2rem] overflow-hidden px-4 py-6 transition-colors duration-300 sm:px-6 sm:py-7"
    x-data="{ progressWidth: @js($progressWidth) }"
>
    <ol class="relative flex items-stretch gap-4 overflow-x-auto px-2 sm:gap-6 sm:px-3">
        <span aria-hidden="true" class="pointer-events-none absolute left-[3rem] right-[3rem] top-8 h-px bg-slate-200 dark:bg-slate-700/70"></span>
        <span aria-hidden="true" class="pointer-events-none absolute left-[3rem] top-8 h-px bg-sky-500 dark:bg-sky-400" :style="{ width: progressWidth }"></span>

        @foreach ($wizardSteps as $wizardStep)
            @php
                $isCurrent = $wizardStep['status'] === 'current';
                $theme = $statusConfig[$wizardStep['status']] ?? $statusConfig['pending'];
                $isCompleted = $wizardStep['status'] === 'completed';
            @endphp

            <li class="flex min-w-[11.5rem] flex-1 items-stretch">
                @if ($wizardStep['href'])
                    <a
                        href="{{ $wizardStep['href'] }}"
                        @class([
                            'group flex w-full flex-col items-center rounded-3xl px-2 py-1 text-center transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-500/20',
                            'cursor-pointer' => true,
                        ])
                        @if ($isCurrent) aria-current="step" @endif
                    >
                @else
                    <div class="group flex w-full flex-col items-center rounded-3xl px-2 py-1 text-center">
                @endif
                        <span @class(['flex h-14 w-14 items-center justify-center rounded-full border text-lg font-semibold transition-all duration-200', $theme['bubble']])>
                            @if ($isCompleted)
                                <i class="fa-solid fa-check text-base" aria-hidden="true"></i>
                            @else
                                {{ $wizardStep['number'] }}
                            @endif
                        </span>

                        <span @class(['mt-4 block text-[0.72rem] font-semibold uppercase tracking-[0.24em] transition-colors duration-200', $theme['label']])>
                            {{ __('core.academic_years.wizard.step_label', ['current' => $wizardStep['number'], 'total' => $totalSteps]) }}
                        </span>

                        <span @class(['mt-1 block text-[1.08rem] font-semibold leading-tight tracking-tight transition-colors duration-200', $theme['title']])>
                            {{ $wizardStep['title'] }}
                        </span>
                @if ($wizardStep['href'])
                    </a>
                @else
                    </div>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
