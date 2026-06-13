@props([
    'name',
    'label' => null,
    'id' => null,
    'type' => 'text',
    'value' => null,
    'hint' => null,
    'oldKey' => null,
    'errorKey' => null,
    'errorBag' => 'default',
    'icon' => null,
    'placeholder' => null,
    'passwordVisibleModel' => null,
])

@php
    $inputId = $id ?: \Illuminate\Support\Str::slug(str_replace(['[', ']', '.'], [' ', ' ', ' '], $name), '-');
    $oldLookupKey = $oldKey ?? $name;
    $errorLookupKey = $errorKey ?? $oldLookupKey;
    $labelText = trim(strip_tags((string) $label));
    $labelLower = mb_strtolower($labelText);
    $isPassword = $type === 'password';
    $passwordVisibleModel = is_string($passwordVisibleModel) ? trim($passwordVisibleModel) : null;
    $hasSharedPasswordVisibility = $isPassword && $passwordVisibleModel !== null && $passwordVisibleModel !== '';
    $showsLocalPasswordToggle = $isPassword && ! $hasSharedPasswordVisibility;
    $isRequired = $attributes->has('required');
    $inputPlaceholder = $placeholder ?? match (true) {
        str_contains($labelLower, 'nom du cycle') => 'Ex : Cycle fondamental',
        str_contains($labelLower, 'nom du niveau') => 'Ex : Niveau CM2',
        str_contains($labelLower, 'nom de la série') => 'Ex : Série A',
        str_contains($labelLower, 'nom de la classe') => 'Ex : 6e A',
        str_contains($labelLower, 'nom de la matière') => 'Ex : Mathématiques',
        str_contains($labelLower, 'nom') && str_contains($labelLower, 'cycle') => 'Ex : Cycle fondamental',
        str_contains($labelLower, 'nom') && str_contains($labelLower, 'niveau') => 'Ex : Niveau CM2',
        str_contains($labelLower, 'nom') && str_contains($labelLower, 'série') => 'Ex : Série A',
        str_contains($labelLower, 'nom') && str_contains($labelLower, 'classe') => 'Ex : 6e A',
        str_contains($labelLower, 'nom') && str_contains($labelLower, 'matière') => 'Ex : Mathématiques',
        str_contains($labelLower, 'code') => 'Ex : CYCLE-01',
        str_contains($labelLower, 'description') => 'Ex : Décrivez brièvement cet élément',
        str_contains($labelLower, 'ordre') => 'Ex : 1',
        str_contains($labelLower, 'capacité') => 'Ex : 40',
        str_contains($labelLower, 'email') => 'Ex : contact@ecole.bj',
        str_contains($labelLower, 'téléphone') || str_contains($labelLower, 'telephone') => 'Ex : +229 01 00 00 00',
        str_contains($labelLower, 'date') => 'Ex : 01/09/2025',
        str_contains($labelLower, 'heure') => 'Ex : 07:30',
        str_contains($labelLower, 'montant') => 'Ex : 15 000',
        $type === 'password' => 'Ex : Votre mot de passe',
        str_contains($labelLower, 'short') => 'Ex : CF',
        default => $labelText !== '' ? 'Ex : '.$labelText : null,
    };
    $inputValue = old($oldLookupKey, $value);
    $inputClasses = 'form-input'.($showsLocalPasswordToggle ? ' pr-11' : '');

    if (is_array($inputValue)) {
        $inputValue = '';
    }

    $inputAttributes = new \Illuminate\View\ComponentAttributeBag(array_merge(
        $attributes->whereStartsWith('x-')->getAttributes(),
        $attributes->whereStartsWith(':')->getAttributes(),
        $attributes->whereStartsWith('@')->getAttributes(),
        $attributes->only(['required', 'autocomplete', 'min', 'max', 'step', 'disabled', 'readonly'])->getAttributes(),
    ));
@endphp

<div @if($showsLocalPasswordToggle) x-data="{ visible: false }" @endif {{ $attributes->except(['x-model', ':value', 'x-bind:value', ':disabled', 'x-bind:disabled', 'x-on:change', '@change'])->class('space-y-2') }}>
    @if ($label)
        <label for="{{ $inputId }}" class="form-label">
            <span class="inline-flex items-center gap-2">
                @if ($icon)
                    <i class="fa-solid {{ $icon }} text-xs text-sky-500 dark:text-sky-300"></i>
                @endif
                <span>{{ $label }}</span>
                @if ($isRequired)
                    <span class="text-rose-500">*</span>
                @else
                    <span class="text-xs font-normal text-gray-400 dark:text-gray-500">(optionnel)</span>
                @endif
            </span>
        </label>
    @endif

    <div class="relative">
        <input
            id="{{ $inputId }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ $inputValue }}"
            placeholder="{{ $inputPlaceholder }}"
            {{ $inputAttributes }}
            @if($hasSharedPasswordVisibility) x-bind:type="{{ $passwordVisibleModel }} ? 'text' : 'password'" @elseif($showsLocalPasswordToggle) x-bind:type="visible ? 'text' : 'password'" @endif
            @class([$inputClasses])
        >

        @if ($showsLocalPasswordToggle)
            <button
                type="button"
                class="absolute inset-y-0 right-0 z-10 flex items-center px-3 text-slate-500 transition hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-sky-300/40 dark:text-slate-400 dark:hover:text-slate-200"
                @click="visible = ! visible"
                :aria-label="visible ? '{{ __('ui.form.password_hide') }}' : '{{ __('ui.form.password_show') }}'"
                :aria-pressed="visible.toString()"
            >
                <x-ui.password-toggle-icon state="visible" />
                <span class="sr-only" x-text="visible ? '{{ __('ui.form.password_hide') }}' : '{{ __('ui.form.password_show') }}'"></span>
            </button>
        @endif
    </div>

    @if ($hint)
        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif

    @error($errorLookupKey, $errorBag)
        <p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>
    @enderror
</div>
