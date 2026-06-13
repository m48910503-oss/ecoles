@props([
    'paginator',
])

@if ($paginator->hasPages())
    <div {{ $attributes->class('pt-2') }}>
        {{ $paginator->links() }}
    </div>
@endif
