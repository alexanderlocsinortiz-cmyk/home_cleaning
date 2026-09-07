<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.pwa-head')
    <title>@yield('title', 'Client') - {{ $siteSettings->website_name }} Client</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    @include('partials.ui-theme')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
@php
    $clientUser = auth()->user();
    $clientInitials = $clientUser->initials;
    $dashboardActive = request()->routeIs('client.dashboard');
    $bookingsActive = request()->routeIs('bookings.index') || request()->routeIs('bookings.show');
    $bookServiceActive = request()->routeIs('bookings.create');
    $serviceAreasActive = request()->routeIs('client.service-areas');
    $profileActive = request()->routeIs('client.profile') || request()->routeIs('client.profile.edit');
    $clientUnreadNotificationCount = $clientUnreadNotificationCount ?? 0;
    $clientNotificationsPreview = $clientNotificationsPreview ?? collect();
@endphp
<body class="min-h-screen bg-gray-50 text-slate-900">
    <header class="sticky top-0 z-30 border-b border-gray-100 bg-white/95 shadow-sm backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-4">
            <a href="{{ route('client.dashboard') }}" class="flex items-center gap-3 text-decoration-none">
                <img src="{{ $siteSettings->logo_url }}" alt="{{ $siteSettings->website_name }}" class="h-14 w-auto">
                <span class="text-lg font-bold text-slate-900 hidden sm:block">{{ $siteSettings->website_name }}</span>
            </a>

            <div class="hidden items-center gap-8 lg:flex">
                <nav class="flex items-center gap-6">
                    <a href="{{ route('client.dashboard') }}" class="border-b-2 pb-1 text-sm {{ $dashboardActive ? 'border-blue-600 font-semibold text-blue-700' : 'border-transparent font-medium text-gray-600 hover:text-gray-900' }}">Dashboard</a>
                    <a href="{{ route('bookings.index') }}" class="border-b-2 pb-1 text-sm {{ $bookingsActive ? 'border-blue-600 font-semibold text-blue-700' : 'border-transparent font-medium text-gray-600 hover:text-gray-900' }}">My Bookings</a>
                    <a href="{{ route('bookings.create') }}" class="border-b-2 pb-1 text-sm {{ $bookServiceActive ? 'border-blue-600 font-semibold text-blue-700' : 'border-transparent font-medium text-gray-600 hover:text-gray-900' }}">Book Service</a>
                    <a href="{{ route('client.service-areas') }}" class="border-b-2 pb-1 text-sm {{ $serviceAreasActive ? 'border-blue-600 font-semibold text-blue-700' : 'border-transparent font-medium text-gray-600 hover:text-gray-900' }}">Service Areas</a>
                </nav>

                <div class="flex items-center gap-3">
                    <div class="relative" data-client-topbar-menu>
                        <button type="button" class="relative flex h-10 w-10 items-center justify-center rounded-full text-blue-900 transition hover:bg-blue-50 hover:text-blue-700" aria-label="Booking notifications" aria-expanded="false" data-client-topbar-toggle="client-notifications-menu">
                            <i class="fas fa-bell text-lg"></i>
                            @if($clientUnreadNotificationCount > 0)
                                <span class="absolute right-2 top-1.5 h-2.5 w-2.5 rounded-full bg-blue-500 ring-2 ring-white">
                                    <span class="sr-only">{{ $clientUnreadNotificationCount > 9 ? '9+' : $clientUnreadNotificationCount }} unread notifications</span>
                                </span>
                            @endif
                        </button>
                        <div id="client-notifications-menu" class="absolute right-0 top-full z-[60] mt-3 hidden w-96 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
                            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                                <div>
                                    <div class="text-sm font-bold text-slate-900">Booking updates</div>
                                    <div class="text-xs text-slate-500">{{ number_format($clientUnreadNotificationCount) }} unread notification{{ $clientUnreadNotificationCount === 1 ? '' : 's' }}</div>
                                </div>
                                @if($clientUnreadNotificationCount > 0)
                                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">{{ $clientUnreadNotificationCount > 9 ? '9+' : $clientUnreadNotificationCount }}</span>
                                @endif
                            </div>
                            <div class="max-h-80 overflow-y-auto p-2">
                                @forelse($clientNotificationsPreview as $notification)
                                    <a href="{{ $notification->link ? url($notification->link) : route('client.dashboard') }}" class="block rounded-lg px-3 py-3 transition hover:bg-blue-50">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full {{ $notification->read_at ? 'bg-slate-300' : 'bg-blue-500' }}"></span>
                                            <div class="min-w-0">
                                                <div class="truncate text-sm font-bold text-slate-900">{{ $notification->title }}</div>
                                                <div class="mt-1 text-xs leading-5 text-slate-500">{{ \Illuminate\Support\Str::limit($notification->message, 100) }}</div>
                                                <div class="mt-1 text-xs font-semibold text-slate-400">{{ optional($notification->created_at)->diffForHumans() }}</div>
                                            </div>
                                        </div>
                                    </a>
                                @empty
                                    <div class="px-3 py-8 text-center text-sm text-slate-500">
                                        No booking updates yet.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('client.profile') }}" class="flex items-center gap-3 rounded-full border px-3 py-2 no-underline transition hover:border-blue-200 hover:bg-blue-50 focus:outline-none focus:ring-4 focus:ring-blue-100 {{ $profileActive ? 'border-blue-200 bg-blue-50' : 'border-slate-200 bg-slate-50' }}" aria-label="Open my profile">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white shadow-sm">
                            {{ $clientInitials }}
                        </div>
                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium text-gray-800">{{ $clientUser->display_name }}</div>
                            <div class="truncate text-xs text-gray-500">{{ $clientUser->email }}</div>
                        </div>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-gray-600 transition hover:text-gray-900">Logout</button>
                    </form>
                </div>
            </div>

            <div class="flex items-center gap-2 lg:hidden">
                <div class="relative" data-client-topbar-menu>
                    <button type="button" class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-blue-900 transition hover:bg-blue-50 hover:text-blue-700" aria-label="Booking notifications" aria-expanded="false" data-client-topbar-toggle="client-mobile-notifications-menu">
                        <i class="fas fa-bell text-base"></i>
                        @if($clientUnreadNotificationCount > 0)
                            <span class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-blue-500 text-[9px] font-bold text-white ring-2 ring-white">{{ $clientUnreadNotificationCount > 9 ? '9+' : $clientUnreadNotificationCount }}</span>
                        @endif
                    </button>
                    <div id="client-mobile-notifications-menu" class="absolute right-0 top-full z-[60] mt-3 hidden w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
                        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                            <div>
                                <div class="text-sm font-bold text-slate-900">Booking updates</div>
                                <div class="text-xs text-slate-500">{{ number_format($clientUnreadNotificationCount) }} unread notification{{ $clientUnreadNotificationCount === 1 ? '' : 's' }}</div>
                            </div>
                            @if($clientUnreadNotificationCount > 0)
                                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">{{ $clientUnreadNotificationCount > 9 ? '9+' : $clientUnreadNotificationCount }}</span>
                            @endif
                        </div>
                        <div class="max-h-72 overflow-y-auto p-2">
                            @forelse($clientNotificationsPreview as $notification)
                                <a href="{{ $notification->link ? url($notification->link) : route('client.dashboard') }}" class="block rounded-lg px-3 py-3 transition hover:bg-blue-50">
                                    <div class="flex items-start gap-3">
                                        <span class="mt-1 h-2 w-2 shrink-0 rounded-full {{ $notification->read_at ? 'bg-slate-300' : 'bg-blue-500' }}"></span>
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-bold text-slate-900">{{ $notification->title }}</div>
                                            <div class="mt-1 text-xs leading-5 text-slate-500">{{ \Illuminate\Support\Str::limit($notification->message, 90) }}</div>
                                            <div class="mt-1 text-xs font-semibold text-slate-400">{{ optional($notification->created_at)->diffForHumans() }}</div>
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="px-3 py-8 text-center text-sm text-slate-500">
                                    No booking updates yet.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <button
                    id="mobile-menu-btn"
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-200 p-2 text-slate-700 hover:bg-slate-100"
                    aria-controls="mobile-menu"
                    aria-expanded="false"
                    aria-label="Toggle client navigation"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>

        <div id="mobile-menu" class="hidden border-t border-slate-100 bg-white px-6 py-4 lg:hidden">
            <a href="{{ route('client.profile') }}" class="mb-4 flex items-center gap-3 rounded-2xl border px-4 py-3 no-underline transition hover:border-blue-200 hover:bg-blue-50 focus:outline-none focus:ring-4 focus:ring-blue-100 {{ $profileActive ? 'border-blue-200 bg-blue-50' : 'border-slate-200 bg-slate-50' }}" aria-label="Open my profile">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white shadow-sm">
                    {{ $clientInitials }}
                </div>
                <div class="min-w-0">
                    <div class="truncate text-sm font-medium text-gray-800">{{ $clientUser->display_name }}</div>
                    <div class="truncate text-xs text-gray-500">{{ $clientUser->email }}</div>
                </div>
            </a>

            <nav class="flex flex-col gap-1">
                <a href="{{ route('client.dashboard') }}" class="block rounded-xl px-3 py-2.5 text-sm {{ $dashboardActive ? 'bg-blue-50 font-semibold text-blue-700' : 'font-medium text-gray-600 hover:bg-blue-50 hover:text-gray-900' }}">Dashboard</a>
                <a href="{{ route('bookings.index') }}" class="block rounded-xl px-3 py-2.5 text-sm {{ $bookingsActive ? 'bg-blue-50 font-semibold text-blue-700' : 'font-medium text-gray-600 hover:bg-blue-50 hover:text-gray-900' }}">My Bookings</a>
                <a href="{{ route('bookings.create') }}" class="block rounded-xl px-3 py-2.5 text-sm {{ $bookServiceActive ? 'bg-blue-50 font-semibold text-blue-700' : 'font-medium text-gray-600 hover:bg-blue-50 hover:text-gray-900' }}">Book Service</a>
                <a href="{{ route('client.service-areas') }}" class="block rounded-xl px-3 py-2.5 text-sm {{ $serviceAreasActive ? 'bg-blue-50 font-semibold text-blue-700' : 'font-medium text-gray-600 hover:bg-blue-50 hover:text-gray-900' }}">Service Areas</a>
                <div class="mt-2 border-t border-slate-100 pt-3">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full rounded-xl px-3 py-2.5 text-left text-sm font-medium text-gray-600 transition hover:bg-slate-50 hover:text-gray-900">Logout</button>
                    </form>
                </div>
            </nav>
        </div>
    </header>

    <script>
    const clientMobileMenuButton = document.getElementById('mobile-menu-btn');
    const clientMobileMenu = document.getElementById('mobile-menu');

    clientMobileMenuButton?.addEventListener('click', function () {
        clientMobileMenu?.classList.toggle('hidden');
        const isExpanded = !clientMobileMenu?.classList.contains('hidden');
        clientMobileMenuButton.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
    });

    const clientTopbarMenus = document.querySelectorAll('[data-client-topbar-menu]');

    function closeClientTopbarMenus(exceptMenu = null) {
        clientTopbarMenus.forEach(function(menu) {
            if (menu === exceptMenu) {
                return;
            }

            const panel = menu.querySelector('[id]');
            const toggle = menu.querySelector('[data-client-topbar-toggle]');
            panel?.classList.add('hidden');
            toggle?.setAttribute('aria-expanded', 'false');
        });
    }

    document.querySelectorAll('[data-client-topbar-toggle]').forEach(function(toggle) {
        toggle.addEventListener('click', function(event) {
            event.stopPropagation();

            const menu = toggle.closest('[data-client-topbar-menu]');
            const panel = document.getElementById(toggle.dataset.clientTopbarToggle);
            const willOpen = panel?.classList.contains('hidden');

            closeClientTopbarMenus(menu);
            panel?.classList.toggle('hidden', !willOpen);
            toggle.setAttribute('aria-expanded', String(willOpen));
        });
    });

    document.addEventListener('click', function(event) {
        if (!event.target.closest('[data-client-topbar-menu]')) {
            closeClientTopbarMenus();
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeClientTopbarMenus();
        }
    });
    </script>

    <main class="min-h-[calc(100vh-81px)] bg-gray-50">
        @yield('content')
    </main>
    @stack('scripts')
    @include('partials.pwa-script')
</body>
</html>
