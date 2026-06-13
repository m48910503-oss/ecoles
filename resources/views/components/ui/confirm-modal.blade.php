@props([
    'name',
    'action',
    'method' => 'DELETE',
    'title' => __('core.common.modals.delete_title'),
    'description' => __('core.common.modals.delete_description'),
    'warning' => null,
    'confirmLabel' => __('core.common.actions.confirm_delete'),
    'cancelLabel' => __('core.common.actions.cancel'),
    'triggerLabel' => __('core.common.actions.delete'),
    'triggerVariant' => 'danger',
    'triggerSize' => 'sm',
    'icon' => 'fa-trash-can',
    'maxWidth' => 'xl',
])

@php
    $httpMethod = strtoupper($method);
@endphp

<div class="contents">
    <x-ui.button
        type="button"
        :variant="$triggerVariant"
        :size="$triggerSize"
        x-on:click.prevent="$dispatch('open-modal', '{{ $name }}')"
    >
        <i class="fa-solid {{ $icon }} text-xs"></i>
        {{ $triggerLabel }}
    </x-ui.button>

    <x-modal :name="$name" :max-width="$maxWidth" focusable>
        <div dir="ltr" class="rounded-[1.9rem] border border-gray-200 bg-white p-6 text-left shadow-2xl shadow-slate-900/10 dark:border-slate-700/80 dark:bg-slate-900 dark:shadow-[0_24px_80px_rgba(2,6,23,0.55)]">
            <div class="flex items-start gap-4">
                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300">
                    <i class="fa-solid {{ $icon }} text-lg"></i>
                </span>

                <div class="min-w-0 flex-1">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-gray-500 dark:text-gray-400">{{ $description }}</p>
                </div>
            </div>

            @if ($warning)
                <div class="mt-5 rounded-2xl border border-amber-300 bg-amber-50/95 p-4 shadow-sm shadow-slate-900/5 dark:border-amber-700/70 dark:bg-amber-950/55">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-200">
                            <i class="fa-solid fa-triangle-exclamation text-sm"></i>
                        </span>

                        <div class="min-w-0 flex-1 text-left">
                            <p class="text-sm font-semibold text-amber-900 dark:text-amber-100">
                                {{ __('core.common.feedback.warning_title') }}
                            </p>
                            <p class="mt-1 text-sm leading-relaxed text-amber-800 dark:text-amber-200">
                                {{ $warning }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <x-ui.button type="button" variant="secondary" x-on:click="$dispatch('close-modal', '{{ $name }}')">
                    <i class="fa-solid fa-xmark text-xs"></i>
                    {{ $cancelLabel }}
                </x-ui.button>

                <form method="POST" action="{{ $action }}">
                    @csrf
                    @if ($httpMethod !== 'POST')
                        @method($httpMethod)
                    @endif

                    <x-ui.button type="submit" variant="danger">
                        <i class="fa-solid {{ $icon }} text-xs"></i>
                        {{ $confirmLabel }}
                    </x-ui.button>
                </form>
            </div>
        </div>
    </x-modal>
</div>
