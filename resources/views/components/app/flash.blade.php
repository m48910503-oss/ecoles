@php
    $errorBag = session('errors');
    $errorMessages = [];

    if ($errorBag instanceof \Illuminate\Support\ViewErrorBag) {
        foreach ($errorBag->getBags() as $bag) {
            $errorMessages = array_merge($errorMessages, $bag->all());
        }

        $errorMessages = array_values(array_unique($errorMessages));
    }

    $status = session('status');
    $statusMessages = [
        'profile-updated' => __('core.common.feedback.profile_updated'),
        'password-updated' => __('core.common.feedback.password_updated'),
        'verification-link-sent' => __('core.common.feedback.verification_link_sent'),
    ];

    $flash = null;

    if (session()->has('success') || session()->has('status')) {
        $message = session('success') ?? ($statusMessages[$status] ?? (string) $status);

        $flash = [
            'tone' => 'success',
            'title' => __('core.common.feedback.success_title'),
            'message' => $message !== '' ? (string) $message : null,
            'icon' => 'fa-circle-check',
        ];
    } elseif ($errorMessages !== []) {
        $flash = [
            'tone' => 'error',
            'title' => __('core.common.feedback.validation_title'),
            'message' => null,
            'icon' => 'fa-triangle-exclamation',
        ];
    } elseif (session()->has('error')) {
        $flash = [
            'tone' => 'error',
            'title' => __('core.common.feedback.error_title'),
            'message' => (string) session('error'),
            'icon' => 'fa-triangle-exclamation',
        ];
    } elseif (session()->has('warning')) {
        $flash = [
            'tone' => 'warning',
            'title' => __('core.common.feedback.warning_title'),
            'message' => (string) session('warning'),
            'icon' => 'fa-circle-exclamation',
        ];
    } elseif (session()->has('info')) {
        $flash = [
            'tone' => 'info',
            'title' => __('core.common.feedback.info_title'),
            'message' => (string) session('info'),
            'icon' => 'fa-circle-info',
        ];
    }

    $tone = $flash['tone'] ?? 'success';
    $showModal = $flash !== null;
@endphp

@if ($showModal)
    <div
        x-data="{
            open: @js($showModal),
            close() {
                this.open = false;
            },
        }"
        x-show="open"
        x-cloak
        x-on:keydown.escape.window="close()"
        x-effect="document.body.classList.toggle('overflow-y-hidden', open)"
        x-transition.opacity.duration.200ms
        data-tone="{{ $tone }}"
        class="flash-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="flash-modal-title"
    >
        <div class="flash-modal__overlay" x-on:click="close()"></div>

        <div
            x-show="open"
            x-transition:enter="ease-out duration-250"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="ease-in duration-180"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-2"
            class="flash-modal__panel"
        >
            <div class="flash-modal__inner">
                <div class="flash-modal__icon">
                    <i class="fa-solid {{ $flash['icon'] }} flash-modal__glyph"></i>
                </div>

                <div class="flash-modal__content">
                    <h2 id="flash-modal-title" class="flash-modal__title">
                        {{ $flash['title'] }}
                    </h2>

                    @if ($flash['message'])
                        <p class="flash-modal__body">
                            {{ $flash['message'] }}
                        </p>
                    @endif
                </div>

                @if ($errorMessages !== [])
                    <div class="flash-modal__details">
                        <p class="flash-modal__details-title">
                            {{ __('core.common.feedback.validation_details') }}
                        </p>

                        <ul class="flash-modal__details-list">
                            @foreach ($errorMessages as $error)
                                <li class="flex items-start gap-3">
                                    <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-current/70"></span>
                                    <span>{{ $error }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mt-8">
                    <button type="button" x-on:click="close()" class="flash-modal__ok">
                        {{ __('core.common.actions.ok') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
