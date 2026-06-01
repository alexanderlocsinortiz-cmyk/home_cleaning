<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.pwa-head')
    <title>@yield('title', 'Admin') - {{ $siteSettings->website_name }} Admin</title>
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    @include('partials.ui-theme')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="admin-ui bg-slate-50 flex min-h-screen overflow-x-hidden">
    @php
        $adminTimezone = config('cleanflow.attendance_timezone', 'Asia/Manila');
        $adminToday = now($adminTimezone);
        $adminDateRangeStart = $adminToday->copy()->startOfMonth();
        $adminDateRangeEnd = $adminToday->copy()->endOfMonth();
        $adminCalendarStart = $adminDateRangeStart->copy()->startOfWeek(\Carbon\CarbonInterface::SUNDAY);
        $adminCalendarEnd = $adminDateRangeEnd->copy()->endOfWeek(\Carbon\CarbonInterface::SATURDAY);
        $adminCalendarDays = [];

        for ($day = $adminCalendarStart->copy(); $day->lte($adminCalendarEnd); $day->addDay()) {
            $adminCalendarDays[] = $day->copy();
        }
    @endphp
    <div class="fixed inset-0 z-90 hidden bg-black/50 backdrop-blur-[2px]" id="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside class="admin-sidebar fixed left-0 top-0 z-100 flex h-screen w-64 flex-col bg-blue-800" id="admin-sidebar">
        <div class="p-4 border-b border-slate-700 flex items-center gap-3">
            <img src="{{ $siteSettings->logo_url }}" alt="{{ $siteSettings->website_name }}" class="h-12 w-auto shrink-0">
            <span class="text-lg font-bold text-white">{{ $siteSettings->website_name }}</span>
        </div>
        <nav class="py-4 flex-1 overflow-y-auto">
            <div class="px-6 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mt-4">Main</div>
            <a href="{{ route('admin.dashboard') }}" class="mx-3 flex items-center gap-3 rounded-lg border-l-4 border-transparent px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white hover:border-blue-500 {{ request()->routeIs('admin.dashboard') ? 'border-blue-500 bg-blue-500/15 text-blue-200' : '' }}">
                <i class="fas fa-tachometer-alt w-5 text-center"></i> Dashboard
            </a>
            <div class="px-6 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mt-4">Management</div>
            <a href="{{ route('admin.customers') }}" class="mx-3 flex items-center gap-3 rounded-lg border-l-4 border-transparent px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white hover:border-blue-500 {{ request()->routeIs('admin.customers*') ? 'border-blue-500 bg-blue-500/15 text-blue-200' : '' }}">
                <i class="fas fa-users w-5 text-center"></i> Customers
            </a>
            <a href="{{ route('admin.bookings') }}" class="mx-3 flex items-center gap-3 rounded-lg border-l-4 border-transparent px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white hover:border-blue-500 {{ request()->routeIs('admin.bookings') ? 'border-blue-500 bg-blue-500/15 text-blue-200' : '' }}">
                <i class="fas fa-calendar-check w-5 text-center"></i>
                <span class="flex-1">Bookings</span>
                @if($pendingBookingsCount)
                    <span class="ml-auto inline-flex h-5 w-5 items-center justify-center rounded-full bg-amber-400 text-xs font-bold text-slate-900">{{ $pendingBookingsCount }}</span>
                @endif
            </a>
            <a href="{{ route('admin.services.index') }}" class="mx-3 flex items-center gap-3 rounded-lg border-l-4 border-transparent px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white hover:border-blue-500 {{ request()->routeIs('admin.services*') ? 'border-blue-500 bg-blue-500/15 text-blue-200' : '' }}">
                <i class="fas fa-concierge-bell w-5 text-center"></i> Services
            </a>
            <a href="{{ route('admin.staff.index') }}" class="mx-3 flex items-center gap-3 rounded-lg border-l-4 border-transparent px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white hover:border-blue-500 {{ request()->routeIs('admin.staff.*') ? 'border-blue-500 bg-blue-500/15 text-blue-200' : '' }}">
                <i class="fas fa-user-tie w-5 text-center"></i> Staff
            </a>
            <a href="{{ route('admin.attendance') }}" class="mx-3 flex items-center gap-3 rounded-lg border-l-4 border-transparent px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white hover:border-blue-500 {{ request()->routeIs('admin.attendance*') ? 'border-blue-500 bg-blue-500/15 text-blue-200' : '' }}">
                <i class="fas fa-fingerprint w-5 text-center"></i> Attendance
            </a>
            <a href="{{ route('admin.reports') }}" class="mx-3 flex items-center gap-3 rounded-lg border-l-4 border-transparent px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white hover:border-blue-500 {{ request()->routeIs('admin.reports') ? 'border-blue-500 bg-blue-500/15 text-blue-200' : '' }}">
                <i class="fas fa-chart-bar w-5 text-center"></i> Reports
            </a>
            <a href="{{ route('admin.logs') }}" class="mx-3 flex items-center gap-3 rounded-lg border-l-4 border-transparent px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white hover:border-blue-500 {{ request()->routeIs('admin.logs') ? 'border-blue-500 bg-blue-500/15 text-blue-200' : '' }}">
                <i class="fas fa-clipboard-list w-5 text-center"></i> Logs
            </a>
            <a href="{{ route('admin.service-areas') }}" class="mx-3 flex items-center gap-3 rounded-lg border-l-4 border-transparent px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white hover:border-blue-500 {{ request()->routeIs('admin.service-areas') ? 'border-blue-500 bg-blue-500/15 text-blue-200' : '' }}">
                <i class="fas fa-map-marked-alt w-5 text-center"></i> Service Areas
            </a>
            <a href="{{ route('admin.settings') }}" class="mx-3 flex items-center gap-3 rounded-lg border-l-4 border-transparent px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white hover:border-blue-500 {{ request()->routeIs('admin.settings*') ? 'border-blue-500 bg-blue-500/15 text-blue-200' : '' }}">
                <i class="fas fa-gear w-5 text-center"></i> Settings
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
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#1E40AF" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div class="flex min-w-0 flex-1 items-center justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="text-xl font-bold text-slate-800">@yield('page-title', 'Dashboard')</h1>
                    <p class="text-sm text-slate-500">@yield('page-subtitle', 'Welcome to Home Cleaning Service Admin')</p>
                </div>
                <div class="flex shrink-0 items-center gap-4">
                    <div class="relative" data-topbar-menu>
                        <button type="button" class="flex h-11 min-w-[250px] items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-900 shadow-sm transition hover:border-blue-200 hover:bg-blue-50" aria-label="Dashboard date range" aria-expanded="false" data-topbar-toggle="date-range-menu">
                            <span class="flex items-center gap-3">
                                <i class="fas fa-calendar-days text-blue-800"></i>
                                <span>{{ $adminDateRangeStart->format('M d, Y') }} - {{ $adminDateRangeEnd->format('M d, Y') }}</span>
                            </span>
                            <i class="fas fa-chevron-down text-xs text-blue-900"></i>
                        </button>
                        <div id="date-range-menu" class="absolute right-0 top-full z-[60] mt-3 hidden w-96 rounded-xl border border-slate-200 bg-white p-4 shadow-xl">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-bold text-slate-900">Dashboard date range</div>
                                    <div class="mt-1 text-xs text-slate-500">Current view covers {{ $adminToday->format('F Y') }}.</div>
                                </div>
                                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">Today {{ $adminToday->format('M d') }}</span>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
                                <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                                    <div class="font-bold uppercase tracking-wide text-slate-400">From</div>
                                    <div class="mt-1 font-semibold text-slate-900">{{ $adminDateRangeStart->format('M d, Y') }}</div>
                                </div>
                                <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                                    <div class="font-bold uppercase tracking-wide text-slate-400">To</div>
                                    <div class="mt-1 font-semibold text-slate-900">{{ $adminDateRangeEnd->format('M d, Y') }}</div>
                                </div>
                            </div>
                            <div class="mt-4 rounded-xl border border-slate-100 p-3">
                                <div class="mb-3 flex items-center justify-between">
                                    <div class="text-sm font-black text-slate-900">{{ $adminToday->format('F Y') }}</div>
                                    <div class="text-xs font-semibold text-slate-500">{{ $adminTimezone }}</div>
                                </div>
                                <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-bold uppercase text-slate-400">
                                    @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday)
                                        <div class="py-1">{{ $weekday }}</div>
                                    @endforeach
                                </div>
                                <div class="mt-1 grid grid-cols-7 gap-1 text-center text-xs">
                                    @foreach($adminCalendarDays as $calendarDay)
                                        @php
                                            $isCurrentMonth = $calendarDay->isSameMonth($adminToday);
                                            $isToday = $calendarDay->isSameDay($adminToday);
                                        @endphp
                                        <div class="flex h-8 items-center justify-center rounded-lg font-bold {{ $isToday ? 'bg-blue-700 text-white' : ($isCurrentMonth ? 'text-slate-800 hover:bg-blue-50' : 'text-slate-300') }}">
                                            {{ $calendarDay->day }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="mt-4 flex items-center justify-between gap-3">
                                <a href="{{ route('admin.reports') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                                    <i class="fas fa-chart-bar"></i>
                                    Reports
                                </a>
                                <a href="{{ route('admin.analytics.export', ['date_range' => 30]) }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-800 px-3 py-2 text-xs font-bold text-white transition hover:bg-blue-700">
                                    <i class="fas fa-download"></i>
                                    Export 30 days
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="relative" data-topbar-menu>
                        <button type="button" class="relative flex h-10 w-10 items-center justify-center rounded-full text-blue-900 transition hover:bg-blue-50 hover:text-blue-700" aria-label="Booking notifications" aria-expanded="false" data-topbar-toggle="booking-notifications-menu">
                            <i class="fas fa-bell text-lg"></i>
                            @if($pendingBookingsCount)
                                <span class="absolute right-2 top-1.5 h-2.5 w-2.5 rounded-full bg-blue-500 ring-2 ring-white">
                                    <span class="sr-only">{{ $pendingBookingsCount > 9 ? '9+' : $pendingBookingsCount }} pending bookings</span>
                                </span>
                            @endif
                        </button>
                        <div id="booking-notifications-menu" class="absolute right-0 top-full z-[60] mt-3 hidden w-96 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
                            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                                <div>
                                    <div class="text-sm font-bold text-slate-900">Booking notifications</div>
                                    <div class="text-xs text-slate-500">{{ number_format($pendingBookingsCount) }} pending booking{{ $pendingBookingsCount === 1 ? '' : 's' }}</div>
                                </div>
                                @if($pendingBookingsCount)
                                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">{{ $pendingBookingsCount > 9 ? '9+' : $pendingBookingsCount }}</span>
                                @endif
                            </div>
                            <div class="max-h-80 overflow-y-auto p-2">
                                @forelse($pendingBookingsPreview as $booking)
                                    <a href="{{ route('admin.bookings', ['tab' => 'active']) }}" class="block rounded-lg px-3 py-3 transition hover:bg-blue-50">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="truncate text-sm font-bold text-slate-900">CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</div>
                                                <div class="mt-1 truncate text-xs text-slate-500">{{ $booking->user?->display_name ?? $booking->user?->email ?? 'Unknown customer' }}</div>
                                                <div class="mt-1 text-xs font-semibold text-slate-600">
                                                    {{ \Carbon\Carbon::parse($booking->scheduled_date)->format('M d, Y') }} at {{ \Carbon\Carbon::parse($booking->scheduled_time)->format('h:i A') }}
                                                </div>
                                            </div>
                                            <span class="shrink-0 rounded-full bg-amber-100 px-2 py-1 text-[11px] font-bold text-amber-700">Pending</span>
                                        </div>
                                    </a>
                                @empty
                                    <div class="px-3 py-8 text-center text-sm text-slate-500">
                                        No pending bookings right now.
                                    </div>
                                @endforelse
                            </div>
                            <div class="border-t border-slate-100 p-3">
                                <a href="{{ route('admin.bookings', ['tab' => 'active']) }}" class="flex items-center justify-center gap-2 rounded-lg bg-blue-800 px-3 py-2 text-sm font-bold text-white transition hover:bg-blue-700">
                                    <i class="fas fa-calendar-check"></i>
                                    Open bookings
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-600 text-base font-bold text-white">
                        {{ auth()->user()->initials }}
                    </div>
                    <span class="text-sm font-semibold text-slate-900">{{ auth()->user()->display_name }}</span>
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

const topbarMenus = document.querySelectorAll('[data-topbar-menu]');

function closeTopbarMenus(exceptMenu = null) {
    topbarMenus.forEach(function(menu) {
        if (menu === exceptMenu) {
            return;
        }

        const panel = menu.querySelector('[id]');
        const toggle = menu.querySelector('[data-topbar-toggle]');
        panel?.classList.add('hidden');
        toggle?.setAttribute('aria-expanded', 'false');
    });
}

document.querySelectorAll('[data-topbar-toggle]').forEach(function(toggle) {
    toggle.addEventListener('click', function(event) {
        event.stopPropagation();

        const menu = toggle.closest('[data-topbar-menu]');
        const panel = document.getElementById(toggle.dataset.topbarToggle);
        const willOpen = panel?.classList.contains('hidden');

        closeTopbarMenus(menu);
        panel?.classList.toggle('hidden', !willOpen);
        toggle.setAttribute('aria-expanded', String(willOpen));
    });
});

document.addEventListener('click', function(event) {
    if (!event.target.closest('[data-topbar-menu]')) {
        closeTopbarMenus();
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeTopbarMenus();
    }
});
</script>
</body>
</html>
