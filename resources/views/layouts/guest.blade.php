<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ERP Scolaire') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(16,185,129,0.18),_transparent_34%),linear-gradient(180deg,_#f8fafc,_#e2e8f0)] px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto flex min-h-[calc(100vh-4rem)] max-w-6xl items-center">
                <div class="grid w-full gap-10 lg:grid-cols-[1.1fr_0.9fr]">
                    <section class="hidden rounded-[2rem] bg-slate-950 p-10 text-white shadow-2xl shadow-slate-900/20 lg:flex lg:flex-col lg:justify-between">
                        <div>
                            <div class="flex items-center gap-3">
                                <x-application-logo />
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-emerald-300">Afrique francophone</p>
                                    <p class="text-lg font-semibold">ERP scolaire SaaS</p>
                                </div>
                            </div>

                            <h1 class="mt-10 max-w-xl text-4xl font-semibold leading-tight">
                                Une base solide pour piloter les utilisateurs, les rôles, la sécurité et l’année académique.
                            </h1>

                            <p class="mt-6 max-w-xl text-base leading-7 text-slate-300">
                                Connexion sécurisée, audit des actions critiques et interface claire pour les écoles privées et publiques.
                            </p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                                <p class="text-sm font-semibold text-white">Socle modulaire</p>
                                <p class="mt-2 text-sm leading-6 text-slate-300">Pensé pour accueillir progressivement les 14 modules métier.</p>
                            </div>
                            <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                                <p class="text-sm font-semibold text-white">Sécurité native</p>
                                <p class="mt-2 text-sm leading-6 text-slate-300">Permissions backend, comptes suspendus et journal d’audit centralisé.</p>
                            </div>
                        </div>
                    </section>

                    <section class="page-shell mx-auto w-full max-w-xl p-6 sm:p-8">
                        <div class="mb-8 flex items-center gap-3 lg:hidden">
                            <x-application-logo />
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-emerald-700">ERP scolaire</p>
                                <p class="text-sm font-semibold text-slate-900">{{ config('app.name') }}</p>
                            </div>
                        </div>

                        <x-app.flash />

                        {{ $slot }}
                    </section>
                </div>
            </div>
        </div>
    </body>
</html>
