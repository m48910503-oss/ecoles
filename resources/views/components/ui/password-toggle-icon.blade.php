@props([
    'state' => 'visible',
])

@php
    $stateExpression = is_string($state) ? trim($state) : 'visible';
@endphp

<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5 shrink-0" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5 9.75 7.5 9.75 7.5-3.75 7.5-9.75 7.5S2.25 12 2.25 12Z" />
    <circle cx="12" cy="12" r="3" />
    <path
        x-show="{{ $stateExpression }}"
        x-cloak
        stroke-linecap="round"
        stroke-linejoin="round"
        d="M4 4l16 16"
    />
</svg>
