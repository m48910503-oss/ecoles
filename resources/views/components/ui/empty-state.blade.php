@props([
    'title',
    'description',
    'icon' => 'fa-inbox',
])

<div {{ $attributes->class('rounded-2xl border border-dashed border-gray-200 bg-gray-50/80 px-5 py-8 text-center transition-colors duration-300 dark:border-gray-700 dark:bg-gray-950/50') }}>
    <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-full bg-sky-100 text-sky-600 dark:bg-sky-500/15 dark:text-sky-300">
        <i class="fa-solid {{ $icon }} text-lg"></i>
    </span>

    <h3 class="mt-4 text-base font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
    <p class="mx-auto mt-2 max-w-2xl text-sm leading-relaxed text-gray-500 dark:text-gray-400">{{ $description }}</p>

    @isset($actions)
        <div class="mt-5 flex flex-wrap justify-center gap-3">
            {{ $actions }}
        </div>
    @endisset
</div>
