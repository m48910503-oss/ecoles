<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$event->title" :description="$event->description ?: __('academic.events.index.description')">
            <x-slot:actions>
                <x-ui.button :href="route('academic.events.index')" variant="secondary">
                    {{ __('core.common.actions.back') }}
                </x-ui.button>
                @can('update', $event)
                    <x-ui.button :href="route('academic.events.edit', $event)" variant="secondary">
                        {{ __('core.common.actions.edit') }}
                    </x-ui.button>
                @endcan
                @can('publish', $event)
                    @if ($event->status !== \App\Enums\Academic\CalendarEventStatus::Published && $event->status !== \App\Enums\Academic\CalendarEventStatus::Cancelled && $event->status !== \App\Enums\Academic\CalendarEventStatus::Archived)
                        <form method="POST" action="{{ route('academic.events.publish', $event) }}">
                            @csrf
                            <x-ui.button type="submit">
                                {{ __('academic.events.show.publish_action') }}
                            </x-ui.button>
                        </form>
                    @endif
                @endcan
                @can('archive', $event)
                    @if ($event->status !== \App\Enums\Academic\CalendarEventStatus::Archived)
                        <form method="POST" action="{{ route('academic.events.archive', $event) }}">
                            @csrf
                            <x-ui.button type="submit" variant="secondary">
                                {{ __('academic.events.show.archive_action') }}
                            </x-ui.button>
                        </form>
                    @endif
                @endcan
            </x-slot:actions>
        </x-ui.page-header>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-6 xl:grid-cols-[1fr_0.95fr]">
            <x-ui.card class="space-y-5">
                <div class="flex flex-wrap gap-2">
                    <x-ui.badge :tone="$event->event_type->tone()">{{ $event->event_type->label() }}</x-ui.badge>
                    <x-ui.badge :tone="$event->status->tone()">{{ $event->status->label() }}</x-ui.badge>
                    <x-ui.badge :tone="$event->visibility->tone()">{{ $event->visibility->label() }}</x-ui.badge>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.events.show.calendar') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $event->calendar->name }}</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $event->calendar->academicYear->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.events.show.period') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $event->period?->name ?? __('academic.events.show.period_not_linked') }}</p>
                        @if ($event->period)
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $event->period->start_date?->translatedFormat('d M Y') }} - {{ $event->period->end_date?->translatedFormat('d M Y') }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.events.show.target') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">
                            {{ $event->targetReferenceLabel() }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.common.date') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $event->start_datetime?->translatedFormat('d M Y H:i') }}</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $event->end_datetime?->translatedFormat('d M Y H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.events.form.location') }}</p>
                        <p class="mt-2 text-base font-semibold text-gray-900 dark:text-white">{{ $event->location ?: '—' }}</p>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card class="space-y-4">
                <div class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.events.show.created_by') }}</p>
                        <p class="mt-2 font-medium text-gray-900 dark:text-white">{{ $event->creator?->name ?? __('core.audit_logs.index.system') }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.events.show.validated_by') }}</p>
                        <p class="mt-2 font-medium text-gray-900 dark:text-white">{{ $event->validator?->name ?? '—' }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $event->validated_at?->translatedFormat('d M Y H:i') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ __('academic.events.show.published_by') }}</p>
                        <p class="mt-2 font-medium text-gray-900 dark:text-white">{{ $event->publisher?->name ?? '—' }}</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $event->published_at?->translatedFormat('d M Y H:i') ?? '—' }}</p>
                    </div>
                    @if ($event->cancellation_reason)
                        <div class="rounded-2xl border border-rose-200 bg-rose-50/80 p-4 dark:border-rose-800/60 dark:bg-rose-950/20">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-500">{{ __('academic.events.show.cancelled_by') }}</p>
                            <p class="mt-2 font-medium text-rose-800 dark:text-rose-200">{{ $event->canceller?->name ?? '—' }}</p>
                            <p class="mt-1 text-xs text-rose-600/90 dark:text-rose-300">{{ $event->cancelled_at?->translatedFormat('d M Y H:i') ?? '—' }}</p>
                            <p class="mt-3 text-sm leading-relaxed text-rose-700 dark:text-rose-300">{{ $event->cancellation_reason }}</p>
                        </div>
                    @endif
                </div>
            </x-ui.card>
        </section>

        @can('cancel', $event)
            @if ($event->status !== \App\Enums\Academic\CalendarEventStatus::Cancelled && $event->status !== \App\Enums\Academic\CalendarEventStatus::Archived)
                <x-ui.card>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('academic.events.show.cancel_title') }}</h2>
                    <form method="POST" action="{{ route('academic.events.cancel', $event) }}" class="mt-5 space-y-4">
                        @csrf
                        <x-form.textarea name="cancellation_reason" :label="__('academic.events.show.cancel_reason')" :value="null" icon="fa-comment-slash" required />
                        <div class="flex flex-wrap gap-3">
                            <x-ui.button type="submit" variant="danger">
                                <i class="fa-solid fa-ban text-xs"></i>
                                {{ __('academic.events.show.cancel_action') }}
                            </x-ui.button>
                        </div>
                    </form>
                </x-ui.card>
            @endif
        @endcan

        @can('delete', $event)
            @if ($event->status !== \App\Enums\Academic\CalendarEventStatus::Published)
                <x-ui.card>
                    <x-ui.confirm-modal
                        :name="'delete-academic-event-'.$event->id"
                        :action="route('academic.events.destroy', $event)"
                        :description="__('academic.events.show.delete_confirm')"
                        :trigger-label="__('academic.events.show.delete_action')"
                        trigger-size="md"
                    />
                </x-ui.card>
            @endif
        @endcan
    </div>
</x-app-layout>
