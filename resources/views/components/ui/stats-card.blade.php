@props([
    'label',
    'value',
    'caption' => null,
    'icon' => 'fa-chart-line',
    'tone' => 'sky',
    'size' => 'default',
])

@php
    $tones = [
        'sky' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
        'cyan' => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-500/15 dark:text-cyan-300',
        'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
        'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
        'rose' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
        'slate' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        'violet' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300',
    ];

    $sizes = [
        'default' => [
            'label' => 'text-[11px]',
            'value' => 'mt-3 text-[1.7rem] leading-none',
            'caption' => 'mt-2 text-xs',
            'icon' => 'h-10 w-10 text-sm',
            'cardPadding' => 'p-5',
        ],
        'compact' => [
            'label' => 'text-[10px]',
            'value' => 'mt-2 text-[1.35rem] leading-tight',
            'caption' => 'mt-1.5 text-[11px]',
            'icon' => 'h-9 w-9 text-xs',
            'cardPadding' => 'p-4',
        ],
    ];

    $sizeClasses = $sizes[$size] ?? $sizes['default'];
    $iconClass = $size === 'compact' ? 'text-xs' : 'text-sm';
@endphp

<x-ui.card variant="metric" :padding="$sizeClasses['cardPadding']" {{ $attributes->class('h-full') }}>
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="ui-stats-card__label font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500 {{ $sizeClasses['label'] }}">{{ $label }}</p>
            <p class="ui-stats-card__value break-words font-semibold tracking-tight text-gray-900 dark:text-white {{ $sizeClasses['value'] }}">{{ $value }}</p>
            @if ($caption)
                <p class="ui-stats-card__caption leading-relaxed text-gray-500 dark:text-gray-400 {{ $sizeClasses['caption'] }}">{{ $caption }}</p>
            @endif
        </div>

        <span class="ui-stats-card__icon inline-flex items-center justify-center rounded-xl {{ $tones[$tone] ?? $tones['sky'] }} {{ $sizeClasses['icon'] }}">
            <i class="fa-solid {{ $icon }} {{ $iconClass }}"></i>
        </span>
    </div>
</x-ui.card>
