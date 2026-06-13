<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'ERP Scolaire') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.18),_transparent_28%),radial-gradient(circle_at_bottom_right,_rgba(15,23,42,0.12),_transparent_32%),linear-gradient(180deg,_#f8fafc,_#e2e8f0)]">
            <div class="mx-auto flex min-h-screen max-w-7xl flex-col px-4 py-8 sm:px-6 lg:px-8">
                <header class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <x-application-logo />
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-emerald-700">Bénin et Afrique francophone</p>
                            <p class="text-lg font-semibold text-slate-950">{{ config('app.name') }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn-primary">Accéder au tableau de bord</a>
                        @else
                            <a href="{{ route('login') }}" class="btn-primary">Se connecter</a>
                        @endauth
                    </div>
                </header>

                <main class="grid flex-1 items-center gap-12 py-12 lg:grid-cols-[1.05fr_0.95fr] lg:py-20">
                    <section>
                        <p class="text-sm font-semibold uppercase tracking-[0.3em] text-emerald-700">Plateforme SaaS de gestion scolaire</p>
                        <h1 class="mt-6 max-w-4xl text-5xl font-semibold leading-tight tracking-tight text-slate-950">
                            Un ERP scolaire propre, modulaire et sécurisé pour les établissements francophones.
                        </h1>
                        <p class="mt-6 max-w-3xl text-lg leading-8 text-slate-600">
                            Le socle initial couvre l’authentification, les utilisateurs, les rôles, les permissions, l’année académique active, les paramètres globaux et le journal d’audit.
                        </p>

                        <div class="mt-8 flex flex-wrap gap-3">
                            @auth
                                <a href="{{ route('dashboard') }}" class="btn-primary">Ouvrir le Core System</a>
                            @else
                                <a href="{{ route('login') }}" class="btn-primary">Connexion sécurisée</a>
                            @endauth
                            <a href="#fonctionnalites" class="btn-secondary">Voir le périmètre initial</a>
                        </div>
                    </section>

                    <section class="page-shell p-8 sm:p-10">
                        <div class="grid gap-4 sm:grid-cols-2" id="fonctionnalites">
                            <div class="page-card bg-slate-950 text-white">
                                <p class="text-sm font-semibold">Authentification</p>
                                <p class="mt-3 text-sm leading-7 text-slate-300">Connexion, réinitialisation de mot de passe et contrôle des comptes suspendus.</p>
                            </div>
                            <div class="page-card">
                                <p class="text-sm font-semibold text-slate-950">Autorisations</p>
                                <p class="mt-3 text-sm leading-7 text-slate-600">Rôles multiples, permissions backend et menus adaptés au rôle connecté.</p>
                            </div>
                            <div class="page-card">
                                <p class="text-sm font-semibold text-slate-950">Année académique</p>
                                <p class="mt-3 text-sm leading-7 text-slate-600">Une seule année académique active à la fois, prête pour les modules suivants.</p>
                            </div>
                            <div class="page-card">
                                <p class="text-sm font-semibold text-slate-950">Traçabilité</p>
                                <p class="mt-3 text-sm leading-7 text-slate-600">Audit des connexions et des opérations sensibles du Core System.</p>
                            </div>
                        </div>
                    </section>
                </main>
            </div>
        </div>
    </body>
</html>
