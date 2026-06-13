@props([
    'disabled' => false,
    'passwordVisibleModel' => null,
])

@php
    $type = $attributes->get('type', 'text');
    $isPassword = $type === 'password';
    $passwordVisibleModel = is_string($passwordVisibleModel) ? trim($passwordVisibleModel) : null;
    $hasSharedPasswordVisibility = $isPassword && $passwordVisibleModel !== null && $passwordVisibleModel !== '';
    $showsLocalPasswordToggle = $isPassword && ! $hasSharedPasswordVisibility;
    $inputClasses = 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm'.($showsLocalPasswordToggle ? ' pr-11' : '');
@endphp

@if ($isPassword && $showsLocalPasswordToggle)
    <div x-data="{ visible: false }" class="relative w-full">
        <input
            @disabled($disabled)
            {{ $attributes->except('type')->merge(['type' => 'password', 'class' => $inputClasses]) }}
            x-bind:type="visible ? 'text' : 'password'"
        >

        <button
            type="button"
            class="absolute inset-y-0 right-0 z-10 flex items-center px-3 text-slate-500 transition hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 dark:text-slate-400 dark:hover:text-slate-200"
            @click="visible = ! visible"
            :aria-label="visible ? '{{ __('ui.form.password_hide') }}' : '{{ __('ui.form.password_show') }}'"
            :aria-pressed="visible"
        >
            <x-ui.password-toggle-icon state="visible" />
            <span class="sr-only" x-text="visible ? '{{ __('ui.form.password_hide') }}' : '{{ __('ui.form.password_show') }}'"></span>
        </button>
    </div>
@elseif ($isPassword && $hasSharedPasswordVisibility)
    <input
        @disabled($disabled)
        {{ $attributes->except('type')->merge(['type' => 'password', 'class' => $inputClasses]) }}
        x-bind:type="{{ $passwordVisibleModel }} ? 'text' : 'password'"
    >
@else
    <input @disabled($disabled) {{ $attributes->merge(['class' => $inputClasses]) }}>
@endif
