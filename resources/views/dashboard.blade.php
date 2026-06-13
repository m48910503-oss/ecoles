@php
    $portalMode = $dashboard['mode'] ?? 'admin';
    $isPortalMode = in_array($portalMode, ['parent', 'teacher', 'student'], true);
    $moduleRows = collect($dashboard['module_cards'] ?? [])->chunk(4);
    $portalTranslationPrefix = "ui.dashboard.{$portalMode}";
    $portalProfile = $dashboard['portal_profile'] ?? null;
    $portalRelated = $dashboard['portal_related'] ?? null;
    $appShortLabel = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::before(config('app.name', 'ERP Scolaire'), ' '));
@endphp

<x-app-layout>
    @if ($isPortalMode)
        <div class="space-y-6">
            <section class="rounded-[2rem] border border-sky-300/80 bg-gradient-to-r from-sky-100 via-blue-100 to-cyan-100 px-4 py-3 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_30px_rgba(14,165,233,0.10)] transition-colors duration-300 dark:border-sky-900/40 dark:bg-gradient-to-r dark:from-sky-950 dark:via-blue-950/70 dark:to-cyan-950/70 dark:shadow-none sm:px-5">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-500 text-white shadow-md shadow-blue-500/20">
                            <i class="fa-solid {{ $dashboard['welcome_panel']['icon'] }} text-sm"></i>
                        </span>

                        <div class="min-w-0 flex-1">
                            <h1 class="text-lg font-bold tracking-tight text-gray-900 dark:text-white sm:text-xl">
                                {{ $dashboard['headline'] ?? __('ui.dashboard.title') }}
                            </h1>

                            <p class="mt-1 max-w-3xl text-xs leading-relaxed text-gray-600 dark:text-gray-300 sm:text-sm">
                                {{ $dashboard['welcome_panel']['subtitle'] }}
                            </p>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <x-ui.badge tone="sky">{{ $dashboard['welcome_panel']['role'] }}</x-ui.badge>
                                <x-ui.badge tone="slate">{{ $dashboard['welcome_panel']['school_name'] }}</x-ui.badge>
                            </div>
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-wrap items-center gap-3 lg:justify-end">
                        <a
                            href="{{ $dashboard['welcome_panel']['secondary_route'] }}"
                            class="inline-flex items-center gap-2 rounded-full bg-blue-600 px-4 py-2 text-xs font-semibold text-white transition-all duration-200 hover:bg-blue-500 hover:shadow-lg hover:shadow-blue-500/20 sm:text-sm"
                        >
                            <i class="fa-solid fa-id-card text-sm"></i>
                            {{ $dashboard['welcome_panel']['secondary_label'] }}
                        </a>
                    </div>
                </div>
            </section>

            <section class="grid gap-5 xl:grid-cols-3">
                @foreach ($dashboard['stats'] as $card)
                    <x-ui.stats-card
                        :label="$card['label']"
                        :value="$card['value']"
                        :icon="$card['icon']"
                        :tone="$card['tone']"
                    />
                @endforeach
            </section>

            @if ($dashboard['alerts'] !== [])
                <section class="space-y-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __($portalTranslationPrefix.'.alerts_title') }}</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __($portalTranslationPrefix.'.alerts_description') }}</p>
                    </div>

                    <div class="grid gap-4">
                        @foreach ($dashboard['alerts'] as $alert)
                            <x-ui.alert :tone="$alert['tone']" :title="$alert['title']">
                                {{ $alert['description'] }}
                            </x-ui.alert>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="grid gap-5 xl:grid-cols-3">
                <x-ui.card class="space-y-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ $portalProfile['section_label'] ?? __('ui.dashboard.title') }}</p>
                            <h2 class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $portalProfile['title'] ?? $dashboard['welcome_panel']['title'] }}
                            </h2>
                            @if (! empty($portalProfile['description']))
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $portalProfile['description'] }}</p>
                            @endif
                        </div>

                        @if (! empty($portalProfile['status_label']))
                            <x-ui.badge :tone="$portalProfile['status_tone'] ?? 'slate'">
                                {{ $portalProfile['status_label'] }}
                            </x-ui.badge>
                        @endif
                    </div>

                    @if (! empty($portalProfile['fields']))
                        <dl class="grid gap-3 sm:grid-cols-2">
                            @foreach ($portalProfile['fields'] as $field)
                                <div class="rounded-2xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950/60">
                                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400 dark:text-gray-500">{{ $field['label'] }}</dt>
                                    <dd class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ $field['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>

                        @if (! empty($portalProfile['route']) && ! empty($portalProfile['action_label']))
                            <x-ui.button :href="$portalProfile['route']" variant="secondary">
                                {{ $portalProfile['action_label'] }}
                            </x-ui.button>
                        @endif
                    @else
                        <x-ui.empty-state
                            :title="$portalProfile['empty_title'] ?? __('ui.dashboard.title')"
                            :description="$portalProfile['empty_description'] ?? __('ui.dashboard.title')"
                            :icon="$portalProfile['empty_icon'] ?? 'fa-circle-info'"
                        />
                    @endif
                </x-ui.card>

                <x-ui.card class="space-y-5 xl:col-span-2">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500">{{ $portalRelated['section_label'] ?? __('ui.dashboard.title') }}</p>
                            <h2 class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">{{ $portalRelated['title'] ?? __('ui.dashboard.title') }}</h2>
                            @if (! empty($portalRelated['description']))
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $portalRelated['description'] }}</p>
                            @endif
                        </div>

                        @if (! empty($portalRelated['action_route']) && ! empty($portalRelated['action_label']))
                            <x-ui.button :href="$portalRelated['action_route']" variant="secondary" size="sm">
                                {{ $portalRelated['action_label'] }}
                            </x-ui.button>
                        @endif
                    </div>

                    @if (($portalRelated['items'] ?? []) !== [])
                        <div class="grid gap-4 lg:grid-cols-2">
                            @foreach ($portalRelated['items'] as $item)
                                <article class="rounded-2xl border border-gray-100 bg-gray-50 p-4 transition-colors duration-300 dark:border-gray-800 dark:bg-gray-950/60">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $item['title'] }}</h3>
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $item['subtitle'] ?? '—' }}</p>
                                        </div>

                                        @if (! empty($item['badge']))
                                            <x-ui.badge :tone="$item['badge']['tone'] ?? 'slate'">
                                                {{ $item['badge']['label'] }}
                                            </x-ui.badge>
                                        @endif
                                    </div>

                                    @if (! empty($item['badges']))
                                        <div class="mt-4 flex flex-wrap gap-2">
                                            @foreach ($item['badges'] as $badge)
                                                <x-ui.badge :tone="$badge['tone'] ?? 'slate'">
                                                    {{ $badge['label'] }}
                                                </x-ui.badge>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if (! empty($item['route']) && ! empty($item['action_label']))
                                        <div class="mt-4 flex items-center justify-end">
                                            <x-ui.button :href="$item['route']" variant="secondary" size="sm">
                                                {{ $item['action_label'] }}
                                            </x-ui.button>
                                        </div>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty-state
                            :title="$portalRelated['empty_title'] ?? __('ui.dashboard.title')"
                            :description="$portalRelated['empty_description'] ?? __('ui.dashboard.title')"
                            :icon="$portalRelated['empty_icon'] ?? 'fa-circle-info'"
                        />
                    @endif
                </x-ui.card>
            </section>
        </div>
    @else
    <div class="space-y-4">
        <section class="rounded-[1.65rem] border border-sky-300/80 bg-gradient-to-r from-sky-100 via-blue-100 to-cyan-100 px-4 py-3.5 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_30px_rgba(14,165,233,0.10)] transition-colors duration-300 dark:border-sky-900/40 dark:bg-gradient-to-r dark:from-sky-950 dark:via-blue-950/70 dark:to-cyan-950/70 dark:shadow-none sm:px-5">
            <div class="flex flex-col gap-2.5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-blue-500 text-white shadow-[0_10px_22px_rgba(59,130,246,0.22)]">
                        <i class="fa-solid {{ $dashboard['welcome_panel']['icon'] }} text-base"></i>
                    </span>

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[1.05rem] font-bold tracking-tight text-gray-900 dark:text-white sm:text-[1.45rem]">
                            {{ $dashboard['welcome_panel']['title'] }}
                        </h2>

                         <p class="mt-1 max-w-3xl text-[0.92rem] leading-relaxed text-gray-600 dark:text-gray-300">
                            {{ $dashboard['welcome_panel']['subtitle'] }}
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-3 lg:justify-end">
                    <button
                        type="button"
                        @click="guideOpen = true"
                        class="inline-flex items-center gap-2 rounded-full bg-blue-600 px-3.5 py-1.5 text-[0.92rem] font-semibold text-white transition-all duration-200 hover:bg-blue-500 hover:shadow-lg hover:shadow-blue-500/20"
                    >
                        <i class="fa-solid fa-circle-play text-sm"></i>
                        {{ __('ui.dashboard.welcome.actions.open_guide') }}
                     </button>

                     <a
                        href="{{ $dashboard['welcome_panel']['secondary_route'] }}"
                        class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3.5 py-1.5 text-[0.92rem] font-medium text-slate-700 transition-all duration-200 hover:border-blue-200 hover:text-blue-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-blue-800 dark:hover:text-blue-300"
                    >
                        {{ $dashboard['welcome_panel']['secondary_label'] }}
                    </a>
                </div>
            </div>
        </section>

        <section class="flex items-center justify-between">
            <h1 class="text-[1.65rem] font-bold text-gray-800 dark:text-white">{{ __('ui.dashboard.title') }}</h1>

            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <i class="fa-solid fa-house text-sm"></i>
                {{ $appShortLabel }}
            </div>
        </section>

        <section class="grid gap-3 xl:grid-cols-4">
            @foreach ($dashboard['financial_cards'] as $card)
                <x-dashboard.metric-card
                    :label="$card['label']"
                    :subtitle="$card['subtitle']"
                    :value="$card['value']"
                    :trend="$card['trend']"
                    :icon="$card['icon']"
                    :tone="$card['tone']"
                />
            @endforeach
        </section>

        <div class="space-y-3">
            @foreach ($moduleRows as $row)
                <section class="grid gap-3 xl:grid-cols-4">
                    @foreach ($row as $card)
                        <x-dashboard.module-card
                            :label="$card['label']"
                            :value="$card['value']"
                            :icon="$card['icon']"
                            :tone="$card['tone']"
                            :route="$card['route']"
                        />
                    @endforeach
                </section>
            @endforeach
        </div>

        <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04),0_12px_28px_rgba(15,23,42,0.03)] transition-colors duration-300 dark:border-gray-800 dark:bg-gray-900 dark:shadow-none">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('ui.dashboard.recent_activity.title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('ui.dashboard.recent_activity.subtitle') }}</p>
                </div>

                <span class="inline-flex items-center gap-2 self-start rounded-full border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-semibold text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <i class="fa-solid fa-wave-square text-sky-500"></i>
                    {{ count($dashboard['recent_activity']) }}
                </span>
            </div>

            @if ($dashboard['recent_activity'] !== [])
                <div class="mt-5 grid gap-3 lg:grid-cols-2 2xl:grid-cols-4">
                    @foreach ($dashboard['recent_activity'] as $activity)
                        <article class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 transition-colors duration-300 dark:border-gray-800 dark:bg-gray-950/60">
                            <div class="flex items-start gap-3">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-sky-100 text-sm font-bold text-sky-700 dark:bg-sky-500/15 dark:text-sky-300">
                                    {{ $activity['action'] }}
                                </span>

                                <div class="min-w-0">
                                    <h3 class="text-sm font-semibold leading-6 text-gray-900 dark:text-white">{{ $activity['title'] }}</h3>
                                    <p class="mt-1 text-xs font-medium text-gray-500 dark:text-gray-400">{{ $activity['actor'] }}</p>
                                    <p class="mt-2 text-xs leading-relaxed text-gray-400 dark:text-gray-500">{{ $activity['meta'] }}</p>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="mt-5 rounded-2xl border border-dashed border-gray-200 bg-gray-50/80 px-4 py-5 text-sm text-gray-500 transition-colors duration-300 dark:border-gray-800 dark:bg-gray-950/50 dark:text-gray-400">
                    {{ __('ui.dashboard.recent_activity.empty') }}
                </div>
            @endif
        </section>
    </div>

    <div
        x-show="guideOpen"
        x-cloak
        class="fixed inset-0 z-[70] flex items-center justify-center bg-gray-950/60 px-4 py-8 backdrop-blur-sm"
    >
        <div
            x-show="guideOpen"
            x-transition.opacity.scale.duration.200ms
            @click.outside="guideOpen = false"
            class="w-full max-w-4xl rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl transition-colors duration-300 dark:border-gray-800 dark:bg-gray-900 md:p-8"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.28em] text-sky-600 dark:bg-sky-500/10 dark:text-sky-300 [font-family:'JetBrains_Mono',monospace]">
                        <i class="fa-solid fa-circle-play text-[0.7rem]"></i>
                        {{ __('ui.dashboard.quick_guide') }}
                    </span>
                    <h2 class="mt-4 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ __('ui.dashboard.guide_title') }}</h2>
                    <p class="mt-3 max-w-2xl text-base leading-relaxed text-gray-500 dark:text-gray-400">
                        {{ __('ui.dashboard.guide_intro') }}
                    </p>
                </div>

                <button
                    type="button"
                    @click="guideOpen = false"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 transition-all duration-200 hover:border-rose-300 hover:text-rose-600 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300"
                >
                    <span class="sr-only">{{ __('ui.layout.close_guide') }}</span>
                    <i class="fa-solid fa-xmark text-base"></i>
                </button>
            </div>

            <div class="mt-8 grid gap-4 md:grid-cols-3">
                @foreach ($dashboard['guide_steps'] as $step)
                    <article class="rounded-2xl border border-gray-200 bg-gray-50 p-5 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800 dark:bg-gray-950/60 dark:hover:shadow-none">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-sky-500 text-white shadow">
                            <i class="fa-solid {{ $step['icon'] }} text-lg"></i>
                        </span>
                        <h3 class="mt-4 text-xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $step['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-gray-500 dark:text-gray-400">{{ $step['description'] }}</p>
                    </article>
                @endforeach
            </div>

            <div class="mt-8 rounded-2xl bg-gray-50 p-5 transition-colors duration-300 dark:bg-gray-800/80">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-gray-400 dark:text-gray-500 [font-family:'JetBrains_Mono',monospace]">{{ __('ui.dashboard.snapshot_badge') }}</p>
                        <p class="mt-2 text-base font-medium text-gray-900 dark:text-white">{{ __('ui.dashboard.snapshot_subtitle') }}</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        @foreach ($dashboard['core_snapshot'] as $stat)
                            <div class="rounded-xl bg-white px-4 py-3 text-center shadow-sm transition-colors duration-300 dark:bg-gray-900 dark:shadow-none">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-gray-400 dark:text-gray-500">{{ $stat['label'] }}</p>
                                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $stat['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</x-app-layout>
