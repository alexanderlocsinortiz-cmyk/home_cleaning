<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - Home Cleaning Service Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    @stack('styles')
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: '#1D9E75',
                    'primary-dark': '#0F6E56',
                    secondary: '#E1F5EE',
                }
            }
        }
    }
    </script>
    <style>
    /* ===== ADMIN MOBILE RESPONSIVE ===== */
    @media (max-width: 900px) {
        .admin-sidebar {
            position: fixed !important;
            left: -260px !important;
            top: 0 !important;
            height: 100vh !important;
            z-index: 100 !important;
            transition: left 0.3s ease !important;
            box-shadow: none !important;
        }
        .admin-sidebar.sidebar-open {
            left: 0 !important;
            box-shadow: 4px 0 20px rgba(0,0,0,0.15) !important;
        }
        .admin-main {
            margin-left: 0 !important;
            width: 100% !important;
        }
        .mobile-hamburger {
            display: flex !important;
        }
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 99;
            backdrop-filter: blur(2px);
        }
        .sidebar-overlay.show {
            display: block !important;
        }
        .admin-topbar {
            padding: 0 1rem !important;
        }
        .admin-stats-row-1,
        .admin-stats-row-2 {
            grid-template-columns: repeat(2, 1fr) !important;
        }
        .admin-bottom-grid {
            grid-template-columns: 1fr !important;
        }
        .admin-welcome-inner {
            flex-direction: column !important;
            gap: 1rem !important;
            align-items: flex-start !important;
        }
        .admin-quick-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
        .admin-page-content {
            padding: 1rem !important;
        }
        .admin-table-wrap {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch !important;
        }
        .admin-customers-header {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 10px !important;
        }
        .admin-customers-header form {
            width: 100% !important;
        }
        .admin-customers-header input {
            width: 100% !important;
        }
    }
    .mobile-hamburger {
        display: none;
    }
    </style>
</head>
<body class="bg-slate-50 flex min-h-screen">
    <div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside class="admin-sidebar w-64 bg-slate-800 fixed top-0 left-0 z-100 flex flex-col h-screen">
        <div class="p-4 border-b border-slate-700 flex items-center gap-3">
            <img src="{{ asset('images/logo.png') }}" alt="Home Cleaning Service" style="height: 40px; width: auto; flex-shrink: 0;">
            <div>
                <div class="text-sm font-bold text-white leading-tight">Home Cleaning</div>
                <small class="block text-xs text-slate-400 font-normal">Admin Panel</small>
            </div>
        </div>
        <nav class="py-4 flex-1 overflow-y-auto">
            <div class="px-6 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mt-4">Main</div>
            <a href="{{ route('admin.dashboard') }}" class="mx-3 flex items-center gap-3 rounded-lg px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-600/20 text-emerald-400' : '' }}">
                <i class="fas fa-tachometer-alt w-5 text-center"></i> Dashboard
            </a>
            <div class="px-6 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mt-4">Management</div>
            <a href="{{ route('admin.customers') }}" class="mx-3 flex items-center gap-3 rounded-lg px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white {{ request()->routeIs('admin.customers*') ? 'bg-emerald-600/20 text-emerald-400' : '' }}">
                <i class="fas fa-users w-5 text-center"></i> Customers
            </a>
            @php($pendingBookings = \App\Models\Booking::where('status','pending')->count())
            <a href="{{ route('admin.bookings') }}" class="mx-3 flex items-center gap-3 rounded-lg px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white {{ request()->routeIs('admin.bookings') ? 'bg-emerald-600/20 text-emerald-400' : '' }}">
                <i class="fas fa-calendar-check w-5 text-center"></i>
                <span class="flex-1">Bookings</span>
                @if($pendingBookings)
                    <span class="ml-auto inline-flex h-5 w-5 items-center justify-center rounded-full bg-amber-400 text-xs font-bold text-slate-900">{{ $pendingBookings }}</span>
                @endif
            </a>
            <a href="{{ route('admin.services.index') }}" class="mx-3 flex items-center gap-3 rounded-lg px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white {{ request()->routeIs('admin.services*') ? 'bg-emerald-600/20 text-emerald-400' : '' }}">
                <i class="fas fa-concierge-bell w-5 text-center"></i> Services
            </a>
            <a href="{{ route('admin.staff.index') }}" class="mx-3 flex items-center gap-3 rounded-lg px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white {{ request()->routeIs('admin.staff.*') ? 'bg-emerald-600/20 text-emerald-400' : '' }}">
                <i class="fas fa-user-tie w-5 text-center"></i> Staff
            </a>
            <a href="{{ route('admin.attendance') }}" class="mx-3 flex items-center gap-3 rounded-lg px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white {{ request()->routeIs('admin.attendance') ? 'bg-emerald-600/20 text-emerald-400' : '' }}">
                <i class="fas fa-fingerprint w-5 text-center"></i> Attendance
            </a>
            <a href="{{ route('admin.attendance.history') }}" class="mx-3 flex items-center gap-3 rounded-lg px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white {{ request()->routeIs('admin.attendance.history') ? 'bg-emerald-600/20 text-emerald-400' : '' }}">
                <i class="fas fa-history w-5 text-center"></i> Attendance Logs
            </a>
            <a href="{{ route('admin.reports') }}" class="mx-3 flex items-center gap-3 rounded-lg px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white {{ request()->routeIs('admin.reports') ? 'bg-emerald-600/20 text-emerald-400' : '' }}">
                <i class="fas fa-chart-bar w-5 text-center"></i> Reports
            </a>
            <a href="{{ route('admin.service-areas') }}" class="mx-3 flex items-center gap-3 rounded-lg px-3 py-3 text-slate-300 transition-all hover:bg-slate-700/50 hover:text-white {{ request()->routeIs('admin.service-areas') ? 'bg-emerald-600/20 text-emerald-400' : '' }}">
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
    <div class="admin-main ml-64 flex-1 flex flex-col min-h-screen">
        <div class="admin-topbar bg-white px-8 py-4 border-b border-slate-200 flex items-center sticky top-0 z-50">
            <button class="mobile-hamburger" onclick="toggleSidebar()" 
                style="align-items: center; justify-content: center; width: 38px; height: 38px; border: 1px solid #e2e8f0; border-radius: 8px; background: white; cursor: pointer; margin-right: 12px; flex-shrink: 0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#475569" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <div class="flex min-w-0 flex-1 items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">@yield('page-title', 'Dashboard')</h1>
                    <p class="text-sm text-slate-500">@yield('page-subtitle', 'Welcome to Home Cleaning Service Admin')</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-emerald-600 rounded-full flex items-center justify-center text-white font-bold">
                        {{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}
                    </div>
                    <span class="text-sm font-semibold text-slate-800">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span>
                </div>
            </div>
        </div>
        <div class="flex-1 bg-slate-50 p-8">
            @yield('content')
        </div>
    </div>

@stack('scripts')
<script>
function toggleSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    sidebar.classList.toggle('sidebar-open');
    overlay.classList.toggle('show');
}

document.querySelectorAll('.admin-sidebar a').forEach(function(link) {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 900) {
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.remove('sidebar-open');
            overlay.classList.remove('show');
        }
    });
});
</script>
</body>
</html>

