@props([
    'name',
    'label' => null,
    'id' => null,
    'value' => null,
    'oldKey' => null,
    'errorKey' => null,
    'errorBag' => 'default',
    'icon' => null,
    'placeholder' => null,
])

@php
    $inputId = $id ?: \Illuminate\Support\Str::slug(str_replace(['[', ']', '.'], [' ', ' ', ' '], $name), '-');
    $oldLookupKey = $oldKey ?? $name;
    $errorLookupKey = $errorKey ?? $oldLookupKey;
    $selectedValue = old($oldLookupKey, $value);
    $isRequired = $attributes->has('required');
    $selectAttributes = new \Illuminate\View\ComponentAttributeBag(array_merge(
        $attributes->whereStartsWith('x-')->getAttributes(),
        $attributes->whereStartsWith(':')->getAttributes(),
        $attributes->whereStartsWith('@')->getAttributes(),
        $attributes->only(['required', 'disabled'])->getAttributes(),
    ));
@endphp

<div {{ $attributes->except(['x-model', ':disabled', 'x-bind:disabled', 'x-on:change', '@change'])->class('space-y-2') }}>
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

    <select
        id="{{ $inputId }}"
        name="{{ $name }}"
        {{ $selectAttributes }}
        class="form-input"
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        {{ $slot }}
    </select>

    @error($errorLookupKey, $errorBag)
        <p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>
    @enderror
</div>
