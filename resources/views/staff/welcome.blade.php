@extends('layouts.staff')
@section('title', 'Staff Dashboard')
@section('page-title', 'Staff Dashboard')
@section('page-subtitle', 'Your assignments and tasks')

@section('content')
@php
    $greeting = now()->hour < 12 ? 'Good Morning' : (now()->hour < 17 ? 'Good Afternoon' : 'Good Evening');
    $initials = $user->initials;
    $completionRate = $totalBookings > 0 ? round(($completedBookings / $totalBookings) * 100, 1) : 0;

    $stats = [
        [
            'label' => 'Total Assigned',
            'value' => $totalBookings,
            'icon' => 'fa-clipboard-list',
            'cardClasses' => 'border-l-4 border-primary-300 bg-primary-50/80',
            'iconClasses' => 'bg-primary-100 text-primary-700',
        ],
        [
            'label' => 'Completed',
            'value' => $completedBookings,
            'icon' => 'fa-circle-check',
            'cardClasses' => 'border-l-4 border-accent-300 bg-accent-50/80',
            'iconClasses' => 'bg-accent-100 text-accent-700',
        ],
        [
        'label' => 'In Progress',
            'value' => $inProgress,
            'icon' => 'fa-spinner',
            'cardClasses' => 'border-l-4 border-primary-300 bg-primary-50/80',
            'iconClasses' => 'bg-primary-100 text-primary-700',
        ],
        [
            'label' => 'Earnings',
            'value' => 'P' . number_format($totalEarnings, 0),
            'icon' => 'fa-wallet',
            'cardClasses' => 'border-l-4 border-amber-300 bg-primary-50/80',
            'iconClasses' => 'bg-primary-100 text-primary-700',
        ],
    ];

    $quickActions = [
        [
            'label' => 'My Bookings',
            'description' => 'Manage proof uploads and job progress.',
            'route' => route('staff.bookings'),
            'icon' => 'fa-broom',
            'classes' => 'bg-primary-50 text-primary-700',
        ],
        [
            'label' => 'My Performance',
            'description' => 'Review ratings and customer feedback.',
            'route' => route('staff.performance'),
            'icon' => 'fa-chart-line',
            'classes' => 'bg-primary-50 text-primary-700',
        ],
        [
            'label' => 'My Schedule',
            'description' => 'See your upcoming work calendar.',
            'route' => route('staff.schedule'),
            'icon' => 'fa-calendar-days',
            'classes' => 'bg-primary-50 text-primary-700',
        ],
    ];

    $statusClasses = [
        'confirmed' => 'border border-accent-200 bg-accent-50 text-accent-700',
        'in_progress' => 'border border-primary-200 bg-primary-50 text-primary-700',
    ];
@endphp

<div class="cleanflow-page-shell min-h-[calc(100vh-81px)] px-4 py-6 sm:px-6 sm:py-8">
    <div class="mx-auto max-w-7xl space-y-6">
        @if (session('success'))
            <div class="cleanflow-alert cleanflow-alert--success flex items-start gap-3">
                <i class="fas fa-circle-check mt-0.5 text-base"></i>
                <div>
                    <p class="text-sm font-semibold">Staff update saved.</p>
                    <p class="mt-1 text-sm text-primary-800/80">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="cleanflow-alert cleanflow-alert--error">
                <div class="flex items-start gap-3">
                    <i class="fas fa-circle-exclamation mt-0.5 text-base"></i>
                    <div>
                        <p class="text-sm font-semibold">Please fix the following before continuing.</p>
                        <ul class="mt-2 space-y-1 text-sm text-red-700/90">
                            @foreach ($errors->all() as $error)
                                <li class="flex items-start gap-2">
                                    <span class="mt-1 h-1.5 w-1.5 rounded-full bg-primary-400"></span>
                                    <span>{{ $error }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8 lg:px-10">
            <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="flex max-w-3xl flex-col gap-5">
                    <span class="cleanflow-kicker">
                        <i class="fas fa-user-check text-[0.75rem]"></i>
                        Staff operations
                    </span>

                    <div class="flex items-start gap-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full border-2 border-white/25 bg-white/15 text-xl font-black text-white shadow-lg">
                            {{ $initials }}
                        </div>
                        <div class="space-y-2">
                            <p class="text-sm text-white/70">{{ now()->format('l, F d Y') }}</p>
                            <h1 class="text-3xl font-black tracking-tight sm:text-4xl">
                                {{ $greeting }}, {{ $user->display_name }}!
                            </h1>
                            <p class="text-sm leading-7 text-white/80 sm:text-base">
                                Stay on top of your assignments, monitor live jobs, and keep your staff profile ready
                                for today’s workload in {{ ucfirst($user->barangay ?? 'your area') }}.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 text-sm text-white/85">
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-clipboard-check text-xs"></i>
                            {{ $assignedBookings->count() }} active job{{ $assignedBookings->count() === 1 ? '' : 's' }}
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-star text-xs"></i>
                            {{ $avgRating ?? 'No' }} average rating
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-chart-simple text-xs"></i>
                            {{ $completionRate }}% completion rate
                        </span>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 xl:max-w-sm xl:justify-end">
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-5 py-4 text-center backdrop-blur-sm">
                        <div class="text-2xl font-black">{{ $assignedBookings->count() }}</div>
                        <div class="mt-1 text-xs uppercase tracking-[0.18em] text-white/70">Active jobs</div>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-5 py-4 text-center backdrop-blur-sm">
                        <div class="text-2xl font-black">P{{ number_format($totalEarnings, 0) }}</div>
                        <div class="mt-1 text-xs uppercase tracking-[0.18em] text-white/70">Earnings</div>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($stats as $stat)
                <section class="cleanflow-panel px-5 py-5 {{ $stat['cardClasses'] }}">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $stat['label'] }}</p>
                            <strong class="mt-3 block text-3xl font-black tracking-tight text-slate-900">{{ $stat['value'] }}</strong>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl {{ $stat['iconClasses'] }}">
                            <i class="fas {{ $stat['icon'] }}"></i>
                        </span>
                    </div>
                </section>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-[320px_minmax(0,1fr)]">
            <aside class="space-y-6">
                <section class="cleanflow-panel p-6">
                    <div class="mb-5 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">My information</h2>
                            <p class="text-sm text-slate-500">Your current staff profile and readiness snapshot.</p>
                        </div>
                        <a href="{{ route('staff.profile') }}" class="inline-flex items-center gap-2 rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700 transition hover:bg-primary-100">
                            <i class="fas fa-pen text-[10px]"></i>
                            Edit
                        </a>
                    </div>

                    <div class="rounded-3xl border border-slate-100 bg-slate-50/85 p-5 text-center">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-primary-600 text-2xl font-black text-white shadow-lg">
                            {{ $initials }}
                        </div>
                        <div class="mt-4 text-lg font-bold text-slate-900">{{ $user->display_name }}</div>
                        <div class="mt-1 text-sm text-slate-500">{{ $user->email }}</div>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                        <div class="client-profile-summary-row">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-white text-slate-500 shadow-sm">
                                    <i class="fas fa-phone text-sm"></i>
                                </span>
                                <span class="text-sm font-medium text-slate-500">Phone</span>
                            </div>
                            <span class="client-profile-summary-value text-sm">{{ $user->phone ?? 'Not set' }}</span>
                        </div>

                        <div class="client-profile-summary-row">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-white text-slate-500 shadow-sm">
                                    <i class="fas fa-location-dot text-sm"></i>
                                </span>
                                <span class="text-sm font-medium text-slate-500">Barangay</span>
                            </div>
                            <span class="client-profile-summary-value text-sm">{{ ucfirst($user->barangay ?? 'N/A') }}</span>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                        <div class="rounded-[1.2rem] border {{ $completionRate >= 70 ? 'border-primary-200 bg-primary-50 text-primary-700' : ($completionRate >= 40 ? 'border-amber-200 bg-primary-50 text-primary-700' : 'border-danger-200 bg-danger-50 text-danger-700') }} p-4 text-center">
                            <div class="text-2xl font-black">{{ $completionRate }}%</div>
                            <div class="mt-1 text-xs font-semibold uppercase tracking-[0.18em]">Completion rate</div>
                        </div>
                        <div class="rounded-[1.2rem] border border-amber-200 bg-primary-50 p-4 text-center text-primary-700">
                            <div class="text-2xl font-black">{{ $avgRating ?? '-' }}</div>
                            <div class="mt-1 text-xs font-semibold uppercase tracking-[0.18em]">Average rating</div>
                        </div>
                    </div>
                </section>

                <section class="cleanflow-panel p-6">
                    <div class="mb-4">
                        <h2 class="text-base font-bold text-slate-900">Quick actions</h2>
                        <p class="mt-1 text-sm text-slate-500">Jump into the parts of the staff portal you use most.</p>
                    </div>

                    <div class="space-y-3">
                        @foreach ($quickActions as $action)
                            <a href="{{ $action['route'] }}" class="flex items-start gap-3 rounded-[1.2rem] border border-slate-100 bg-slate-50/85 px-4 py-4 transition hover:border-slate-200 hover:bg-white hover:shadow-sm">
                                <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl {{ $action['classes'] }}">
                                    <i class="fas {{ $action['icon'] }}"></i>
                                </span>
                                <span class="block">
                                    <span class="block text-sm font-semibold text-slate-900">{{ $action['label'] }}</span>
                                    <span class="mt-1 block text-sm leading-6 text-slate-500">{{ $action['description'] }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </section>
            </aside>

            <section class="cleanflow-panel overflow-hidden">
                <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">Active assignments</h2>
                        <p class="mt-1 text-sm text-slate-500">Confirmed and in-progress jobs assigned to you right now.</p>
                    </div>
                    <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                        {{ $assignedBookings->count() }} booking{{ $assignedBookings->count() === 1 ? '' : 's' }}
                    </span>
                </div>

                @if ($assignedBookings->count())
                    <div class="grid gap-4 px-6 py-6 lg:grid-cols-2">
                        @foreach ($assignedBookings as $booking)
                            @php
                                $isToday = \Carbon\Carbon::parse($booking->scheduled_date)->isToday();
                            @endphp
                            <article class="rounded-3xl border border-slate-100 bg-slate-50/75 p-5 transition hover:border-slate-200 hover:bg-white hover:shadow-sm">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="{{ route('bookings.show', $booking->id) }}" class="font-mono text-sm font-bold text-primary-600 hover:underline">
                                                CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}
                                            </a>
                                            @if ($isToday)
                                                <span class="rounded-full border border-primary-200 bg-primary-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-primary-700">
                                                    Today
                                                </span>
                                            @endif
                                        </div>
                                        <h3 class="mt-3 text-base font-bold text-slate-900">{{ $booking->service_label }}</h3>
                                        <p class="mt-1 text-sm text-slate-500">
                                            {{ $booking->user->display_name }}
                                            @if ($booking->user->phone)
                                                &middot; {{ substr($booking->user->phone, 0, 4) }}****
                                            @endif
                                        </p>
                                    </div>
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$booking->status] ?? 'border border-slate-200 bg-slate-50 text-slate-600' }}">
                                        {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                                    </span>
                                </div>

                                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-2xl bg-white px-4 py-3 shadow-sm">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Schedule</p>
                                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ \Carbon\Carbon::parse($booking->scheduled_date)->format('M d, Y') }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ \Carbon\Carbon::parse($booking->scheduled_time)->format('h:i A') }}</p>
                                    </div>
                                    <div class="rounded-2xl bg-white px-4 py-3 shadow-sm">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Address</p>
                                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $booking->street_address }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ ucfirst($booking->barangay) }}</p>
                                    </div>
                                </div>

                                <div class="mt-5 flex flex-wrap items-center gap-3">
                                    <a href="{{ route('bookings.show', $booking->id) }}" class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                                        <i class="fas fa-arrow-up-right-from-square text-xs"></i>
                                        Open booking
                                    </a>

                                    @if ($booking->status === 'in_progress')
                                        <button
                                            type="button"
                                            onclick="startTracking({{ $booking->id }})"
                                            id="track-btn-{{ $booking->id }}"
                                            data-location-update-url="{{ route('booking.location.update', $booking->id) }}"
                                            class="inline-flex items-center gap-2 rounded-2xl border border-primary-200 bg-primary-50 px-4 py-2.5 text-sm font-semibold text-primary-700 transition hover:bg-primary-100"
                                        >
                                            <i class="fas fa-location-arrow text-xs"></i>
                                            Share live location
                                        </button>
                                    @else
                                        <span class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-500">
                                            <i class="fas fa-circle-info text-xs"></i>
                                            Start this job from My Bookings
                                        </span>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="px-6 py-14 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-50 text-primary-600">
                            <i class="fas fa-clipboard-list text-xl"></i>
                        </div>
                        <h3 class="mt-4 text-lg font-bold text-slate-900">No active assignments right now</h3>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                            Confirmed and in-progress bookings will appear here as soon as work is assigned to you.
                        </p>
                    </div>
                @endif
            </section>
        </div>
    </div>
</div>

<script>
const watchIds = {};

function setTrackingButtonState(button, isLive) {
    if (!button) {
        return;
    }

    const icon = button.querySelector('i');
    if (icon) {
        icon.className = isLive ? 'fas fa-satellite-dish text-xs' : 'fas fa-location-arrow text-xs';
    }

    button.classList.remove(
        'border-primary-200',
        'bg-primary-50',
        'text-primary-700',
        'hover:bg-primary-100',
        'border-primary-600',
        'bg-primary-600',
        'text-white'
    );

    if (isLive) {
        button.classList.add('border-primary-600', 'bg-primary-600', 'text-white');
        button.innerHTML = '<i class="fas fa-satellite-dish text-xs"></i> Location live';
        button.disabled = true;
    } else {
        button.classList.add('border-primary-200', 'bg-primary-50', 'text-primary-700', 'hover:bg-primary-100');
        button.innerHTML = '<i class="fas fa-location-arrow text-xs"></i> Share live location';
        button.disabled = false;
    }
}

function startTracking(bookingId) {
    if (!navigator.geolocation) {
        alert('Geolocation is not supported on this device.');
        return;
    }

    const button = document.getElementById('track-btn-' + bookingId);
    const locationUpdateUrl = button?.dataset.locationUpdateUrl;

    if (!locationUpdateUrl) {
        alert('Location sharing is not configured for this booking yet.');
        return;
    }

    setTrackingButtonState(button, true);

    watchIds[bookingId] = navigator.geolocation.watchPosition(
        async (position) => {
            try {
                await fetch(locationUpdateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        speed: position.coords.speed,
                        heading: position.coords.heading
                    })
                });
            } catch (error) {
                console.error('Location update failed:', error);
            }
        },
        (error) => {
            console.error('GPS error:', error);
            alert('Could not get location. Please allow location access.');
            setTrackingButtonState(button, false);
        },
        {
            enableHighAccuracy: true,
            maximumAge: 5000,
            timeout: 10000
        }
    );
}

function stopTracking(bookingId) {
    if (watchIds[bookingId] !== undefined) {
        navigator.geolocation.clearWatch(watchIds[bookingId]);
        delete watchIds[bookingId];
    }
}

window.addEventListener('beforeunload', () => {
    Object.keys(watchIds).forEach((bookingId) => stopTracking(bookingId));
});
</script>
@endsection
