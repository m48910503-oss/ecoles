@props([
    'as' => 'section',
    'padding' => 'p-5',
    'variant' => 'surface',
])

@php
    $variantClasses = [
        'surface' => 'page-card--surface',
        'metric' => 'page-card--metric',
        'callout' => 'page-card--callout',
        'dense' => 'page-card--dense',
    ];

    $variantClass = $variantClasses[$variant] ?? $variantClasses['surface'];
@endphp

<{{ $as }} {{ $attributes->class("page-card {$variantClass} {$padding}") }}>
    {{ $slot }}
</{{ $as }}>
