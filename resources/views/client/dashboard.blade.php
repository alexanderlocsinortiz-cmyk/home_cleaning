@extends('layouts.client')
@section('title', 'Dashboard - Home Cleaning Service')

@section('content')
@php
    $user = auth()->user();
    $initials = $user->initials;
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
    $totalBookings = $bookings->count();
    $completedBookings = $bookings->where('status', 'completed')->count();
    $upcomingBookings = $bookings->whereIn('status', ['pending', 'confirmed', 'in_progress'])->count();
    $recentBookings = $bookings->take(4);
    $recentNotifications = $notifications->take(5);
    $completionRate = $totalBookings > 0 ? round(($completedBookings / $totalBookings) * 100) : 0;
    $activeBooking = $bookings->whereIn('status', ['confirmed', 'in_progress'])->first();
    $userBarangayLabel = $user->barangay ? ucfirst(str_replace('_', ' ', $user->barangay)) : 'Not set';
    $statusClasses = [
        'pending'     => 'bg-amber-100 text-amber-700',
        'confirmed'   => 'bg-accent-50 text-accent-700',
        'in_progress' => 'bg-primary-100 text-primary-700',
        'completed'   => 'bg-accent-100 text-accent-800',
        'cancelled'   => 'bg-danger-100 text-danger-700',
    ];
    $paymentStatusClasses = [
        'pending' => 'bg-amber-100 text-amber-700',
        'paid'    => 'bg-accent-100 text-accent-800',
    ];
    $stats = [
        [
            'label' => 'Total',
            'value' => $totalBookings,
            'description' => 'All requests',
            'cardClasses' => 'border-primary-200 bg-primary-50/85',
            'iconClasses' => 'bg-primary-100 text-primary-600',
            'valueClasses' => 'text-primary-700',
            'icon' => 'fa-calendar-days',
        ],
        [
            'label' => 'Completed',
            'value' => $completedBookings,
            'description' => 'Finished visits',
            'cardClasses' => 'border-primary-200 bg-primary-50/85',
            'iconClasses' => 'bg-primary-100 text-primary-600',
            'valueClasses' => 'text-primary-700',
            'icon' => 'fa-circle-check',
        ],
        [
            'label' => 'Upcoming',
            'value' => $upcomingBookings,
            'description' => 'Scheduled now',
            'cardClasses' => 'border-amber-200 bg-primary-50/85',
            'iconClasses' => 'bg-primary-100 text-primary-600',
            'valueClasses' => 'text-primary-700',
            'icon' => 'fa-clock',
        ],
        [
            'label' => 'Completion',
            'value' => $completionRate . '%',
            'description' => 'Jobs finished',
            'cardClasses' => 'border-primary-200 bg-primary-50/85',
            'iconClasses' => 'bg-primary-100 text-primary-600',
            'valueClasses' => 'text-primary-700',
            'icon' => 'fa-chart-line',
        ],
    ];
    $quickActions = [
        [
            'label' => 'Book a Service',
            'description' => 'Schedule a new visit.',
            'href' => route('bookings.create'),
            'icon' => 'fa-broom',
            'iconClasses' => 'bg-primary-100 text-primary-600',
            'textClasses' => 'text-primary-700',
        ],
        [
            'label' => 'My Bookings',
            'description' => 'Review your requests.',
            'href' => route('bookings.index'),
            'icon' => 'fa-clipboard-list',
            'iconClasses' => 'bg-primary-100 text-primary-600',
            'textClasses' => 'text-primary-700',
        ],
        [
            'label' => 'Service Areas',
            'description' => 'Check covered barangays.',
            'href' => route('client.service-areas'),
            'icon' => 'fa-location-dot',
            'iconClasses' => 'bg-primary-100 text-primary-600',
            'textClasses' => 'text-primary-700',
        ],
        [
            'label' => 'Edit Profile',
            'description' => 'Update account details.',
            'href' => route('client.profile.edit'),
            'icon' => 'fa-user-pen',
            'iconClasses' => 'bg-primary-100 text-primary-600',
            'textClasses' => 'text-primary-700',
        ],
    ];
@endphp

<div class="cleanflow-page-shell min-h-[calc(100vh-81px)] px-4 py-6 sm:px-6 sm:py-8">
    <div class="mx-auto max-w-6xl space-y-6">

        @if(session('success'))
        <div class="cleanflow-alert cleanflow-alert--success flex items-start gap-3">
            <i class="fas fa-circle-check mt-0.5"></i>
            <div>
                <div class="text-sm font-semibold">Update saved</div>
                <div class="text-sm">{{ session('success') }}</div>
            </div>
        </div>
        @endif

        @if(session('warning'))
        <div class="cleanflow-alert cleanflow-alert--warning flex items-start gap-3">
            <i class="fas fa-triangle-exclamation mt-0.5"></i>
            <div>
                <div class="text-sm font-semibold">Preferred cleaner update</div>
                <div class="text-sm">{{ session('warning') }}</div>
            </div>
        </div>
        @endif

        @if(session('info'))
        <div class="cleanflow-alert cleanflow-alert--info flex items-start gap-3">
            <i class="fas fa-circle-info mt-0.5"></i>
            <div>
                <div class="text-sm font-semibold">Booking note</div>
                <div class="text-sm">{{ session('info') }}</div>
            </div>
        </div>
        @endif

        <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8">
            <div class="cleanflow-hero-content flex flex-col justify-between gap-6 xl:flex-row xl:items-end">
                <div class="flex items-start gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-[1.4rem] bg-white/15 text-lg font-bold text-white ring-1 ring-white/30 backdrop-blur-sm">
                        {{ $initials }}
                    </div>
                    <div>
                        <span class="cleanflow-kicker">
                            <i class="fas fa-house-user"></i>
                            Client Dashboard
                        </span>
                        <h1 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">{{ $greeting }}, {{ $user->display_name }}.</h1>
                        <div class="mt-2 text-sm font-medium text-white/75">{{ now()->format('l, F d, Y') }}</div>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-white/85">Track your latest bookings, cleaner updates, payment status, and next service steps from one calm workspace.</p>
                        <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-white/80">
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
                    <a href="{{ route('bookings.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-primary-500 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-primary-600">
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
            <div class="cleanflow-panel border-l-4 px-5 py-5 {{ $stat['cardClasses'] }}">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $stat['label'] }}</div>
                        <div class="mt-3 text-4xl font-bold {{ $stat['valueClasses'] }}">{{ $stat['value'] }}</div>
                    </div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $stat['iconClasses'] }}">
                        <i class="fas {{ $stat['icon'] }}"></i>
                    </div>
                </div>
                <div class="mt-3 text-sm text-slate-500">{{ $stat['description'] }}</div>
            </div>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <section class="cleanflow-panel p-6">
                <div class="flex flex-col gap-3 border-b border-gray-100 pb-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Recent Bookings</h2>
                        <p class="mt-1 text-sm text-slate-500">Your latest requests and booking updates.</p>
                    </div>
                    <a href="{{ route('bookings.index') }}" class="text-sm font-medium text-primary-600 hover:underline">View all</a>
                </div>

                @if($recentBookings->count())
                <div class="divide-y divide-gray-100">
                    @foreach($recentBookings as $booking)
                    <div class="flex flex-col gap-4 py-4 transition hover:bg-gray-50 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-start gap-3">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-100 text-primary-700">
                                <i class="fas fa-broom"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-sm font-bold text-primary-600">CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</span>
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$booking->status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ ucwords(str_replace('_', ' ', $booking->status)) }}
                                    </span>
                                </div>
                                <div class="mt-2 text-base font-semibold text-slate-900">{{ $booking->service_label }}</div>
                                <div class="mt-1 text-sm text-slate-500">
                                    {{ \Carbon\Carbon::parse($booking->scheduled_date)->format('M d, Y') }} at {{ \Carbon\Carbon::parse($booking->scheduled_time)->format('h:i A') }}
                                </div>
                                <div class="text-sm text-slate-500">{{ ucfirst($booking->barangay) }}</div>
                                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                    <span class="rounded-full px-2.5 py-1 font-semibold {{ $paymentStatusClasses[$booking->payment_status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ \App\Models\Booking::paymentStatusLabel($booking->payment_status) }}
                                    </span>
                                    <span class="text-slate-500">{{ \App\Models\Booking::paymentMethodLabel($booking->payment_method) }}</span>
                                    @if($booking->isSubscription())
                                    <span class="text-slate-500">{{ $booking->subscriptionSummary() }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-4 sm:justify-end">
                            <div class="text-right">
                                <div class="text-base font-bold text-slate-900">&#8369;{{ number_format($booking->price, 0) }}</div>
                                <div class="text-xs text-slate-500">Total price</div>
                            </div>
                            <a href="{{ route('bookings.show', $booking->id) }}" class="text-sm font-medium text-primary-600 hover:underline">View &rarr;</a>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="py-12 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-50 text-primary-600">
                        <i class="fas fa-broom text-xl"></i>
                    </div>
                    <h3 class="mt-4 text-lg font-bold text-slate-900">No bookings yet</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">Once you schedule a service, the latest status, payment details, and cleaner assignment will appear here.</p>
                    <a href="{{ route('bookings.create') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-primary-700">
                        <i class="fas fa-plus"></i>
                        Book a Service
                    </a>
                </div>
                @endif
            </section>

            <aside class="space-y-6">
                <section class="cleanflow-panel overflow-hidden">
                    <div class="bg-slate-900 p-4 text-center text-white">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/15 text-lg font-bold ring-2 ring-white/30">
                            {{ $initials }}
                        </div>
                        <div class="mt-3 text-lg font-bold">{{ $user->display_name }}</div>
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
                            <span class="text-sm font-semibold {{ $completionRate >= 70 ? 'text-primary-600' : ($completionRate >= 40 ? 'text-primary-600' : 'text-slate-500') }}">{{ $completionRate }}%</span>
                        </div>
                        <a href="{{ route('client.profile.edit') }}" class="inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:underline">
                            <i class="fas fa-user-pen"></i>
                            Edit Profile
                        </a>
                    </div>
                </section>

                <section class="cleanflow-panel p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-lg font-bold text-slate-900">Booking Updates</div>
                            <div class="mt-1 text-sm text-slate-500">Recent cleaner, payment, and service updates.</div>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-50 text-primary-600">
                            <i class="fas fa-bell"></i>
                        </div>
                    </div>

                    @if($recentNotifications->count())
                    <div class="mt-4 space-y-3">
                        @foreach($recentNotifications as $notification)
                        <div class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                            <div class="text-sm font-semibold text-slate-900">{{ $notification->title }}</div>
                            <div class="mt-1 text-xs leading-5 text-slate-500">{{ $notification->message }}</div>
                            <div class="mt-2 flex items-center justify-between gap-3 text-[11px] text-slate-400">
                                <span>{{ optional($notification->created_at)->diffForHumans() }}</span>
                                @if($notification->link)
                                <a href="{{ url($notification->link) }}" class="font-semibold text-primary-600 hover:underline">View</a>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                        Booking confirmations, cleaner assignment updates, and proof-of-service notices will appear here.
                    </div>
                    @endif
                </section>

                <section class="cleanflow-panel p-5">
                    <div class="text-lg font-bold text-slate-900">Quick Actions</div>
                    <div class="mt-1 text-sm text-slate-500">Shortcuts for common booking tasks.</div>
                    <div class="mt-4 space-y-3">
                        @foreach($quickActions as $action)
                        <a href="{{ $action['href'] }}" class="flex items-center gap-3 rounded-2xl border border-slate-100 px-4 py-3 transition hover:bg-slate-50">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $action['iconClasses'] }}">
                                <i class="fas {{ $action['icon'] }}"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold {{ $action['textClasses'] }}">{{ $action['label'] }}</div>
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
        <section class="cleanflow-panel flex flex-col gap-4 border-primary-200 bg-primary-50/90 px-6 py-5 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="text-sm font-semibold uppercase tracking-[0.16em] text-primary-700">Active Booking</div>
                <div class="mt-2 text-xl font-bold text-slate-900">{{ $activeBooking->service_label }}</div>
                <div class="mt-1 text-sm text-slate-600">
                    CF-{{ str_pad($activeBooking->id, 5, '0', STR_PAD_LEFT) }} on {{ \Carbon\Carbon::parse($activeBooking->scheduled_date)->format('M d, Y') }}
                </div>
            </div>
            <a href="{{ route('bookings.show', $activeBooking->id) }}" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-primary-700">
                <i class="fas fa-location-arrow"></i>
                Track Booking
            </a>
        </section>
        @elseif($upcomingBookings === 0)
        <section class="cleanflow-panel flex flex-col gap-4 border-slate-200 bg-white px-6 py-5 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="text-sm font-semibold uppercase tracking-[0.16em] text-primary-700">Ready to Schedule</div>
                <div class="mt-2 text-xl font-bold text-slate-900">Plan your next cleaning service.</div>
                <div class="mt-1 text-sm text-slate-600">You do not have any upcoming bookings right now.</div>
            </div>
            <a href="{{ route('bookings.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-primary-700">
                <i class="fas fa-plus"></i>
                Book a Service
            </a>
        </section>
        @endif

    </div>
</div>
@endsection
