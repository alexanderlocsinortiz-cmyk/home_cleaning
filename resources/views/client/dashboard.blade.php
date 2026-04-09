@extends('layouts.client')
@section('title', 'Dashboard - Home Cleaning Service')

@section('content')
@php
    $user = auth()->user();
    $initials = strtoupper(substr($user->first_name ?? 'C', 0, 1) . substr($user->last_name ?? 'U', 0, 1));
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
    $totalBookings = $bookings->count();
    $completedBookings = $bookings->where('status', 'completed')->count();
    $upcomingBookings = $bookings->whereIn('status', ['pending', 'confirmed', 'in_progress'])->count();
    $recentBookings = $bookings->take(4);
    $completionRate = $totalBookings > 0 ? round(($completedBookings / $totalBookings) * 100) : 0;
    $activeBooking = $bookings->whereIn('status', ['confirmed', 'in_progress'])->first();
    $userBarangayLabel = $user->barangay ? ucfirst(str_replace('_', ' ', $user->barangay)) : 'Not set';
    $statusClasses = [
        'pending' => 'bg-yellow-100 text-yellow-700',
        'confirmed' => 'bg-blue-100 text-blue-700',
        'in_progress' => 'bg-orange-100 text-orange-700',
        'completed' => 'bg-green-100 text-green-700',
        'cancelled' => 'bg-red-100 text-red-700',
    ];
    $stats = [
        [
            'label' => 'Total',
            'value' => $totalBookings,
            'description' => 'All bookings',
            'border' => '#60a5fa',
            'background' => 'rgba(239, 246, 255, 0.7)',
            'iconBackground' => '#dbeafe',
            'iconColor' => '#2563eb',
            'icon' => 'fa-calendar-days',
        ],
        [
            'label' => 'Completed',
            'value' => $completedBookings,
            'description' => 'Finished services',
            'border' => '#4ade80',
            'background' => 'rgba(240, 253, 244, 0.7)',
            'iconBackground' => '#dcfce7',
            'iconColor' => '#16a34a',
            'icon' => 'fa-circle-check',
        ],
        [
            'label' => 'Upcoming',
            'value' => $upcomingBookings,
            'description' => 'Active bookings',
            'border' => '#facc15',
            'background' => 'rgba(254, 252, 232, 0.7)',
            'iconBackground' => '#fef3c7',
            'iconColor' => '#ca8a04',
            'icon' => 'fa-clock',
        ],
        [
            'label' => 'Completion',
            'value' => $completionRate . '%',
            'description' => 'Completion rate',
            'border' => '#c084fc',
            'background' => 'rgba(250, 245, 255, 0.7)',
            'iconBackground' => '#f3e8ff',
            'iconColor' => '#9333ea',
            'icon' => 'fa-chart-line',
        ],
    ];
    $quickActions = [
        [
            'label' => 'Book a Service',
            'description' => 'Schedule your next cleaning visit.',
            'href' => route('bookings.create'),
            'icon' => 'fa-broom',
            'iconBackground' => '#dcfce7',
            'iconColor' => '#16a34a',
            'textColor' => '#15803d',
        ],
        [
            'label' => 'My Bookings',
            'description' => 'Review all submitted service requests.',
            'href' => route('bookings.index'),
            'icon' => 'fa-clipboard-list',
            'iconBackground' => '#dbeafe',
            'iconColor' => '#2563eb',
            'textColor' => '#1d4ed8',
        ],
        [
            'label' => 'Service Areas',
            'description' => 'Explore covered barangays and locations.',
            'href' => route('client.service-areas'),
            'icon' => 'fa-location-dot',
            'iconBackground' => '#fed7aa',
            'iconColor' => '#ea580c',
            'textColor' => '#c2410c',
        ],
        [
            'label' => 'Edit Profile',
            'description' => 'Update your contact and address details.',
            'href' => route('client.profile.edit'),
            'icon' => 'fa-user-pen',
            'iconBackground' => '#e9d5ff',
            'iconColor' => '#9333ea',
            'textColor' => '#7e22ce',
        ],
    ];
@endphp

<div class="min-h-[calc(100vh-81px)] bg-gray-50 px-6 py-8" style="font-family: 'DM Sans', sans-serif;">
    <div class="mx-auto max-w-6xl space-y-6">

        @if(session('success'))
        <div class="flex items-start gap-3 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-green-700 shadow-sm">
            <i class="fas fa-circle-check mt-0.5"></i>
            <div>
                <div class="text-sm font-semibold">Action completed</div>
                <div class="text-sm">{{ session('success') }}</div>
            </div>
        </div>
        @endif

        <section class="overflow-hidden rounded-2xl bg-gradient-to-r from-green-700 via-emerald-600 to-teal-500 px-8 py-7 text-white shadow-sm">
            <div class="flex flex-col justify-between gap-6 lg:flex-row lg:items-center">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-white/20 text-lg font-bold text-white ring-2 ring-white/30">
                        {{ $initials }}
                    </div>
                    <div>
                        <div class="text-sm font-medium text-white/75">{{ now()->format('l, F d, Y') }}</div>
                        <h1 class="mt-1 text-3xl font-bold">{{ $greeting }}, {{ $user->first_name }}.</h1>
                        <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-white/80">
                            <span>{{ $userBarangayLabel === 'Not set' ? 'Profile details not set yet' : $userBarangayLabel . ', Valencia City' }}</span>
                            @if($upcomingBookings > 0)
                            <span class="rounded-full border border-white/20 bg-white/15 px-3 py-1 text-xs font-semibold text-white">
                                {{ $upcomingBookings }} active booking{{ $upcomingBookings > 1 ? 's' : '' }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('bookings.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-medium text-emerald-700 shadow-sm transition hover:bg-emerald-50">
                        <i class="fas fa-broom"></i>
                        Book a Service
                    </a>
                    <a href="{{ route('client.service-areas') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/30 bg-white/10 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-white/20">
                        <i class="fas fa-location-dot"></i>
                        Service Areas
                    </a>
                </div>
            </div>
        </section>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach($stats as $stat)
            <div class="rounded-2xl border border-slate-200 px-5 py-5 shadow-sm transition hover:shadow-md" style="border-left: 4px solid {{ $stat['border'] }}; background: {{ $stat['background'] }};">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $stat['label'] }}</div>
                        <div class="mt-3 text-4xl font-bold text-slate-900">{{ $stat['value'] }}</div>
                    </div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl" style="background: {{ $stat['iconBackground'] }}; color: {{ $stat['iconColor'] }};">
                        <i class="fas {{ $stat['icon'] }}"></i>
                    </div>
                </div>
                <div class="mt-3 text-sm text-slate-500">{{ $stat['description'] }}</div>
            </div>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <section class="rounded-2xl bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-3 border-b border-gray-100 pb-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Recent Bookings</h2>
                        <p class="mt-1 text-sm text-slate-500">Your latest cleaning requests and booking updates.</p>
                    </div>
                    <a href="{{ route('bookings.index') }}" class="text-sm font-medium text-green-600 hover:underline">View all</a>
                </div>

                @if($recentBookings->count())
                <div class="divide-y divide-gray-100">
                    @foreach($recentBookings as $booking)
                    <div class="flex flex-col gap-4 py-4 transition hover:bg-gray-50 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-start gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-green-100 text-green-700">
                                <i class="fas fa-broom"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-sm font-bold text-green-600">CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</span>
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$booking->status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ ucwords(str_replace('_', ' ', $booking->status)) }}
                                    </span>
                                </div>
                                <div class="mt-2 text-base font-semibold text-slate-900">{{ $booking->service_label }}</div>
                                <div class="mt-1 text-sm text-slate-500">
                                    {{ \Carbon\Carbon::parse($booking->scheduled_date)->format('M d, Y') }} at {{ \Carbon\Carbon::parse($booking->scheduled_time)->format('h:i A') }}
                                </div>
                                <div class="text-sm text-slate-500">{{ ucfirst($booking->barangay) }}</div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-4 sm:justify-end">
                            <div class="text-right">
                                <div class="text-base font-bold text-slate-900">&#8369;{{ number_format($booking->price, 0) }}</div>
                                <div class="text-xs text-slate-500">Total price</div>
                            </div>
                            <a href="{{ route('bookings.show', $booking->id) }}" class="text-sm font-medium text-green-600 hover:underline">View &rarr;</a>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="py-12 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-green-50 text-green-600">
                        <i class="fas fa-broom text-xl"></i>
                    </div>
                    <h3 class="mt-4 text-lg font-bold text-slate-900">No recent bookings yet</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">Your booking history will appear here after your first service request.</p>
                    <a href="{{ route('bookings.create') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-green-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-green-700">
                        <i class="fas fa-plus"></i>
                        Book a Service
                    </a>
                </div>
                @endif
            </section>

            <aside class="space-y-6">
                <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
                    <div class="bg-gradient-to-r from-green-500 to-teal-400 p-4 text-center text-white">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/15 text-lg font-bold ring-2 ring-white/30">
                            {{ $initials }}
                        </div>
                        <div class="mt-3 text-lg font-bold">{{ $user->first_name }} {{ $user->last_name }}</div>
                        <div class="text-sm text-white/80">{{ $user->email }}</div>
                    </div>
                    <div class="space-y-4 p-5">
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-sm text-slate-500">Phone</span>
                            <span class="text-sm font-semibold text-slate-900">{{ $user->phone ?: 'Not set' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-sm text-slate-500">Barangay</span>
                            <span class="text-sm font-semibold text-slate-900">{{ $userBarangayLabel }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-sm text-slate-500">Member Since</span>
                            <span class="text-sm font-semibold text-slate-900">{{ $user->created_at->format('M Y') }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-sm text-slate-500">Completion</span>
                            <span class="text-sm font-semibold {{ $completionRate >= 70 ? 'text-green-600' : ($completionRate >= 40 ? 'text-amber-600' : 'text-slate-500') }}">{{ $completionRate }}%</span>
                        </div>
                        <a href="{{ route('client.profile.edit') }}" class="inline-flex items-center gap-2 text-sm font-medium text-green-600 hover:underline">
                            <i class="fas fa-user-pen"></i>
                            Edit Profile
                        </a>
                    </div>
                </section>

                <section class="rounded-2xl bg-white p-5 shadow-sm">
                    <div class="text-lg font-bold text-slate-900">Quick Actions</div>
                    <div class="mt-1 text-sm text-slate-500">Common shortcuts for managing your cleaning requests.</div>
                    <div class="mt-4 space-y-3">
                        @foreach($quickActions as $action)
                        <a href="{{ $action['href'] }}" class="flex items-center gap-3 rounded-2xl border border-slate-100 px-4 py-3 transition hover:bg-slate-50">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl" style="background: {{ $action['iconBackground'] }}; color: {{ $action['iconColor'] }};">
                                <i class="fas {{ $action['icon'] }}"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold" style="color: {{ $action['textColor'] }};">{{ $action['label'] }}</div>
                                <div class="text-xs text-slate-500">{{ $action['description'] }}</div>
                            </div>
                            <i class="fas fa-chevron-right text-xs text-slate-300"></i>
                        </a>
                        @endforeach
                    </div>
                </section>
            </aside>
        </div>

        @if($activeBooking)
        <section class="flex flex-col gap-4 rounded-2xl border border-green-200 bg-green-50 px-6 py-5 shadow-sm md:flex-row md:items-center md:justify-between">
            <div>
                <div class="text-sm font-semibold uppercase tracking-[0.16em] text-green-700">Active Booking</div>
                <div class="mt-2 text-xl font-bold text-slate-900">{{ $activeBooking->service_label }}</div>
                <div class="mt-1 text-sm text-slate-600">
                    CF-{{ str_pad($activeBooking->id, 5, '0', STR_PAD_LEFT) }} on {{ \Carbon\Carbon::parse($activeBooking->scheduled_date)->format('M d, Y') }}
                </div>
            </div>
            <a href="{{ route('bookings.show', $activeBooking->id) }}" class="inline-flex items-center gap-2 rounded-xl bg-green-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-green-700">
                <i class="fas fa-location-arrow"></i>
                Track Booking
            </a>
        </section>
        @elseif($upcomingBookings === 0)
        <section class="flex flex-col gap-4 rounded-2xl border border-emerald-100 bg-gradient-to-r from-emerald-50 to-teal-50 px-6 py-5 shadow-sm md:flex-row md:items-center md:justify-between">
            <div>
                <div class="text-sm font-semibold uppercase tracking-[0.16em] text-emerald-700">Ready to Schedule</div>
                <div class="mt-2 text-xl font-bold text-slate-900">Plan your next cleaning service.</div>
                <div class="mt-1 text-sm text-slate-600">You do not have any upcoming bookings right now.</div>
            </div>
            <a href="{{ route('bookings.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-green-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-green-700">
                <i class="fas fa-plus"></i>
                Book a Service
            </a>
        </section>
        @endif

    </div>
</div>
@endsection
