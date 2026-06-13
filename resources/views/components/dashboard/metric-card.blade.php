@props([
    'label',
    'subtitle',
    'value',
    'valueClass' => 'text-[1.65rem] font-bold text-gray-900 dark:text-white',
    'trend' => null,
    'icon' => 'fa-chart-line',
    'tone' => 'sky',
])

@php
    $tones = [
        'sky' => [
            'iconWrap' => 'bg-sky-100 text-sky-500 dark:bg-sky-500/20 dark:text-sky-300',
            'trend' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/15 dark:text-sky-300',
        ],
        'emerald' => [
            'iconWrap' => 'bg-emerald-100 text-emerald-500 dark:bg-emerald-500/20 dark:text-emerald-300',
            'trend' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
        ],
        'amber' => [
            'iconWrap' => 'bg-amber-100 text-amber-500 dark:bg-amber-500/20 dark:text-amber-300',
            'trend' => 'bg-amber-50 text-amber-500 dark:bg-amber-500/15 dark:text-amber-300',
        ],
        'cyan' => [
            'iconWrap' => 'bg-cyan-100 text-cyan-500 dark:bg-cyan-500/20 dark:text-cyan-300',
            'trend' => 'bg-cyan-50 text-cyan-500 dark:bg-cyan-500/15 dark:text-cyan-300',
        ],
        'slate' => [
            'iconWrap' => 'bg-slate-100 text-slate-500 dark:bg-slate-500/20 dark:text-slate-300',
            'trend' => 'bg-slate-50 text-slate-600 dark:bg-slate-500/15 dark:text-slate-300',
        ],
        'pink' => [
            'iconWrap' => 'bg-pink-100 text-pink-500 dark:bg-pink-500/20 dark:text-pink-300',
            'trend' => 'bg-pink-50 text-pink-500 dark:bg-pink-500/15 dark:text-pink-300',
        ],
        'violet' => [
            'iconWrap' => 'bg-violet-100 text-violet-500 dark:bg-violet-500/20 dark:text-violet-300',
            'trend' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300',
        ],
    ];

    $toneClasses = $tones[$tone] ?? $tones['sky'];
@endphp

<article class="rounded-[1.3rem] border border-gray-200/80 bg-white p-3.5 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_8px_24px_rgba(15,23,42,0.03)] transition-all duration-300 hover:border-gray-300/80 hover:shadow-[0_2px_6px_rgba(15,23,42,0.04),0_14px_30px_rgba(15,23,42,0.05)] dark:border-gray-800 dark:bg-gray-900 dark:hover:border-gray-700 dark:shadow-none">
    <div class="flex items-center gap-3">
        <span class="inline-flex h-12 w-12 items-center justify-center rounded-[1rem] {{ $toneClasses['iconWrap'] }}">
            <i class="fa-solid {{ $icon }} text-base"></i>
        </span>

        <div class="min-w-0">
            <h3 class="text-[0.92rem] font-semibold tracking-tight text-gray-900 dark:text-white">{{ $label }}</h3>
            <p class="mt-1 text-[0.86rem] text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
        </div>
    </div>

    <div class="mt-4 flex items-end justify-between gap-3">
        <p class="{{ $valueClass }}">{{ $value }}</p>

        @if ($trend)
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[0.72rem] font-semibold {{ $toneClasses['trend'] }}">
                {{ $trend }}
            </span>
        @endif
    </div>
</article>
