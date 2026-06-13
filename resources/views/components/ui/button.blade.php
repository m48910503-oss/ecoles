@props([
    'href' => null,
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
])

@php
    $variantClasses = [
        'primary' => 'bg-sky-500 text-white shadow-sm shadow-sky-500/20 hover:bg-sky-600 hover:shadow-lg hover:shadow-sky-500/20 focus:ring-sky-300/40',
        'secondary' => 'border border-gray-200 bg-white text-gray-700 shadow-sm shadow-slate-900/5 hover:border-sky-200 hover:text-sky-600 focus:ring-sky-300/40 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-sky-800 dark:hover:text-sky-300',
        'ghost' => 'text-sky-600 hover:bg-sky-50 focus:ring-sky-300/40 dark:text-sky-300 dark:hover:bg-sky-900/20',
        'soft-slate' => 'border border-slate-200 bg-slate-100/90 text-slate-700 shadow-sm shadow-slate-900/5 hover:border-slate-300 hover:bg-slate-200/80 focus:ring-slate-300/40 dark:border-slate-700/80 dark:bg-slate-800/90 dark:text-slate-200 dark:hover:border-slate-600 dark:hover:bg-slate-700/90',
        'soft-sky' => 'border border-sky-200 bg-sky-50 text-sky-700 shadow-sm shadow-sky-500/10 hover:border-sky-300 hover:bg-sky-100 focus:ring-sky-300/40 dark:border-sky-900/40 dark:bg-sky-500/10 dark:text-sky-200 dark:hover:border-sky-800 dark:hover:bg-sky-500/20',
        'soft-amber' => 'border border-amber-200 bg-amber-50 text-amber-800 shadow-sm shadow-amber-500/10 hover:border-amber-300 hover:bg-amber-100 focus:ring-amber-300/40 dark:border-amber-900/40 dark:bg-amber-500/10 dark:text-amber-200 dark:hover:border-amber-800 dark:hover:bg-amber-500/20',
        'soft-emerald' => 'border border-emerald-200 bg-emerald-50 text-emerald-800 shadow-sm shadow-emerald-500/10 hover:border-emerald-300 hover:bg-emerald-100 focus:ring-emerald-300/40 dark:border-emerald-900/40 dark:bg-emerald-500/10 dark:text-emerald-200 dark:hover:border-emerald-800 dark:hover:bg-emerald-500/20',
        'soft-violet' => 'border border-violet-200 bg-violet-50 text-violet-800 shadow-sm shadow-violet-500/10 hover:border-violet-300 hover:bg-violet-100 focus:ring-violet-300/40 dark:border-violet-900/40 dark:bg-violet-500/10 dark:text-violet-200 dark:hover:border-violet-800 dark:hover:bg-violet-500/20',
        'info' => 'border border-sky-100 bg-sky-50 text-sky-700 shadow-sm shadow-sky-500/10 hover:border-sky-200 hover:bg-sky-100 hover:text-sky-800 focus:ring-sky-300/40 dark:border-sky-900/50 dark:bg-sky-500/10 dark:text-sky-300 dark:hover:border-sky-800 dark:hover:bg-sky-500/20 dark:hover:text-sky-200',
        'warning' => 'border border-amber-100 bg-amber-50 text-amber-700 shadow-sm shadow-amber-500/10 hover:border-amber-200 hover:bg-amber-100 hover:text-amber-800 focus:ring-amber-300/40 dark:border-amber-900/50 dark:bg-amber-500/10 dark:text-amber-300 dark:hover:border-amber-800 dark:hover:bg-amber-500/20 dark:hover:text-amber-200',
        'danger' => 'bg-rose-600 text-white shadow-sm hover:bg-rose-700 focus:ring-rose-300/40',
    ];

    $sizeClasses = [
        'sm' => 'px-3.5 py-2 text-xs',
        'md' => 'px-4 py-2.5 text-sm',
    ];

    $classes = 'inline-flex items-center justify-center gap-2 rounded-xl font-semibold transition-all duration-200 focus:outline-none focus:ring-2 '
        .($variantClasses[$variant] ?? $variantClasses['primary']).' '
        .($sizeClasses[$size] ?? $sizeClasses['md']);

    $resolvedHref = $href;
    $slotText = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $slot)));
    $isBackButton = $href !== null && $slotText === __('core.common.actions.back');

    if ($isBackButton) {
        $fallbackHref = (string) $href;
        $previousHref = url()->previous($fallbackHref);
        $currentUrl = url()->full();
        $currentPathUrl = url()->current();
        $currentHost = parse_url($currentUrl, PHP_URL_HOST);
        $previousHost = parse_url($previousHref, PHP_URL_HOST);
        $sameHost = $previousHost === null || $previousHost === $currentHost;
        $samePage = in_array(rtrim($previousHref, '/'), [rtrim($currentUrl, '/'), rtrim($currentPathUrl, '/')], true);

        $resolvedHref = $sameHost && ! $samePage ? $previousHref : $fallbackHref;
    }
@endphp

@if ($resolvedHref)
    <a href="{{ $resolvedHref }}" {{ $attributes->class($classes) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>
        {{ $slot }}
    </button>
@endif
