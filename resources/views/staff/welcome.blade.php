@extends('layouts.staff')
@section('title', 'Staff Dashboard')
@section('page-title', 'Staff Dashboard')
@section('page-subtitle', 'Your assignments and tasks')

@push('styles')
<style>
    .staff-dashboard-page {
        --staff-blue-900: #1e3a8a;
        --staff-blue-800: #1e40af;
        --staff-blue-700: #1d4ed8;
        --staff-blue-100: #dbeafe;
        --staff-blue-50: #eff6ff;
        --staff-green: #059669;
        --staff-amber: #d97706;
        --staff-slate: #334155;
        background:
            linear-gradient(90deg, rgba(219, 234, 254, 0.7), rgba(248, 250, 252, 0.95) 22%, rgba(239, 246, 255, 0.92));
    }

    .staff-dashboard-page [class*="tracking-"] {
        letter-spacing: 0;
    }

    .staff-dashboard-page .cleanflow-panel,
    .staff-dashboard-card {
        border: 1px solid #bfdbfe;
        border-radius: 1.2rem;
        background: rgba(255, 255, 255, 0.94);
        box-shadow: 0 16px 34px rgba(30, 64, 175, 0.07);
    }

    .staff-hero {
        border: 1px solid #1e3a8a;
        border-radius: 1.35rem;
        background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 62%, #1d4ed8 100%);
        color: #fff;
        box-shadow: 0 18px 36px rgba(30, 58, 138, 0.18);
    }

    .staff-hero-metric {
        min-width: 9.5rem;
        border: 1px solid rgba(191, 219, 254, 0.72);
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.12);
        padding: 1rem;
        text-align: center;
    }

    .staff-stat-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
    }

    .staff-stat-card {
        min-height: 6.75rem;
        border: 1px solid #e2e8f0;
        border-left: 4px solid var(--stat-accent, var(--staff-blue-700));
        background: #fff;
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.06);
    }

    .staff-stat-card--assigned {
        --stat-accent: #1d4ed8;
        --stat-soft: rgba(219, 234, 254, 0.8);
    }

    .staff-stat-card--completed {
        --stat-accent: #059669;
        --stat-soft: rgba(209, 250, 229, 0.82);
    }

    .staff-stat-card--progress {
        --stat-accent: #d97706;
        --stat-soft: rgba(254, 243, 199, 0.85);
    }

    .staff-stat-card--earnings {
        --stat-accent: #0f766e;
        --stat-soft: rgba(204, 251, 241, 0.78);
    }

    .staff-stat-icon {
        background: var(--stat-accent, var(--staff-blue-700));
        color: #fff;
    }

    .staff-dashboard-grid {
        display: grid;
        grid-template-columns: minmax(280px, 360px) minmax(0, 1fr);
        gap: 1rem;
        align-items: start;
    }

    .staff-side-stack {
        display: grid;
        gap: 1rem;
    }

    .staff-profile-card {
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    }

    .staff-summary-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        border: 1px solid #dbeafe;
        border-radius: 1rem;
        background: #eff6ff;
        padding: 0.85rem 1rem;
    }

    .staff-mini-metric {
        border: 1px solid #bfdbfe;
        border-radius: 1rem;
        background: #eff6ff;
        padding: 1rem;
        text-align: center;
        color: #1d4ed8;
    }

    .staff-action-link {
        display: flex;
        align-items: center;
        gap: 0.9rem;
        border: 1px solid #dbeafe;
        border-radius: 1rem;
        background: #f8fbff;
        padding: 0.9rem 1rem;
        transition: border-color 160ms ease, background 160ms ease, transform 160ms ease;
    }

    .staff-action-link:hover {
        border-color: #93c5fd;
        background: #ffffff;
        transform: translateY(-1px);
    }

    .active-assignment-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
        gap: 1rem;
        align-items: start;
    }

    .active-assignment-card {
        border: 1px solid #bfdbfe;
        border-radius: 1.15rem;
        background: #ffffff;
        padding: 1.15rem;
        box-shadow: 0 10px 24px rgba(30, 64, 175, 0.06);
    }

    .assignment-meta {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .assignment-meta-item {
        min-height: 5.6rem;
        border: 1px solid #e5edf8;
        border-radius: 0.95rem;
        background: #f8fbff;
        padding: 0.85rem;
    }

    @media (max-width: 1180px) {
        .staff-stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .staff-dashboard-grid {
            grid-template-columns: 1fr;
        }

        .staff-side-stack {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 760px) {
        .staff-dashboard-page {
            padding: 1rem;
        }

        .staff-stat-grid,
        .staff-side-stack,
        .assignment-meta {
            grid-template-columns: 1fr;
        }

        .staff-hero-metric {
            flex: 1 1 100%;
        }
    }
</style>
@endpush

@section('content')
@php
    $dashboardTimezone = config('cleanflow.attendance_timezone', 'Asia/Manila');
    $dashboardNow = \Carbon\Carbon::now($dashboardTimezone);
    $greeting = $dashboardNow->hour < 12 ? 'Good Morning' : ($dashboardNow->hour < 17 ? 'Good Afternoon' : 'Good Evening');
    $initials = $user->initials;
    $completionRate = $totalBookings > 0 ? round(($completedBookings / $totalBookings) * 100, 1) : 0;

    $stats = [
        [
            'label' => 'Total Assigned',
            'value' => $totalBookings,
            'icon' => 'fa-clipboard-list',
            'variant' => 'assigned',
        ],
        [
            'label' => 'Completed',
            'value' => $completedBookings,
            'icon' => 'fa-circle-check',
            'variant' => 'completed',
        ],
        [
            'label' => 'In Progress',
            'value' => $inProgress,
            'icon' => 'fa-spinner',
            'variant' => 'progress',
        ],
        [
            'label' => 'Earnings',
            'value' => 'P' . number_format($totalEarnings, 2),
            'icon' => 'fa-wallet',
            'variant' => 'earnings',
        ],
    ];

    $quickActions = [
        [
            'label' => 'My Bookings',
            'description' => 'Manage proof uploads and job progress.',
            'route' => route('staff.bookings'),
            'icon' => 'fa-broom',
        ],
        [
            'label' => 'My Performance',
            'description' => 'Review ratings and customer feedback.',
            'route' => route('staff.performance'),
            'icon' => 'fa-chart-line',
        ],
        [
            'label' => 'My Schedule',
            'description' => 'See your upcoming work calendar.',
            'route' => route('staff.schedule'),
            'icon' => 'fa-calendar-days',
        ],
    ];

    $statusClasses = [
        'confirmed' => 'border border-blue-200 bg-blue-50 text-blue-700',
        'in_progress' => 'border border-teal-200 bg-teal-50 text-teal-700',
    ];
@endphp

<div class="staff-dashboard-page cleanflow-page-shell min-h-[calc(100vh-81px)] px-4 py-5 sm:px-6 sm:py-7">
    <div class="mx-auto max-w-[92rem] space-y-5">
        @if (session('success'))
            <div class="cleanflow-alert cleanflow-alert--success flex items-start gap-3">
                <i class="fas fa-circle-check mt-0.5 text-base"></i>
                <div>
                    <p class="text-sm font-semibold">Staff update saved.</p>
                    <p class="mt-1 text-sm text-emerald-800/80">{{ session('success') }}</p>
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
                                    <span class="mt-1 h-1.5 w-1.5 rounded-full bg-red-400"></span>
                                    <span>{{ $error }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <section class="staff-hero overflow-hidden px-5 py-5 sm:px-7">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex max-w-3xl flex-col gap-4">
                    <span class="cleanflow-kicker">
                        <i class="fas fa-user-check text-[0.75rem]"></i>
                        Staff operations
                    </span>

                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-blue-300 bg-blue-800 text-lg font-black text-white shadow-sm">
                            {{ $initials }}
                        </div>
                        <div class="space-y-2">
                            <p class="text-sm text-blue-200">{{ $dashboardNow->format('l, F d Y') }}</p>
                            <h1 class="text-2xl font-black tracking-tight sm:text-3xl">
                                {{ $greeting }}, {{ $user->display_name }}!
                            </h1>
                            <p class="max-w-2xl text-sm leading-6 text-blue-100">
                                Stay on top of your assignments, monitor live jobs, and keep your staff profile ready
                                for today's workload in {{ ucfirst($user->barangay ?? 'your area') }}.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 text-sm text-blue-100">
                        <span class="inline-flex items-center gap-2 rounded-full border border-blue-300 bg-blue-800 px-3 py-2">
                            <i class="fas fa-clipboard-check text-xs"></i>
                            {{ $assignedBookings->count() }} active job{{ $assignedBookings->count() === 1 ? '' : 's' }}
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-blue-300 bg-blue-800 px-3 py-2">
                            <i class="fas fa-star text-xs"></i>
                            {{ $avgRating ?? 'No' }} average rating
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-blue-300 bg-blue-800 px-3 py-2">
                            <i class="fas fa-chart-simple text-xs"></i>
                            {{ $completionRate }}% completion rate
                        </span>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 xl:max-w-sm xl:justify-end">
                    <div class="staff-hero-metric">
                        <div class="text-2xl font-black">{{ $assignedBookings->count() }}</div>
                        <div class="mt-1 text-xs uppercase tracking-[0.18em] text-blue-200">Active jobs</div>
                    </div>
                    <div class="staff-hero-metric">
                        <div class="text-2xl font-black">P{{ number_format($totalEarnings, 2) }}</div>
                        <div class="mt-1 text-xs uppercase tracking-[0.18em] text-blue-200">Earnings</div>
                    </div>
                </div>
            </div>
        </section>

        <div class="staff-stat-grid">
            @foreach ($stats as $stat)
                <section class="staff-dashboard-card staff-stat-card staff-stat-card--{{ $stat['variant'] }} px-5 py-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-400">{{ $stat['label'] }}</p>
                            <strong class="mt-2 block text-4xl font-black leading-none text-slate-900">{{ $stat['value'] }}</strong>
                        </div>
                        <span class="staff-stat-icon inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl">
                            <i class="fas {{ $stat['icon'] }}"></i>
                        </span>
                    </div>
                </section>
            @endforeach
        </div>

        <div class="staff-dashboard-grid">
            <aside class="staff-side-stack">
                <section class="cleanflow-panel staff-profile-card p-5">
                    <div class="mb-5 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">My information</h2>
                            <p class="text-sm text-slate-500">Your current staff profile and readiness snapshot.</p>
                        </div>
                        <a href="{{ route('staff.profile') }}" class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:bg-slate-200">
                            <i class="fas fa-pen text-[10px]"></i>
                            Edit
                        </a>
                    </div>

                    <div class="rounded-[1.1rem] border border-blue-100 bg-white p-5 text-center">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-blue-600 text-2xl font-black text-white shadow-lg shadow-blue-200/70">
                            {{ $initials }}
                        </div>
                        <div class="mt-4 text-lg font-bold text-slate-900">{{ $user->display_name }}</div>
                        <div class="mt-1 text-sm text-slate-500">{{ $user->email }}</div>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                        <div class="staff-summary-row">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-white text-slate-500 shadow-sm">
                                    <i class="fas fa-phone text-sm"></i>
                                </span>
                                <span class="text-sm font-medium text-slate-500">Phone</span>
                            </div>
                            <span class="client-profile-summary-value text-sm">{{ $user->phone ?? 'Not set' }}</span>
                        </div>

                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                        <div class="staff-mini-metric">
                            <div class="text-2xl font-black">{{ $completionRate }}%</div>
                            <div class="mt-1 text-xs font-semibold uppercase tracking-[0.18em]">Completion rate</div>
                        </div>
                        <div class="staff-mini-metric">
                            <div class="text-2xl font-black">{{ $avgRating ?? '-' }}</div>
                            <div class="mt-1 text-xs font-semibold uppercase tracking-[0.18em]">Average rating</div>
                        </div>
                    </div>
                </section>

                <section class="cleanflow-panel p-5">
                    <div class="mb-4">
                        <h2 class="text-base font-bold text-slate-900">Quick actions</h2>
                        <p class="mt-1 text-sm text-slate-500">Jump into the parts of the staff portal you use most.</p>
                    </div>

                    <div class="space-y-3">
                        @foreach ($quickActions as $action)
                            <a href="{{ $action['route'] }}" class="staff-action-link">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
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

            <section class="cleanflow-panel self-start overflow-hidden">
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
                    <div class="active-assignment-list px-5 py-5 sm:px-6 sm:py-6">
                        @foreach ($assignedBookings as $booking)
                            @php
                                $bookingDate = \Carbon\Carbon::parse($booking->scheduled_date->toDateString(), $dashboardTimezone);
                                $isToday = $bookingDate->isSameDay($dashboardNow);
                            @endphp
                            <article class="active-assignment-card">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <a href="{{ route('bookings.show', $booking->id) }}" class="font-mono text-sm font-bold text-slate-700 hover:underline">
                                                CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}
                                            </a>
                                            @if ($isToday)
                                                <span class="rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-blue-700">
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

                                <div class="assignment-meta mt-5">
                                    <div class="assignment-meta-item">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Schedule</p>
                                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $bookingDate->format('M d, Y') }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ \Carbon\Carbon::parse($booking->scheduled_time)->format('h:i A') }}</p>
                                    </div>
                                    <div class="assignment-meta-item">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Address</p>
                                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $booking->street_address ?: 'No street details' }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ $booking->barangay ? ucfirst($booking->barangay) : 'Barangay not set' }}</p>
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
                                            class="inline-flex items-center gap-2 rounded-2xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700 transition hover:bg-blue-100"
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
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-600">
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
        'border-blue-200',
        'bg-blue-50',
        'text-blue-700',
        'hover:bg-blue-100',
        'border-blue-600',
        'bg-blue-600',
        'text-white'
    );

    if (isLive) {
        button.classList.add('border-blue-600', 'bg-blue-600', 'text-white');
        button.innerHTML = '<i class="fas fa-satellite-dish text-xs"></i> Location live';
        button.disabled = true;
    } else {
        button.classList.add('border-blue-200', 'bg-blue-50', 'text-blue-700', 'hover:bg-blue-100');
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
                const response = await fetch(locationUpdateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        speed: position.coords.speed,
                        heading: position.coords.heading
                    })
                });

                if (!response.ok) {
                    const payload = await response.json().catch(() => null);
                    throw new Error(payload?.message || 'We could not save your location. Please try again.');
                }
            } catch (error) {
                console.error('Location update failed:', error);
                navigator.geolocation.clearWatch(watchIds[bookingId]);
                delete watchIds[bookingId];
                setTrackingButtonState(button, false);
                alert(error.message || 'We could not share your location. Please try again.');
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
