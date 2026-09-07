<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.pwa-head')
    <title>@yield('title', 'Cleaner Portal') - {{ $siteSettings->website_name }} Cleaners</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    @include('partials.ui-theme')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="admin-ui staff-ui bg-slate-50 flex min-h-screen overflow-x-hidden">
    <div class="fixed inset-0 z-50 hidden bg-black/50" id="provider-sidebar-overlay" onclick="toggleProviderSidebar()"></div>

    <aside class="admin-sidebar staff-sidebar fixed left-0 top-0 z-50 flex min-h-screen w-64 flex-col bg-gradient-to-b from-blue-950 via-blue-900 to-blue-800 shadow-2xl shadow-blue-950/30" id="provider-sidebar">
        <div class="flex items-center gap-3 p-5">
            <img src="{{ $siteSettings->logo_url }}" alt="{{ $siteSettings->website_name }}" class="h-12 w-auto shrink-0">
            <span class="min-w-0 text-lg font-bold leading-tight text-white">{{ $siteSettings->website_name }}</span>
            <button type="button" class="ml-auto hidden h-9 w-9 items-center justify-center rounded-lg text-blue-100 transition hover:bg-white/10 hover:text-white max-[900px]:flex" aria-label="Close navigation" onclick="toggleProviderSidebar()">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <nav class="flex-1 px-4 py-3" aria-label="Cleaner portal navigation">
            <div class="mt-3 px-2 py-2 text-xs font-black uppercase tracking-wider text-blue-200/70">Cleaner Portal</div>
            <a href="{{ route('provider.dashboard') }}" aria-current="{{ request()->routeIs('provider.dashboard') ? 'page' : 'false' }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-blue-100 transition-all hover:bg-white/10 hover:text-white {{ request()->routeIs('provider.dashboard') ? 'bg-blue-600 text-white shadow-lg shadow-blue-950/20' : '' }}">
                <i class="fas fa-house w-5 text-center"></i> Dashboard
            </a>
            <div class="mt-5 px-2 py-2 text-xs font-black uppercase tracking-wider text-blue-200/70">Operations</div>
            <a href="{{ route('provider.bookings') }}" aria-current="{{ request()->routeIs('provider.bookings*') ? 'page' : 'false' }}" class="flex items-center gap-3 rounded-xl px-4 py-3 text-blue-100 transition-all hover:bg-white/10 hover:text-white {{ request()->routeIs('provider.bookings*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-950/20' : '' }}">
                <i class="fas fa-calendar-check w-5 text-center"></i> Assigned Bookings
            </a>
            <a href="{{ route('provider.payouts') }}" aria-current="{{ request()->routeIs('provider.payouts') ? 'page' : 'false' }}" class="mt-1 flex items-center gap-3 rounded-xl px-4 py-3 text-blue-100 transition-all hover:bg-white/10 hover:text-white {{ request()->routeIs('provider.payouts') ? 'bg-blue-600 text-white shadow-lg shadow-blue-950/20' : '' }}">
                <i class="fas fa-wallet w-5 text-center"></i> Payouts
            </a>
            <div class="mt-5 px-2 py-2 text-xs font-black uppercase tracking-wider text-blue-200/70">Account</div>
            <a href="{{ route('provider.dashboard') }}#availability" class="flex items-center gap-3 rounded-xl px-4 py-3 text-blue-100 transition-all hover:bg-white/10 hover:text-white">
                <i class="fas fa-toggle-on w-5 text-center"></i> Availability
            </a>
            <a href="{{ route('provider.dashboard') }}#payout-setup" class="mt-1 flex items-center gap-3 rounded-xl px-4 py-3 text-blue-100 transition-all hover:bg-white/10 hover:text-white">
                <i class="fas fa-id-card w-5 text-center"></i> Payout Setup
            </a>
        </nav>
        <div class="p-4">
            <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-xl px-4 py-2 text-blue-100 transition-colors hover:bg-white/10 hover:text-white">
                <i class="fas fa-globe"></i> View Website
            </a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-4 py-2 text-left text-blue-100 transition-colors hover:bg-white/10 hover:text-white">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </form>
        </div>
    </aside>

    <div class="admin-main ml-64 flex min-h-screen min-w-0 flex-1 flex-col overflow-x-hidden">
        <div class="staff-topbar admin-topbar sticky top-0 z-50 flex items-center border-b border-slate-200 bg-white/95 px-8 py-4 shadow-sm backdrop-blur">
            <button
                class="staff-mobile-hamburger mr-2.5 h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50"
                id="provider-sidebar-toggle"
                type="button"
                aria-controls="provider-sidebar"
                aria-expanded="false"
                onclick="toggleProviderSidebar()">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#1E40AF" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div class="flex min-w-0 flex-1 items-center justify-between gap-4">
                <div>
                    <h1 class="page-title text-xl font-bold text-slate-800">@yield('page-title', 'Cleaner Dashboard')</h1>
                    <p class="page-subtitle text-sm text-slate-500">@yield('page-subtitle', 'Manage your cleaner account')</p>
                </div>
                <div class="relative flex items-center gap-4">
                    <button
                        type="button"
                        class="relative hidden h-10 w-10 items-center justify-center rounded-full text-slate-600 transition hover:bg-slate-100 sm:flex"
                        aria-label="Open quick reminders"
                        aria-controls="provider-reminders"
                        aria-expanded="false"
                        onclick="toggleProviderReminders()">
                        <i class="fas fa-bell"></i>
                    </button>
                    <div id="provider-reminders" class="absolute right-0 top-12 z-50 hidden w-80 overflow-hidden rounded-2xl border border-slate-200 bg-white text-left shadow-xl shadow-slate-900/10">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <div class="text-sm font-black text-slate-950">Quick reminders</div>
                            <div class="mt-1 text-xs text-slate-500">Useful shortcuts for your cleaner account</div>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <a href="{{ route('provider.bookings') }}" class="block px-4 py-3 transition hover:bg-blue-50">
                                <div class="text-sm font-bold text-slate-900">Check assigned bookings</div>
                                <div class="mt-1 text-xs text-slate-500">Review pending or active cleaner assignments.</div>
                            </a>
                            <a href="{{ route('provider.dashboard') }}#availability" class="block px-4 py-3 transition hover:bg-blue-50">
                                <div class="text-sm font-bold text-slate-900">Keep availability updated</div>
                                <div class="mt-1 text-xs text-slate-500">Pause assignments if you cannot accept more work.</div>
                            </a>
                            <a href="{{ route('provider.dashboard') }}#payout-setup" class="block px-4 py-3 transition hover:bg-blue-50">
                                <div class="text-sm font-bold text-slate-900">Complete payout setup</div>
                                <div class="mt-1 text-xs text-slate-500">Upload documents before payouts can be released.</div>
                            </a>
                        </div>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 font-bold text-white">
                        {{ auth()->user()->initials }}
                    </div>
                    <span class="hidden text-sm font-semibold text-slate-800 lg:block">{{ auth()->user()->display_name }}</span>
                    <i class="fas fa-chevron-down hidden text-xs text-slate-500 lg:block"></i>
                </div>
            </div>
        </div>
        <div class="admin-content min-w-0 flex-1 overflow-x-hidden bg-slate-50 p-0">
            @yield('content')
        </div>
    </div>

<script>
function toggleProviderSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.getElementById('provider-sidebar-overlay');
    const toggle = document.getElementById('provider-sidebar-toggle');
    const willOpen = !sidebar.classList.contains('sidebar-open');

    sidebar.classList.toggle('sidebar-open', willOpen);
    overlay.classList.toggle('hidden', !willOpen);
    toggle?.setAttribute('aria-expanded', String(willOpen));
}
function toggleProviderReminders() {
    const panel = document.getElementById('provider-reminders');
    const button = document.querySelector('[aria-controls="provider-reminders"]');
    const willOpen = panel.classList.contains('hidden');

    panel.classList.toggle('hidden', !willOpen);
    button?.setAttribute('aria-expanded', String(willOpen));
}
document.addEventListener('click', function(event) {
    const panel = document.getElementById('provider-reminders');
    const button = document.querySelector('[aria-controls="provider-reminders"]');

    if (!panel || panel.classList.contains('hidden')) {
        return;
    }

    if (!panel.contains(event.target) && !button?.contains(event.target)) {
        panel.classList.add('hidden');
        button?.setAttribute('aria-expanded', 'false');
    }
});
document.querySelectorAll('.admin-sidebar a').forEach(function(link) {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 900) {
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.getElementById('provider-sidebar-overlay');
            const toggle = document.getElementById('provider-sidebar-toggle');

            sidebar.classList.remove('sidebar-open');
            overlay.classList.add('hidden');
            toggle?.setAttribute('aria-expanded', 'false');
        }
    });
});
</script>
@stack('scripts')
@include('partials.pwa-script')
</body>
</html>
