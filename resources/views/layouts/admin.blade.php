<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.pwa-head')
    <title>@yield('title', 'Admin') - Home Cleaning Service Admin</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    @include('partials.ui-theme')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="admin-ui bg-slate-50 flex min-h-screen overflow-x-hidden">
    <div class="fixed inset-0 z-90 hidden bg-black/50 backdrop-blur-[2px]" id="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside class="admin-sidebar fixed left-0 top-0 z-100 flex h-screen w-64 flex-col bg-slate-800" id="admin-sidebar">
        <div class="p-4 border-b border-slate-700 flex items-center gap-3">
            <img src="{{ asset('images/logo.png') }}" alt="Home Cleaning Service" class="h-10 w-auto shrink-0">
            <div>
                <div class="text-sm font-bold text-white leading-tight">Home Cleaning</div>
                <small class="block text-xs text-slate-400 font-normal">Admin Panel</small>
            </div>
        </div>
        <nav class="py-4 flex-1 overflow-y-auto">
            <div class="px-6 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mt-4">Main</div>
            <a href="{{ route('admin.dashboard') }}" class="mx-3 flex items-center gap-3 rounded-lg border-l-4 border-transparent px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white hover:border-accent-500 {{ request()->routeIs('admin.dashboard') ? 'border-accent-500 bg-accent-500/15 text-accent-200' : '' }}">
                <i class="fas fa-tachometer-alt w-5 text-center"></i> Dashboard
            </a>
            <div class="px-6 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mt-4">Management</div>
            <a href="{{ route('admin.customers') }}" class="mx-3 flex items-center gap-3 rounded-lg border-l-4 border-transparent px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white hover:border-accent-500 {{ request()->routeIs('admin.customers*') ? 'border-accent-500 bg-accent-500/15 text-accent-200' : '' }}">
                <i class="fas fa-users w-5 text-center"></i> Customers
            </a>
            <a href="{{ route('admin.bookings') }}" class="mx-3 flex items-center gap-3 rounded-lg border-l-4 border-transparent px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white hover:border-accent-500 {{ request()->routeIs('admin.bookings') ? 'border-accent-500 bg-accent-500/15 text-accent-200' : '' }}">
                <i class="fas fa-calendar-check w-5 text-center"></i>
                <span class="flex-1">Bookings</span>
                @if($pendingBookingsCount)
                    <span class="ml-auto inline-flex h-5 w-5 items-center justify-center rounded-full bg-amber-400 text-xs font-bold text-slate-900">{{ $pendingBookingsCount }}</span>
                @endif
            </a>
            <a href="{{ route('admin.services.index') }}" class="mx-3 flex items-center gap-3 rounded-lg border-l-4 border-transparent px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white hover:border-accent-500 {{ request()->routeIs('admin.services*') ? 'border-accent-500 bg-accent-500/15 text-accent-200' : '' }}">
                <i class="fas fa-concierge-bell w-5 text-center"></i> Services
            </a>
            <a href="{{ route('admin.staff.index') }}" class="mx-3 flex items-center gap-3 rounded-lg border-l-4 border-transparent px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white hover:border-accent-500 {{ request()->routeIs('admin.staff.*') ? 'border-accent-500 bg-accent-500/15 text-accent-200' : '' }}">
                <i class="fas fa-user-tie w-5 text-center"></i> Staff
            </a>
            <a href="{{ route('admin.attendance') }}" class="mx-3 flex items-center gap-3 rounded-lg border-l-4 border-transparent px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white hover:border-accent-500 {{ request()->routeIs('admin.attendance*') ? 'border-accent-500 bg-accent-500/15 text-accent-200' : '' }}">
                <i class="fas fa-fingerprint w-5 text-center"></i> Attendance
            </a>
            <a href="{{ route('admin.reports') }}" class="mx-3 flex items-center gap-3 rounded-lg border-l-4 border-transparent px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white hover:border-accent-500 {{ request()->routeIs('admin.reports') ? 'border-accent-500 bg-accent-500/15 text-accent-200' : '' }}">
                <i class="fas fa-chart-bar w-5 text-center"></i> Reports
            </a>
            <a href="{{ route('admin.analytics') }}" class="mx-3 flex items-center gap-3 rounded-lg border-l-4 border-transparent px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white hover:border-accent-500 {{ request()->routeIs('admin.analytics') ? 'border-accent-500 bg-accent-500/15 text-accent-200' : '' }}">
                <i class="fas fa-chart-line w-5 text-center"></i> Analytics
            </a>
            <a href="{{ route('admin.logs') }}" class="mx-3 flex items-center gap-3 rounded-lg border-l-4 border-transparent px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white hover:border-accent-500 {{ request()->routeIs('admin.logs') ? 'border-accent-500 bg-accent-500/15 text-accent-200' : '' }}">
                <i class="fas fa-clipboard-list w-5 text-center"></i> Logs
            </a>
            <a href="{{ route('admin.service-areas') }}" class="mx-3 flex items-center gap-3 rounded-lg border-l-4 border-transparent px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white hover:border-accent-500 {{ request()->routeIs('admin.service-areas') ? 'border-accent-500 bg-accent-500/15 text-accent-200' : '' }}">
                <i class="fas fa-map-marked-alt w-5 text-center"></i> Service Areas
            </a>
        </nav>
        <div class="p-6 border-t border-slate-700 shrink-0">
            <a href="{{ route('home') }}" class="flex items-center gap-3 text-slate-300 hover:text-white transition-colors py-2">
                <i class="fas fa-globe"></i> View Website
            </a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="flex items-center gap-3 text-slate-300 hover:text-white transition-colors py-2 w-full text-left">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </form>
        </div>
    </aside>

    <!-- Main -->
    <div class="admin-main ml-64 flex-1 min-w-0 flex flex-col min-h-screen overflow-x-hidden">
        <div class="admin-topbar bg-white px-8 py-4 border-b border-slate-200 flex items-center sticky top-0 z-50">
            <button
                class="admin-mobile-hamburger mr-3 h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50"
                id="admin-sidebar-toggle"
                type="button"
                aria-controls="admin-sidebar"
                aria-expanded="false"
                onclick="toggleSidebar()">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#475569" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div class="flex min-w-0 flex-1 items-center justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="text-xl font-bold text-slate-800">@yield('page-title', 'Dashboard')</h1>
                    <p class="text-sm text-slate-500">@yield('page-subtitle', 'Welcome to Home Cleaning Service Admin')</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-accent-600 rounded-full flex items-center justify-center text-white font-bold">
                        {{ auth()->user()->initials }}
                    </div>
                    <span class="text-sm font-semibold text-slate-800">{{ auth()->user()->display_name }}</span>
                </div>
            </div>
        </div>
        <div class="flex-1 min-w-0 overflow-x-hidden bg-slate-50 p-8">
            @yield('content')
        </div>
    </div>

@stack('scripts')
@include('partials.pwa-script')
<script>
function toggleSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const toggle = document.getElementById('admin-sidebar-toggle');
    const willOpen = !sidebar.classList.contains('sidebar-open');

    sidebar.classList.toggle('sidebar-open', willOpen);
    overlay.classList.toggle('hidden', !willOpen);
    toggle?.setAttribute('aria-expanded', String(willOpen));
}

document.querySelectorAll('.admin-sidebar a').forEach(function(link) {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 900) {
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const toggle = document.getElementById('admin-sidebar-toggle');
            sidebar.classList.remove('sidebar-open');
            overlay.classList.add('hidden');
            toggle?.setAttribute('aria-expanded', 'false');
        }
    });
});
</script>
</body>
</html>
