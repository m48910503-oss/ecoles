@props([
    'title',
    'description' => null,
])

<div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div class="min-w-0">
        <h1 class="page-title">{{ $title }}</h1>

        @if ($description)
            <p class="page-subtitle max-w-3xl">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-3 lg:justify-end">
            {{ $actions }}
        </div>
    @endisset
</div>
