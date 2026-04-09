<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Staff') - Home Cleaning Service Staff</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
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
@php
  $unreadNotifCount = \App\Models\Notification::where('user_id', auth()->id())->whereNull('read_at')->count();
@endphp
<style>
@media (max-width: 767px) {

    /* Fix topbar subtitle wrapping */
    .admin-topbar {
        padding: 0.6rem 1rem !important;
        min-height: unset !important;
    }
    .admin-topbar .page-subtitle,
    .admin-topbar p {
        display: none !important;
    }
    .admin-topbar h1,
    .admin-topbar .page-title {
        font-size: 16px !important;
        line-height: 1.2 !important;
    }

    /* Fix sidebar */
    .admin-sidebar {
        position: fixed !important;
        left: -260px !important;
        top: 0 !important;
        height: 100vh !important;
        z-index: 100 !important;
        transition: left 0.3s ease !important;
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
    }
    .sidebar-overlay.show {
        display: block !important;
    }
}
.mobile-hamburger { display: none; }
</style>
@stack('styles')
</head>
<body class="bg-slate-100 flex min-h-screen">
    <div class="sidebar-overlay" id="staff-sidebar-overlay" onclick="toggleStaffSidebar()"></div>

    <aside class="admin-sidebar w-64 bg-slate-800 min-h-screen fixed top-0 left-0 z-100 flex flex-col">
        <div class="p-4 border-b border-slate-700 flex items-center gap-3">
            <img src="{{ asset('images/logo.png') }}" alt="Home Cleaning Service" style="height: 40px; width: auto; flex-shrink: 0;">
            <div>
                <div class="text-sm font-bold text-white leading-tight">Home Cleaning</div>
                <small class="block text-xs text-slate-400 font-normal">Staff Portal</small>
            </div>
        </div>
        <nav class="py-4 flex-1">
            <div class="px-6 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mt-4">Main</div>
            <a href="{{ route('staff.dashboard') }}" class="flex items-center gap-3 px-6 py-3 text-slate-300 hover:bg-emerald-600/10 hover:text-white hover:border-r-3 hover:border-emerald-400 transition-all {{ request()->routeIs('staff.dashboard') ? 'bg-emerald-600/10 text-white border-r-3 border-emerald-400' : '' }}">
                <i class="fas fa-tachometer-alt w-5 text-center"></i> Dashboard
            </a>
            <div class="px-6 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider mt-4">Operations</div>
            <a href="{{ route('staff.performance') }}" class="flex items-center gap-3 px-6 py-3 text-slate-300 hover:bg-emerald-600/10 hover:text-white hover:border-r-3 hover:border-emerald-400 transition-all {{ request()->routeIs('staff.performance') ? 'bg-emerald-600/10 text-white border-r-3 border-emerald-400' : '' }}">
                <i class="fas fa-chart-line w-5 text-center"></i> My Performance
            </a>
            <a href="{{ route('staff.schedule') }}" class="flex items-center gap-3 px-6 py-3 text-slate-300 hover:bg-emerald-600/10 hover:text-white hover:border-r-3 hover:border-emerald-400 transition-all {{ request()->routeIs('staff.schedule') ? 'bg-emerald-600/10 text-white border-r-3 border-emerald-400' : '' }}">
                <i class="fas fa-calendar-alt w-5 text-center"></i> Schedule
            </a>
            <a href="{{ route('staff.bookings') }}" class="flex items-center gap-3 px-6 py-3 text-slate-300 hover:bg-emerald-600/10 hover:text-white hover:border-r-3 hover:border-emerald-400 transition-all {{ request()->routeIs('staff.bookings') ? 'bg-emerald-600/10 text-white border-r-3 border-emerald-400' : '' }}">
                <i class="fas fa-calendar-check w-5 text-center"></i> My Bookings
            </a>
            <a href="{{ route('staff.service-areas') }}" class="flex items-center gap-3 px-6 py-3 text-slate-300 hover:bg-emerald-600/10 hover:text-white hover:border-r-3 hover:border-emerald-400 transition-all {{ request()->routeIs('staff.service-areas') ? 'bg-emerald-600/10 text-white border-r-3 border-emerald-400' : '' }}">
                <i class="fas fa-map-marked-alt w-5 text-center"></i> Service Areas
            </a>
            <a href="{{ route('staff.profile') }}" class="flex items-center gap-3 px-6 py-3 text-slate-300 hover:bg-emerald-600/10 hover:text-white hover:border-r-3 hover:border-emerald-400 transition-all {{ request()->routeIs('staff.profile') ? 'bg-emerald-600/10 text-white border-r-3 border-emerald-400' : '' }}">
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
        <div class="admin-topbar bg-white px-8 py-4 border-b border-slate-200 flex items-center sticky top-0 z-50">
            <button class="mobile-hamburger" onclick="toggleStaffSidebar()"
                style="align-items: center; justify-content: center; width: 36px; height: 36px; border: 1px solid #e2e8f0; border-radius: 8px; background: white; cursor: pointer; margin-right: 10px; flex-shrink: 0;">
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
                    <a href="{{ route('staff.notifications') }}" style="position:relative;display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;background:#f1f5f9;color:#6b7280;text-decoration:none;transition:all 0.15s;" onmouseover="this.style.background='#E6F1FB';this.style.color='#185FA5'" onmouseout="this.style.background='#f1f5f9';this.style.color='#6b7280'">
                        <i class="fas fa-bell" style="font-size:16px;"></i>
                        @if($unreadNotifCount > 0)
                        <span style="position:absolute;top:-4px;right:-4px;background:#ef4444;color:white;border-radius:50%;width:16px;height:16px;font-size:9px;font-weight:700;display:flex;align-items:center;justify-content:center;">{{ $unreadNotifCount > 9 ? '9+' : $unreadNotifCount }}</span>
                        @endif
                    </a>
                    <div class="w-10 h-10 bg-emerald-600 rounded-full flex items-center justify-center text-white font-bold">
                        {{ strtoupper(substr(auth()->user()->first_name, 0, 1).substr(auth()->user()->last_name, 0, 1)) }}
                    </div>
                </div>
            </div>
        </div>
        <div class="admin-content flex-1" style="padding:0; background:#f5f7fa;">
            @yield('content')
        </div>
    </div>

<script>
function toggleStaffSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.getElementById('staff-sidebar-overlay');
    sidebar.classList.toggle('sidebar-open');
    overlay.classList.toggle('show');
}
document.querySelectorAll('.admin-sidebar a').forEach(function(link) {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 767) {
            document.querySelector('.admin-sidebar').classList.remove('sidebar-open');
            document.getElementById('staff-sidebar-overlay').classList.remove('show');
        }
    });
});
</script>
@stack('scripts')
</body>
</html>

