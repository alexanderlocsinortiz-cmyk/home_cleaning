<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Home Cleaning Service') }}</title>
    @include('partials.pwa-head')
    @include('partials.ui-theme')
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <div class="relative">
        <main class="relative mx-auto flex min-h-screen w-full max-w-6xl items-center px-6 py-16 sm:px-8 lg:px-12">
            <div class="grid w-full gap-10 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)] lg:items-center">
                <section class="space-y-6">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/6 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-secondary-200">
                        Home Cleaning Service
                    </span>

                    <div class="space-y-4">
                        <h1 class="max-w-3xl text-4xl font-black tracking-tight text-white sm:text-5xl lg:text-6xl">
                            The project entry page is now aligned with the live application.
                        </h1>
                        <p class="max-w-2xl text-base leading-8 text-slate-300 sm:text-lg">
                            This route is not the main homepage anymore. Use the live public site, sign in to your account, or jump straight into the admin, client, or staff experience from the active routes.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-full bg-primary-500 px-5 py-3 text-sm font-bold text-white transition hover:bg-primary-400">
                            Open Public Homepage
                        </a>

                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-full border border-white/12 bg-white/8 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/12">
                                Sign In
                            </a>
                        @endif

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-full border border-white/12 bg-white/8 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/12">
                                Create Account
                            </a>
                        @endif
                    </div>
                </section>

                <aside class="rounded-[28px] border border-white/10 bg-white/8 p-6 shadow-[0_30px_80px_rgba(2,6,23,0.45)] backdrop-blur">
                    <div class="rounded-[24px] border border-white/10 bg-slate-950/55 p-6">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Current Entry Points</div>
                                <div class="mt-2 text-2xl font-black text-white">Live Application Routes</div>
                            </div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-500/15 text-sm font-bold text-primary-300">
                                HC
                            </div>
                        </div>

                        <div class="mt-6 space-y-3">
                            <div class="rounded-2xl border border-white/8 bg-white/5 px-4 py-4">
                                <div class="text-sm font-semibold text-white">Public Site</div>
                                <div class="mt-1 text-sm text-slate-400">Landing page, pricing, service information, and registration flow.</div>
                            </div>
                            <div class="rounded-2xl border border-white/8 bg-white/5 px-4 py-4">
                                <div class="text-sm font-semibold text-white">Client Portal</div>
                                <div class="mt-1 text-sm text-slate-400">Booking creation, history, service tracking, and profile management.</div>
                            </div>
                            <div class="rounded-2xl border border-white/8 bg-white/5 px-4 py-4">
                                <div class="text-sm font-semibold text-white">Staff Portal</div>
                                <div class="mt-1 text-sm text-slate-400">Assignments, schedule, performance, notifications, and service areas.</div>
                            </div>
                            <div class="rounded-2xl border border-white/8 bg-white/5 px-4 py-4">
                                <div class="text-sm font-semibold text-white">Admin Panel</div>
                                <div class="mt-1 text-sm text-slate-400">Operations, attendance, bookings, analytics, reports, and service management.</div>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </main>
    </div>
</body>
</html>
