<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="__('core.audit_logs.index.title')" :description="__('core.audit_logs.index.description')" />
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-ui.stats-card :label="__('core.audit_logs.index.stats.total')" :value="$stats['total']" icon="fa-file-shield" tone="sky" />
            <x-ui.stats-card :label="__('core.audit_logs.index.stats.today')" :value="$stats['today']" icon="fa-clock" tone="emerald" />
            <x-ui.stats-card :label="__('core.audit_logs.index.stats.last_7_days')" :value="$stats['last_7_days']" icon="fa-wave-square" tone="violet" />
            <x-ui.stats-card :label="__('core.audit_logs.index.stats.modules')" :value="$stats['modules']" icon="fa-layer-group" tone="amber" />
        </section>

        <x-ui.card>
            <form method="GET" data-auto-submit class="grid gap-3 xl:grid-cols-[minmax(0,1.2fr)_220px_auto_auto]">
                <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('core.audit_logs.index.search_placeholder') }}" class="form-input">
                <select name="module" class="form-input">
                    <option value="">{{ __('core.common.filters.all_modules') }}</option>
                    @foreach ($modules as $availableModule)
                        <option value="{{ $availableModule }}" @selected($module === $availableModule)>{{ strtoupper($availableModule) }}</option>
                    @endforeach
                </select>

                <x-ui.button type="submit" variant="secondary">
                    <i class="fa-solid fa-filter text-xs"></i>
                    {{ __('core.common.actions.filter') }}
                </x-ui.button>

                <x-ui.button :href="route('core.audit-logs.index')" variant="ghost">
                    {{ __('core.common.actions.reset') }}
                </x-ui.button>
            </form>
        </x-ui.card>

        @if ($auditLogs->count() > 0)
            <section class="space-y-4">
                @foreach ($auditLogs as $auditLog)
                    <x-ui.card as="article">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-ui.badge tone="slate">{{ strtoupper($auditLog->module) }}</x-ui.badge>
                                    <x-ui.badge tone="emerald">{{ $auditLog->action }}</x-ui.badge>
                                </div>

                                <p class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">{{ $auditLog->description ?? __('core.audit_logs.index.system') }}</p>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('core.audit_logs.index.performed_by', [
                                        'user' => $auditLog->user?->name ?? __('core.audit_logs.index.system'),
                                        'date' => $auditLog->created_at?->translatedFormat('d M Y H:i:s'),
                                    ]) }}
                                </p>
                            </div>

                            <div class="grid gap-2 text-sm text-gray-500 dark:text-gray-400">
                                <p>{{ __('core.audit_logs.index.method') }} : {{ $auditLog->method ?? 'N/A' }}</p>
                                <p>{{ __('core.audit_logs.index.ip_address') }} : {{ $auditLog->ip_address ?? 'N/A' }}</p>
                            </div>
                        </div>

                        @if ($auditLog->old_values || $auditLog->new_values)
                            <details class="mt-4 rounded-2xl border border-gray-200 bg-gray-50/80 p-4 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-950/50 dark:text-gray-300">
                                <summary class="cursor-pointer font-medium text-gray-900 dark:text-white">{{ __('core.audit_logs.index.details') }}</summary>
                                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                                    <div>
                                        <p class="mb-2 font-semibold text-gray-900 dark:text-white">{{ __('core.audit_logs.index.old_values') }}</p>
                                        <pre class="soft-scrollbar overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs text-slate-100">{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                    <div>
                                        <p class="mb-2 font-semibold text-gray-900 dark:text-white">{{ __('core.audit_logs.index.new_values') }}</p>
                                        <pre class="soft-scrollbar overflow-x-auto rounded-2xl bg-slate-950 p-4 text-xs text-slate-100">{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </div>
                                </div>
                            </details>
                        @endif
                    </x-ui.card>
                @endforeach
            </section>
        @else
            <x-ui.empty-state :title="__('core.audit_logs.index.empty.title')" :description="__('core.audit_logs.index.empty.description')" icon="fa-file-shield" />
        @endif

        <x-ui.pagination :paginator="$auditLogs" />
    </div>
</x-app-layout>
