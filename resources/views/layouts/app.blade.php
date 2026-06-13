<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ERP Scolaire') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
        @stack('head')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    @php
        $shell = $shell ?? [
            'school_name' => config('app.name'),
            'support_email' => 'support@ecoles.test',
            'active_academic_year' => __('ui.dashboard.not_configured'),
            'user_name' => auth()->user()?->name ?? __('ui.roles.user'),
            'user_role' => __('ui.roles.user'),
            'user_email' => auth()->user()?->email ?? '',
            'user_initials' => 'ES',
        ];
        $availableLocales = config('app.available_locales', ['fr' => 'Français', 'en' => 'English']);

        $bodyClasses = trim($__env->yieldPushContent('body-class')) ?: "min-h-full bg-gray-100 text-gray-800 antialiased [font-family:'Plus_Jakarta_Sans',sans-serif] dark:bg-gray-900 dark:text-gray-100";
        $shellClasses = trim($__env->yieldPushContent('shell-class')) ?: 'min-h-screen bg-gray-100 transition-colors duration-300 dark:bg-gray-900';
        $mainClasses = trim($__env->yieldPushContent('main-class')) ?: 'soft-scrollbar flex-1 overflow-y-auto px-5 py-6 pb-28 sm:px-6 lg:px-7 lg:py-7 lg:pb-32';
    @endphp

    <body class="{{ $bodyClasses }}">
        <div x-data="appShell()" x-on:keydown.escape.window="closeOverlays()" class="{{ $shellClasses }}">
            <header class="fixed left-0 right-0 top-0 z-50 flex h-16 items-center justify-between border-b border-gray-100 bg-white px-4 shadow-sm shadow-slate-900/5 transition-colors duration-300 dark:border-gray-800 dark:bg-gray-900 dark:shadow-none sm:px-5 lg:px-6">
                <div class="flex items-center gap-4">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <x-application-logo class="h-8 w-8 rounded-xl shadow-md shadow-sky-500/15" />
                        <span class="text-lg font-bold text-sky-600 dark:text-sky-400">{{ config('app.name') }}</span>
                    </a>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-gray-500 transition-all duration-200 hover:bg-gray-100 hover:text-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-300/40 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-sky-300"
                        @click="toggleSidebar()"
                    >
                        <span class="sr-only">{{ __('ui.layout.toggle_sidebar') }}</span>
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>
                </div>

                <div class="hidden items-center gap-3 lg:flex">
                    <button
                        type="button"
                        @click="guideOpen = true"
                        class="inline-flex items-center gap-2 rounded-full bg-sky-500 px-4 py-2 text-xs font-semibold text-white shadow transition-all duration-200 hover:bg-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-300/40"
                    >
                        <span class="h-2 w-2 rounded-full bg-white"></span>
                        {{ __('ui.layout.open_guide') }}
                    </button>

                    <div class="relative">
                        <button
                            type="button"
                            @click="academicMenuOpen = ! academicMenuOpen; profileMenuOpen = false"
                            class="inline-flex items-center gap-2 rounded-xl border border-sky-300 px-3.5 py-2.5 text-xs font-semibold text-sky-700 transition-all duration-200 hover:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-sky-300/40 dark:border-sky-700 dark:text-sky-300 dark:hover:bg-sky-900/20"
                        >
                            <i class="fa-solid fa-calendar-days text-sm"></i>
                            {{ __('ui.layout.academic_year') }} : {{ $shell['active_academic_year'] }}
                            <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="{ 'rotate-180': academicMenuOpen }"></i>
                        </button>

                        <div
                            x-show="academicMenuOpen"
                            x-transition.opacity.scale.origin.top.right.duration.200ms
                            @click.outside="academicMenuOpen = false"
                            class="absolute right-0 top-[calc(100%+0.75rem)] w-72 rounded-2xl border border-gray-200 bg-white p-4 shadow-xl transition-colors duration-300 dark:border-gray-800 dark:bg-gray-900"
                        >
                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-gray-400 dark:text-gray-500 [font-family:'JetBrains_Mono',monospace]">{{ __('ui.layout.active_year') }}</p>
                            <p class="mt-3 text-xl font-semibold text-gray-900 dark:text-white">{{ $shell['active_academic_year'] }}</p>
                            <p class="mt-2 text-sm leading-relaxed text-gray-500 dark:text-gray-400">{{ __('ui.layout.active_year_help') }}</p>
                        </div>
                    </div>

                    @if (($schoolSwitcher['enabled'] ?? false) === true)
                        <div class="relative">
                            <button
                                type="button"
                                @click="schoolMenuOpen = ! schoolMenuOpen; academicMenuOpen = false; profileMenuOpen = false"
                                class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-3.5 py-2.5 text-xs font-semibold text-gray-700 transition-all duration-200 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-sky-300/40 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                            >
                                <i class="fa-solid fa-building-columns text-sm text-sky-500 dark:text-sky-300"></i>
                                {{ $shell['school_name'] }}
                                <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="{ 'rotate-180': schoolMenuOpen }"></i>
                            </button>

                            <div
                                x-show="schoolMenuOpen"
                                x-transition.opacity.scale.origin.top.right.duration.200ms
                                @click.outside="schoolMenuOpen = false"
                                class="absolute right-0 top-[calc(100%+0.75rem)] w-80 rounded-2xl border border-gray-200 bg-white p-3 shadow-xl transition-colors duration-300 dark:border-gray-800 dark:bg-gray-900"
                            >
                                <p class="px-2 text-xs font-semibold uppercase tracking-[0.24em] text-gray-400 dark:text-gray-500">{{ __('ui.layout.school_switch') }}</p>

                                <div class="mt-3 space-y-1">
                                    @foreach (($schoolSwitcher['schools'] ?? []) as $schoolOption)
                                        <form method="POST" action="{{ route('core.schools.switch', $schoolOption['id']) }}">
                                            @csrf
                                            <button
                                                type="submit"
                                                @disabled((int) ($schoolSwitcher['current_school_id'] ?? 0) === (int) $schoolOption['id'])
                                                class="{{ (int) ($schoolSwitcher['current_school_id'] ?? 0) === (int) $schoolOption['id'] ? 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-800 dark:bg-sky-900/20 dark:text-sky-300' : 'border-transparent text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800' }} flex w-full items-center justify-between rounded-xl border px-3 py-3 text-left transition-all duration-200"
                                            >
                                                <span>
                                                    <span class="block text-sm font-semibold">{{ $schoolOption['name'] }}</span>
                                                    <span class="mt-1 block text-xs uppercase tracking-[0.16em] text-gray-400 dark:text-gray-500">{{ $schoolOption['code'] }}</span>
                                                </span>
                                                @if ((int) ($schoolSwitcher['current_school_id'] ?? 0) === (int) $schoolOption['id'])
                                                    <i class="fa-solid fa-circle-check text-sm text-sky-500 dark:text-sky-300"></i>
                                                @endif
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="toggleTheme()"
                        class="rounded-lg p-2 text-gray-500 transition-all duration-200 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-sky-300/40 dark:text-gray-400 dark:hover:bg-gray-800"
                    >
                        <span class="sr-only">{{ __('ui.layout.change_theme') }}</span>
                        <i x-show="theme === 'light'" class="fa-solid fa-moon text-lg"></i>
                        <i x-show="theme === 'dark'" class="fa-solid fa-sun text-lg text-yellow-400"></i>
                    </button>

                    <button
                        type="button"
                        class="rounded-lg p-2 text-gray-500 transition-all duration-200 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-sky-300/40 dark:text-gray-400 dark:hover:bg-gray-800"
                    >
                        <span class="sr-only">{{ __('ui.layout.notifications') }}</span>
                        <i class="fa-solid fa-bell text-lg"></i>
                    </button>

                    <div
                        class="hidden items-center gap-1 rounded-full border border-gray-200 bg-white p-1 shadow-sm shadow-slate-900/5 dark:border-gray-800 dark:bg-gray-900 md:inline-flex"
                        role="group"
                        aria-label="{{ __('ui.layout.language_switch') }}"
                    >
                        @foreach ($availableLocales as $localeCode => $localeLabel)
                            <form method="POST" action="{{ route('locale.switch', ['locale' => $localeCode]) }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="{{ app()->currentLocale() === $localeCode ? 'bg-sky-500 text-white shadow-sm shadow-sky-500/20' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white' }} min-w-[3rem] rounded-full px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] transition-all duration-200"
                                    @disabled(app()->currentLocale() === $localeCode)
                                    title="{{ $localeLabel }}"
                                >
                                    {{ strtoupper($localeCode) }}
                                </button>
                            </form>
                        @endforeach
                    </div>

                    <div class="relative">
                        <button
                            type="button"
                            @click="profileMenuOpen = ! profileMenuOpen; academicMenuOpen = false"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 bg-white text-left transition-all duration-200 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-sky-300/40 dark:border-gray-800 dark:bg-gray-900 dark:hover:bg-gray-800"
                        >
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-xs font-bold uppercase tracking-[0.14em] text-gray-700 dark:bg-gray-700 dark:text-gray-100">
                                {{ $shell['user_initials'] }}
                            </span>
                        </button>

                        <div
                            x-show="profileMenuOpen"
                            x-transition.opacity.scale.origin.top.right.duration.200ms
                            @click.outside="profileMenuOpen = false"
                            class="absolute right-0 top-[calc(100%+0.75rem)] w-72 rounded-2xl border border-gray-200 bg-white p-3 shadow-xl transition-colors duration-300 dark:border-gray-800 dark:bg-gray-900"
                        >
                            <div class="rounded-xl bg-gray-50 px-4 py-4 transition-colors duration-300 dark:bg-gray-800">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $shell['user_name'] }}</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $shell['user_email'] }}</p>
                            </div>

                            <div class="mt-3 space-y-1">
                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-gray-600 transition-all duration-200 hover:bg-gray-50 hover:text-sky-700 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-sky-300">
                                    <i class="fa-solid fa-user text-sm"></i>
                                    {{ __('ui.layout.profile') }}
                                </a>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium text-rose-600 transition-all duration-200 hover:bg-rose-50 dark:hover:bg-rose-500/10">
                                        <i class="fa-solid fa-arrow-right-from-bracket text-sm"></i>
                                        {{ __('ui.layout.logout') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="flex min-h-screen pt-16">
                @include('layouts.navigation')

                <main class="ml-0 {{ $mainClasses }}" :class="sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-60'">
                    <x-app.flash />

                    @isset($header)
                        <div class="mb-6">
                            {{ $header }}
                        </div>
                    @endisset

                    {{ $slot }}
                </main>
            </div>

            <div class="fixed bottom-6 right-6 z-50">
                <div class="group flex items-center gap-2">
                    <span class="pointer-events-none hidden translate-x-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 opacity-0 shadow-lg transition-all duration-300 group-hover:translate-x-0 group-hover:opacity-100 group-focus-within:translate-x-0 group-focus-within:opacity-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 sm:inline-flex">
                        {{ __('ui.layout.help') }}
                    </span>
                <a
                    href="mailto:{{ $shell['support_email'] }}"
                    class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-lg transition-all duration-300 hover:scale-105 hover:shadow-purple-300/50"
                >
                    <i class="fa-brands fa-whatsapp text-xl"></i>
                </a>
                </div>
            </div>
        </div>
    </body>
</html>
