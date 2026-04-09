<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Client') - Home Cleaning Service Client</title>
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
</head>
@php
    $clientUser = auth()->user();
    $clientInitials = strtoupper(substr($clientUser->first_name ?? 'C', 0, 1) . substr($clientUser->last_name ?? 'U', 0, 1));
    $dashboardActive = request()->routeIs('client.dashboard');
    $bookingsActive = request()->routeIs('bookings.index') || request()->routeIs('bookings.show');
    $bookServiceActive = request()->routeIs('bookings.create');
    $serviceAreasActive = request()->routeIs('client.service-areas');
@endphp
<body class="min-h-screen bg-gray-50 text-slate-900">
    <header class="sticky top-0 z-30 border-b border-gray-100 bg-white/95 shadow-sm backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-4">
            <a href="{{ route('client.dashboard') }}" class="flex items-center gap-3 text-decoration-none">
                <img src="{{ asset('images/logo.png') }}" alt="Home Cleaning Service" class="h-11 w-auto">
                <div class="leading-tight">
                    <div class="text-[15px] font-extrabold text-slate-800">Home Cleaning</div>
                    <div class="text-xs font-semibold text-green-600">Client Portal</div>
                </div>
            </a>

            <div class="hidden items-center gap-8 md:flex">
                <nav class="flex items-center gap-6">
                    <a href="{{ route('client.dashboard') }}" class="border-b-2 pb-1 text-sm {{ $dashboardActive ? 'border-green-600 font-semibold text-green-600' : 'border-transparent font-medium text-gray-600 hover:text-gray-900' }}">Dashboard</a>
                    <a href="{{ route('bookings.index') }}" class="border-b-2 pb-1 text-sm {{ $bookingsActive ? 'border-green-600 font-semibold text-green-600' : 'border-transparent font-medium text-gray-600 hover:text-gray-900' }}">My Bookings</a>
                    <a href="{{ route('bookings.create') }}" class="border-b-2 pb-1 text-sm {{ $bookServiceActive ? 'border-green-600 font-semibold text-green-600' : 'border-transparent font-medium text-gray-600 hover:text-gray-900' }}">Book Service</a>
                    <a href="{{ route('client.service-areas') }}" class="border-b-2 pb-1 text-sm {{ $serviceAreasActive ? 'border-green-600 font-semibold text-green-600' : 'border-transparent font-medium text-gray-600 hover:text-gray-900' }}">Service Areas</a>
                </nav>

                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-3 rounded-full border border-slate-200 bg-slate-50 px-3 py-2">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-600 text-sm font-bold text-white shadow-sm">
                            {{ $clientInitials }}
                        </div>
                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium text-gray-800">{{ $clientUser->first_name }} {{ $clientUser->last_name }}</div>
                            <div class="truncate text-xs text-gray-500">{{ $clientUser->email }}</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-gray-600 transition hover:text-gray-900">Logout</button>
                    </form>
                </div>
            </div>

            <button id="mobile-menu-btn" class="inline-flex items-center justify-center rounded-xl border border-slate-200 p-2 text-slate-700 hover:bg-slate-100 md:hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>

        <div id="mobile-menu" class="hidden border-t border-slate-100 bg-white px-6 py-4 md:hidden">
            <div class="mb-4 flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-green-600 text-sm font-bold text-white shadow-sm">
                    {{ $clientInitials }}
                </div>
                <div class="min-w-0">
                    <div class="truncate text-sm font-medium text-gray-800">{{ $clientUser->first_name }} {{ $clientUser->last_name }}</div>
                    <div class="truncate text-xs text-gray-500">{{ $clientUser->email }}</div>
                </div>
            </div>

            <nav class="flex flex-col gap-1">
                <a href="{{ route('client.dashboard') }}" class="block rounded-xl px-3 py-2.5 text-sm {{ $dashboardActive ? 'bg-green-50 font-semibold text-green-600' : 'font-medium text-gray-600 hover:bg-green-50 hover:text-gray-900' }}">Dashboard</a>
                <a href="{{ route('bookings.index') }}" class="block rounded-xl px-3 py-2.5 text-sm {{ $bookingsActive ? 'bg-green-50 font-semibold text-green-600' : 'font-medium text-gray-600 hover:bg-green-50 hover:text-gray-900' }}">My Bookings</a>
                <a href="{{ route('bookings.create') }}" class="block rounded-xl px-3 py-2.5 text-sm {{ $bookServiceActive ? 'bg-green-50 font-semibold text-green-600' : 'font-medium text-gray-600 hover:bg-green-50 hover:text-gray-900' }}">Book Service</a>
                <a href="{{ route('client.service-areas') }}" class="block rounded-xl px-3 py-2.5 text-sm {{ $serviceAreasActive ? 'bg-green-50 font-semibold text-green-600' : 'font-medium text-gray-600 hover:bg-green-50 hover:text-gray-900' }}">Service Areas</a>
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
    document.getElementById('mobile-menu-btn').addEventListener('click', function() {
        document.getElementById('mobile-menu').classList.toggle('hidden');
    });
    </script>

    <main class="min-h-[calc(100vh-81px)] bg-gray-50">
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
