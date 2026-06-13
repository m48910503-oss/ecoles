@props([
    'name',
    'label' => null,
    'id' => null,
    'value' => null,
    'rows' => 4,
    'hint' => null,
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
    $labelText = trim(strip_tags((string) $label));
    $labelLower = mb_strtolower($labelText);
    $isRequired = $attributes->has('required');
    $inputPlaceholder = $placeholder ?? match (true) {
        str_contains($labelLower, 'description') => 'Ex : Décrivez brièvement cet élément',
        str_contains($labelLower, 'note') => 'Ex : Observation utile',
        str_contains($labelLower, 'commentaire') => 'Ex : Précision interne',
        str_contains($labelLower, 'nom du cycle') => 'Ex : Cycle fondamental',
        str_contains($labelLower, 'nom') && str_contains($labelLower, 'cycle') => 'Ex : Cycle fondamental',
        default => $labelText !== '' ? 'Ex : '.$labelText : null,
    };
    $textareaValue = old($oldLookupKey, $value);

    if (is_array($textareaValue)) {
        $textareaValue = '';
    }

    $textareaAttributes = new \Illuminate\View\ComponentAttributeBag(array_merge(
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

    <textarea
        id="{{ $inputId }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $inputPlaceholder }}"
        {{ $textareaAttributes }}
        class="form-input"
    >{{ $textareaValue }}</textarea>

    @if ($hint)
        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif

    @error($errorLookupKey, $errorBag)
        <p class="text-xs font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>
    @enderror
</div>
