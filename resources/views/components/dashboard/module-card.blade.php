@props([
    'label',
    'value',
    'icon' => 'fa-cube',
    'tone' => 'cyan',
    'route' => null,
    'buttonLabel' => null,
    'valueClass' => 'text-[1.7rem] font-bold leading-none text-gray-900 dark:text-white',
])

@php
    $tones = [
        'cyan' => [
            'surface' => 'border-cyan-200/70 bg-cyan-50 dark:border-cyan-800/30 dark:bg-sky-950/40',
            'iconWrap' => 'bg-cyan-500 text-white shadow-cyan-300/30 dark:shadow-cyan-900/40',
            'button' => 'border-cyan-300 bg-cyan-100/90 text-cyan-800 hover:border-cyan-500 hover:bg-cyan-500 hover:text-white dark:border-cyan-500/25 dark:bg-cyan-500/10 dark:text-cyan-200 dark:hover:border-cyan-400 dark:hover:bg-cyan-500 dark:hover:text-white',
        ],
        'amber' => [
            'surface' => 'border-amber-200/70 bg-amber-50 dark:border-amber-800/30 dark:bg-amber-950/20',
            'iconWrap' => 'bg-amber-500 text-white shadow-amber-300/30 dark:shadow-amber-900/40',
            'button' => 'border-amber-300 bg-amber-100/90 text-amber-800 hover:border-amber-500 hover:bg-amber-500 hover:text-white dark:border-amber-500/25 dark:bg-amber-500/10 dark:text-amber-200 dark:hover:border-amber-400 dark:hover:bg-amber-500 dark:hover:text-white',
        ],
        'pink' => [
            'surface' => 'border-rose-200/70 bg-rose-50 dark:border-rose-800/30 dark:bg-rose-950/20',
            'iconWrap' => 'bg-rose-500 text-white shadow-rose-300/30 dark:shadow-rose-900/40',
            'button' => 'border-rose-300 bg-rose-100/90 text-rose-800 hover:border-rose-500 hover:bg-rose-500 hover:text-white dark:border-rose-500/25 dark:bg-rose-500/10 dark:text-rose-200 dark:hover:border-rose-400 dark:hover:bg-rose-500 dark:hover:text-white',
        ],
        'sky' => [
            'surface' => 'border-sky-200/70 bg-sky-50 dark:border-sky-800/30 dark:bg-blue-950/35',
            'iconWrap' => 'bg-sky-500 text-white shadow-sky-300/30 dark:shadow-sky-900/40',
            'button' => 'border-sky-300 bg-sky-100/90 text-sky-800 hover:border-sky-500 hover:bg-sky-500 hover:text-white dark:border-sky-500/25 dark:bg-sky-500/10 dark:text-sky-200 dark:hover:border-sky-400 dark:hover:bg-sky-500 dark:hover:text-white',
        ],
        'green' => [
            'surface' => 'border-emerald-200/70 bg-emerald-50 dark:border-emerald-800/30 dark:bg-emerald-950/25',
            'iconWrap' => 'bg-emerald-500 text-white shadow-emerald-300/30 dark:shadow-emerald-900/40',
            'button' => 'border-emerald-300 bg-emerald-100/90 text-emerald-800 hover:border-emerald-500 hover:bg-emerald-500 hover:text-white dark:border-emerald-500/25 dark:bg-emerald-500/10 dark:text-emerald-200 dark:hover:border-emerald-400 dark:hover:bg-emerald-500 dark:hover:text-white',
        ],
        'emerald' => [
            'surface' => 'border-teal-200/70 bg-teal-50 dark:border-teal-800/30 dark:bg-teal-950/25',
            'iconWrap' => 'bg-teal-500 text-white shadow-teal-300/30 dark:shadow-teal-900/40',
            'button' => 'border-teal-300 bg-teal-100/90 text-teal-800 hover:border-teal-500 hover:bg-teal-500 hover:text-white dark:border-teal-500/25 dark:bg-teal-500/10 dark:text-teal-200 dark:hover:border-teal-400 dark:hover:bg-teal-500 dark:hover:text-white',
        ],
        'blue' => [
            'surface' => 'border-cyan-200/70 bg-cyan-50 dark:border-cyan-800/30 dark:bg-cyan-950/30',
            'iconWrap' => 'bg-cyan-500 text-white shadow-cyan-300/30 dark:shadow-cyan-900/40',
            'button' => 'border-cyan-300 bg-cyan-100/90 text-cyan-800 hover:border-cyan-500 hover:bg-cyan-500 hover:text-white dark:border-cyan-500/25 dark:bg-cyan-500/10 dark:text-cyan-200 dark:hover:border-cyan-400 dark:hover:bg-cyan-500 dark:hover:text-white',
        ],
        'orange' => [
            'surface' => 'border-amber-200/70 bg-amber-50 dark:border-amber-800/30 dark:bg-orange-950/20',
            'iconWrap' => 'bg-amber-500 text-white shadow-amber-300/30 dark:shadow-amber-900/40',
            'button' => 'border-amber-300 bg-amber-100/90 text-amber-800 hover:border-amber-500 hover:bg-amber-500 hover:text-white dark:border-amber-500/25 dark:bg-amber-500/10 dark:text-amber-200 dark:hover:border-amber-400 dark:hover:bg-amber-500 dark:hover:text-white',
        ],
    ];

    $toneClasses = $tones[$tone] ?? $tones['cyan'];
    $buttonStateClasses = $route
        ? 'cursor-pointer'
        : 'cursor-default opacity-90';
@endphp

<article class="flex h-full min-h-[12.75rem] flex-col items-center justify-between gap-3 rounded-[1.3rem] border p-3.5 text-center shadow-[0_1px_2px_rgba(15,23,42,0.04),0_8px_24px_rgba(15,23,42,0.03)] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_2px_8px_rgba(15,23,42,0.06),0_18px_34px_rgba(15,23,42,0.08)] dark:shadow-none {{ $toneClasses['surface'] }}">
    <div class="flex w-full flex-1 flex-col items-center justify-center">
        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-[1rem] shadow-lg {{ $toneClasses['iconWrap'] }}">
            <i class="fa-solid {{ $icon }} text-[1.05rem]"></i>
        </span>

        <p class="mt-3 text-[0.9rem] font-medium tracking-tight text-gray-900 dark:text-white">
            {{ $label }}
        </p>

        <p class="mt-2 break-words {{ $valueClass }}">
            {{ $value }}
        </p>
    </div>

    @if ($route)
        <a
            href="{{ $route }}"
            class="{{ $toneClasses['button'] }} {{ $buttonStateClasses }} inline-flex items-center justify-center rounded-xl border px-3 py-1.5 text-[0.85rem] font-semibold transition-all duration-300"
        >
            {{ $buttonLabel ?? __('ui.dashboard.show_all') }}
        </a>
    @else
        <button
            type="button"
            disabled
            class="{{ $toneClasses['button'] }} {{ $buttonStateClasses }} inline-flex items-center justify-center rounded-xl border px-3 py-1.5 text-[0.85rem] font-semibold transition-all duration-300"
        >
            {{ $buttonLabel ?? __('ui.dashboard.show_all') }}
        </button>
    @endif
</article>
