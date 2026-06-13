@props([
    'tone' => 'success',
    'title' => null,
    'icon' => null,
])

@php
    $tones = [
        'success' => [
            'shell' => 'border-emerald-300 bg-emerald-50/95 dark:border-emerald-700/70 dark:bg-emerald-950/55',
            'icon' => 'fa-circle-check',
            'icon_wrap' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200',
            'title' => 'text-emerald-900 dark:text-emerald-100',
            'body' => 'text-emerald-800 dark:text-emerald-200',
        ],
        'warning' => [
            'shell' => 'border-amber-300 bg-amber-50/95 dark:border-amber-700/70 dark:bg-amber-950/55',
            'icon' => 'fa-triangle-exclamation',
            'icon_wrap' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-200',
            'title' => 'text-amber-900 dark:text-amber-100',
            'body' => 'text-amber-800 dark:text-amber-200',
        ],
        'error' => [
            'shell' => 'border-rose-300 bg-rose-50/95 dark:border-rose-700/70 dark:bg-rose-950/55',
            'icon' => 'fa-triangle-exclamation',
            'icon_wrap' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-200',
            'title' => 'text-rose-900 dark:text-rose-100',
            'body' => 'text-rose-800 dark:text-rose-200',
        ],
        'info' => [
            'shell' => 'border-sky-300 bg-sky-50/95 dark:border-sky-700/70 dark:bg-sky-950/55',
            'icon' => 'fa-circle-info',
            'icon_wrap' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-200',
            'title' => 'text-sky-900 dark:text-sky-100',
            'body' => 'text-sky-800 dark:text-sky-200',
        ],
        'slate' => [
            'shell' => 'border-slate-200 bg-slate-50/95 dark:border-slate-700/70 dark:bg-slate-900/70',
            'icon' => 'fa-circle-info',
            'icon_wrap' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
            'title' => 'text-slate-900 dark:text-slate-100',
            'body' => 'text-slate-700 dark:text-slate-300',
        ],
    ];

    $toneConfig = $tones[$tone] ?? $tones['success'];
    $iconName = $icon ?? $toneConfig['icon'];
@endphp

<div {{ $attributes->class('rounded-2xl border px-4 py-4 text-start shadow-sm shadow-slate-900/5 '.$toneConfig['shell']) }}>
    <div class="flex items-start gap-3">
        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $toneConfig['icon_wrap'] }}">
            <i class="fa-solid {{ $iconName }} text-sm"></i>
        </span>

        <div class="min-w-0 flex-1">
            @if ($title)
                <p class="text-sm font-semibold {{ $toneConfig['title'] }}">{{ $title }}</p>
            @endif

            <div class="{{ $title ? 'mt-1' : '' }} text-sm leading-relaxed {{ $toneConfig['body'] }}">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
