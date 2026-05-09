<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.pwa-head')
    <title>@yield('title', 'Staff') - Home Cleaning Service Staff</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    @include('partials.ui-theme')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@stack('styles')
</head>
<body class="bg-slate-100 flex min-h-screen">
    <div class="fixed inset-0 z-50 hidden bg-black/50" id="staff-sidebar-overlay" onclick="toggleStaffSidebar()"></div>

    <aside class="admin-sidebar fixed left-0 top-0 z-50 flex min-h-screen w-64 flex-col bg-slate-800" id="staff-sidebar">
        <div class="p-4 border-b border-slate-700 flex items-center gap-3">
            <img src="{{ asset('images/logo.png') }}" alt="Home Cleaning Service" class="h-10 w-auto shrink-0">
            <div>
                <div class="text-sm font-bold text-white leading-tight">Home Cleaning</div>
                <small class="block text-xs text-slate-400 font-normal">Staff Portal</small>
            </div>
        </div>
        <nav class="py-4 flex-1">
            <div class="px-6 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mt-4">Main</div>
            <a href="{{ route('staff.dashboard') }}" class="flex items-center gap-3 border-r-4 border-transparent px-6 py-3 text-slate-300 transition-all hover:border-accent-500 hover:bg-accent-500/10 hover:text-white {{ request()->routeIs('staff.dashboard') ? 'border-accent-500 bg-accent-500/10 text-white' : '' }}">
                <i class="fas fa-tachometer-alt w-5 text-center"></i> Dashboard
            </a>
            <div class="px-6 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mt-4">Operations</div>
            <a href="{{ route('staff.performance') }}" class="flex items-center gap-3 border-r-4 border-transparent px-6 py-3 text-slate-300 transition-all hover:border-accent-500 hover:bg-accent-500/10 hover:text-white {{ request()->routeIs('staff.performance') ? 'border-accent-500 bg-accent-500/10 text-white' : '' }}">
                <i class="fas fa-chart-line w-5 text-center"></i> My Performance
            </a>
            <a href="{{ route('staff.schedule') }}" class="flex items-center gap-3 border-r-4 border-transparent px-6 py-3 text-slate-300 transition-all hover:border-accent-500 hover:bg-accent-500/10 hover:text-white {{ request()->routeIs('staff.schedule') ? 'border-accent-500 bg-accent-500/10 text-white' : '' }}">
                <i class="fas fa-calendar-alt w-5 text-center"></i> Schedule
            </a>
            <a href="{{ route('staff.bookings') }}" class="flex items-center gap-3 border-r-4 border-transparent px-6 py-3 text-slate-300 transition-all hover:border-accent-500 hover:bg-accent-500/10 hover:text-white {{ request()->routeIs('staff.bookings') ? 'border-accent-500 bg-accent-500/10 text-white' : '' }}">
                <i class="fas fa-calendar-check w-5 text-center"></i> My Bookings
            </a>
            <a href="{{ route('staff.service-areas') }}" class="flex items-center gap-3 border-r-4 border-transparent px-6 py-3 text-slate-300 transition-all hover:border-accent-500 hover:bg-accent-500/10 hover:text-white {{ request()->routeIs('staff.service-areas') ? 'border-accent-500 bg-accent-500/10 text-white' : '' }}">
                <i class="fas fa-map-marked-alt w-5 text-center"></i> Service Areas
            </a>
            <a href="{{ route('staff.profile') }}" class="flex items-center gap-3 border-r-4 border-transparent px-6 py-3 text-slate-300 transition-all hover:border-accent-500 hover:bg-accent-500/10 hover:text-white {{ request()->routeIs('staff.profile') ? 'border-accent-500 bg-accent-500/10 text-white' : '' }}">
                <i class="fas fa-user w-5 text-center"></i> Profile
            </a>
        </nav>
        <div class="p-6 border-t border-slate-700">
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

    <div class="admin-main ml-64 flex-1 flex flex-col min-h-screen">
        <div class="staff-topbar admin-topbar sticky top-0 z-50 flex items-center border-b border-slate-200 bg-white px-8 py-4">
            <button
                class="staff-mobile-hamburger mr-2.5 h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50"
                id="staff-sidebar-toggle"
                type="button"
                aria-controls="staff-sidebar"
                aria-expanded="false"
                onclick="toggleStaffSidebar()">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#475569" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div class="flex min-w-0 flex-1 items-center justify-between gap-4">
                <div>
                    <h1 class="page-title text-xl font-bold text-slate-800">@yield('page-title', 'Dashboard')</h1>
                    <p class="page-subtitle text-sm text-slate-500">@yield('page-subtitle', 'Welcome to Home Cleaning Service Staff')</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('staff.notifications') }}" class="relative flex h-9 w-9 items-center justify-center rounded-[10px] bg-slate-100 text-slate-500 transition hover:bg-primary-100 hover:text-primary-700">
                        <i class="fas fa-bell text-base"></i>
                        @if($unreadNotifCount > 0)
                        <span class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white">{{ $unreadNotifCount > 9 ? '9+' : $unreadNotifCount }}</span>
                        @endif
                    </a>
                    <div class="w-10 h-10 bg-accent-600 rounded-full flex items-center justify-center text-white font-bold">
                        {{ auth()->user()->initials }}
                    </div>
                    <span class="hidden text-sm font-semibold text-slate-800 lg:block">{{ auth()->user()->display_name }}</span>
                </div>
            </div>
        </div>
        <div class="admin-content flex-1 bg-[#f5f7fa] p-0">
            @yield('content')
        </div>
    </div>

<script>
function toggleStaffSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.getElementById('staff-sidebar-overlay');
    const toggle = document.getElementById('staff-sidebar-toggle');
    const willOpen = !sidebar.classList.contains('sidebar-open');

    sidebar.classList.toggle('sidebar-open', willOpen);
    overlay.classList.toggle('hidden', !willOpen);
    toggle?.setAttribute('aria-expanded', String(willOpen));
}
document.querySelectorAll('.admin-sidebar a').forEach(function(link) {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 767) {
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.getElementById('staff-sidebar-overlay');
            const toggle = document.getElementById('staff-sidebar-toggle');

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

