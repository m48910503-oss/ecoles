@props([
    'tone' => 'slate',
])

@php
    $tones = [
        'slate' => 'border border-slate-200 bg-slate-100 text-slate-800 dark:border-slate-700/80 dark:bg-slate-800/85 dark:text-slate-100',
        'sky' => 'border border-sky-200 bg-sky-100 text-sky-800 dark:border-sky-700/70 dark:bg-sky-500/20 dark:text-sky-100',
        'emerald' => 'border border-emerald-200 bg-emerald-100 text-emerald-800 dark:border-emerald-700/70 dark:bg-emerald-500/20 dark:text-emerald-100',
        'rose' => 'border border-rose-200 bg-rose-100 text-rose-800 dark:border-rose-700/70 dark:bg-rose-500/20 dark:text-rose-100',
        'amber' => 'border border-amber-200 bg-amber-100 text-amber-800 dark:border-amber-700/70 dark:bg-amber-500/20 dark:text-amber-100',
        'violet' => 'border border-violet-200 bg-violet-100 text-violet-800 dark:border-violet-700/70 dark:bg-violet-500/20 dark:text-violet-100',
    ];
@endphp

<span {{ $attributes->class('inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold shadow-sm shadow-slate-900/5 '.$tones[$tone]) }}>
    {{ $slot }}
</span>
