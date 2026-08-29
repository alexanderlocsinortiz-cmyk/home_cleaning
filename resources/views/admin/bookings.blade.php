@extends('layouts.admin')
@section('title', 'Bookings')
@section('page-title', 'Booking Management')
@section('page-subtitle', 'Review service requests, assign available staff, and update booking progress')

@push('styles')
<style>
    .admin-bookings-page {
        background: linear-gradient(90deg, rgba(219, 234, 254, 0.72), rgba(248, 250, 252, 0.95) 24%, rgba(239, 246, 255, 0.9));
    }

    .admin-bookings-page [class*="tracking-"] {
        letter-spacing: 0;
    }

    .booking-dispatch-hero {
        border: 1px solid #1e3a8a;
        border-radius: 1.25rem;
        background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 64%, #2563eb 100%);
        box-shadow: 0 18px 36px rgba(30, 58, 138, 0.16);
    }

    .booking-hero-meter {
        border: 1px solid rgba(191, 219, 254, 0.7);
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.12);
        padding: 1rem;
    }

    .booking-stat-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
    }

    .booking-stat-card {
        min-height: 9rem;
        border: 1px solid #e2e8f0;
        border-left: 4px solid var(--stat-accent, #2563eb);
        border-radius: 1rem;
        background: #fff;
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.06);
    }

    .booking-stat-card--total {
        --stat-accent: #2563eb;
        --stat-soft: rgba(219, 234, 254, 0.82);
    }

    .booking-stat-card--pending {
        --stat-accent: #d97706;
        --stat-soft: rgba(254, 243, 199, 0.88);
    }

    .booking-stat-card--confirmed {
        --stat-accent: #1d4ed8;
        --stat-soft: rgba(219, 234, 254, 0.82);
    }

    .booking-stat-card--completed {
        --stat-accent: #059669;
        --stat-soft: rgba(209, 250, 229, 0.86);
    }

    .booking-stat-icon {
        background: var(--stat-accent, #2563eb);
        color: #fff;
    }

    .booking-stat-icon i {
        font-size: 1.05rem;
    }

    .booking-workflow-panel,
    .booking-queue-panel {
        border: 1px solid #bfdbfe;
        border-radius: 1.15rem;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 16px 34px rgba(30, 64, 175, 0.07);
    }

    .booking-tab-pill,
    .booking-filter-pill {
        border-radius: 999px;
        border: 1px solid #dbeafe;
        background: #f8fbff;
        color: #1e3a8a;
        transition: border-color 160ms ease, background 160ms ease, transform 160ms ease;
    }

    .booking-tab-pill:hover,
    .booking-filter-pill:hover {
        border-color: #93c5fd;
        background: #eff6ff;
        transform: translateY(-1px);
    }

    .booking-tab-pill--active,
    .booking-filter-pill--active {
        border-color: #2563eb;
        background: #2563eb;
        color: #fff;
    }

    @media (max-width: 1180px) {
        .booking-stat-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 760px) {
        .admin-bookings-page {
            padding: 1rem;
        }

        .booking-stat-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
@php
    $statusLabels = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];
    $statusClasses = [
        'pending' => 'bg-amber-100 text-amber-700',
        'confirmed' => 'bg-blue-100 text-blue-700',
        'in_progress' => 'bg-teal-100 text-teal-700',
        'completed' => 'bg-emerald-100 text-emerald-700',
        'cancelled' => 'bg-danger-100 text-danger-700',
    ];
    $reviewLabels = [
        'pending' => 'Manual Review',
        'approved' => 'Review Approved',
        'blocked' => 'Review Blocked',
    ];
    $reviewClasses = [
        'pending' => 'bg-amber-100 text-amber-700',
        'approved' => 'bg-emerald-100 text-emerald-700',
        'blocked' => 'bg-danger-100 text-danger-700',
    ];
    $preferredStatusLabels = [
        'requested' => 'Requested',
        'unavailable' => 'Unavailable',
        'assigned' => 'Preferred Cleaner Assigned',
        'alternate_assigned' => 'Alternate Cleaner Assigned',
    ];
    $preferredStatusClasses = [
        'requested' => 'bg-blue-50 text-blue-700',
        'unavailable' => 'bg-amber-100 text-amber-700',
        'assigned' => 'bg-emerald-100 text-emerald-700',
        'alternate_assigned' => 'bg-slate-100 text-slate-600',
    ];
    $paymentStatusClasses = [
        'pending' => 'bg-amber-100 text-amber-700',
        'paid' => 'bg-emerald-100 text-emerald-700',
    ];
    $presentStaffCount = $staffList->where('is_present', true)->count();
    $activeTab = $tab === 'completed' ? 'completed' : 'active';
    $activeTabUrl = route('admin.bookings', array_merge(request()->except(['tab', 'active_page', 'completed_page']), ['tab' => 'active']));
    $completedTabUrl = route('admin.bookings', array_merge(request()->except(['tab', 'active_page', 'completed_page']), ['tab' => 'completed']));
    $showMarketplaceProvider = true;
    $filterLabels = [
        '' => 'All Active',
        'today' => 'Today',
        'unassigned' => 'Unassigned',
        'overdue' => 'Overdue',
        'review' => 'Manual Review',
        'in_progress' => 'In Progress',
        'provider_declined' => 'Provider Declined',
    ];
@endphp

<div class="admin-bookings-page admin-page-content cleanflow-page-shell space-y-5 p-6">
    @if(session('success'))
        <div class="cleanflow-alert cleanflow-alert--success flex items-start gap-3">
            <i class="fas fa-check-circle mt-0.5"></i>
            <div>
                <div class="text-sm font-bold">Action completed</div>
                <div class="text-sm">{{ session('success') }}</div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="cleanflow-alert cleanflow-alert--error flex items-start gap-3">
            <i class="fas fa-exclamation-triangle mt-0.5"></i>
            <div>
                <div class="text-sm font-bold">Action blocked</div>
                <div class="text-sm">{{ session('error') }}</div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="cleanflow-alert cleanflow-alert--error">
            <div class="text-sm font-bold">Review the booking update details.</div>
            <div class="mt-1 text-sm">The booking could not be updated until the items below are resolved.</div>
            <div class="mt-3 space-y-1 text-sm">
                @foreach($errors->all() as $error)
                    <div class="flex items-start gap-2">
                        <i class="fas fa-circle mt-1 text-[7px]"></i>
                        <span>{{ $error }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <section class="booking-dispatch-hero overflow-hidden px-6 py-6 text-white sm:px-7">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
            <div class="max-w-3xl">
                <span class="cleanflow-kicker">
                    <i class="fas fa-calendar-days"></i>
                    Operations Queue
                </span>
                <h2 class="mt-4 text-2xl font-black tracking-tight sm:text-3xl">Booking dispatch queue</h2>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-blue-100">
                    Review active requests, assign available cleaners, clear manual review flags, and keep completed jobs out of the live queue.
                </p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 xl:min-w-[340px]">
                <div class="booking-hero-meter">
                    <div class="text-xs font-semibold uppercase text-blue-100">Active Queue</div>
                    <div class="mt-2 text-4xl font-black leading-none">{{ number_format($queueCounts['active']) }}</div>
                    <div class="mt-2 text-sm text-blue-100">{{ number_format($queueCounts['today']) }} scheduled today</div>
                </div>
                <div class="booking-hero-meter">
                    <div class="text-xs font-semibold uppercase text-blue-100">Cleaners Present</div>
                    <div class="mt-2 text-4xl font-black leading-none">{{ number_format($presentStaffCount) }}</div>
                    <div class="mt-2 text-sm text-blue-100">{{ number_format($queueCounts['review_pending']) }} awaiting review</div>
                </div>
            </div>
        </div>
    </section>

    <div class="booking-stat-grid">
        <div class="booking-stat-card booking-stat-card--total p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase text-slate-400">Total Bookings</div>
                    <div class="mt-2 text-4xl font-black leading-none text-slate-900">{{ number_format($stats['total']) }}</div>
                    <div class="mt-2 text-sm text-slate-500">All service requests currently stored in the system.</div>
                </div>
                <div class="booking-stat-icon flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl">
                    <i class="fas fa-calendar-days"></i>
                </div>
            </div>
        </div>

        <div class="booking-stat-card booking-stat-card--pending p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase text-slate-400">Pending</div>
                    <div class="mt-2 text-4xl font-black leading-none text-slate-900">{{ number_format($stats['pending']) }}</div>
                    <div class="mt-2 text-sm text-slate-500">
                        Requests waiting for confirmation or staffing.
                        @if(($pendingEscalationSummary['warning'] + $pendingEscalationSummary['critical']) > 0)
                            <span class="block pt-1 text-amber-600">
                                {{ $pendingEscalationSummary['critical'] }} critical &bull; {{ $pendingEscalationSummary['warning'] }} warning
                            </span>
                        @endif
                    </div>
                </div>
                <div class="booking-stat-icon flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>

        <div class="booking-stat-card booking-stat-card--confirmed p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase text-slate-400">Confirmed</div>
                    <div class="mt-2 text-4xl font-black leading-none text-slate-900">{{ number_format($stats['confirmed']) }}</div>
                    <div class="mt-2 text-sm text-slate-500">Bookings approved and ready for dispatch planning.</div>
                </div>
                <div class="booking-stat-icon flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl">
                    <i class="fas fa-circle-check"></i>
                </div>
            </div>
        </div>

        <div class="booking-stat-card booking-stat-card--completed p-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase text-slate-400">Completed</div>
                    <div class="mt-2 text-4xl font-black leading-none text-slate-900">{{ number_format($stats['completed']) }}</div>
                    <div class="mt-2 text-sm text-slate-500">Closed jobs that are ready for reporting and review.</div>
                </div>
                <div class="booking-stat-icon flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl">
                    <i class="fas fa-check-double"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="booking-workflow-panel p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="text-lg font-bold text-slate-900">Booking Workflow</div>
                <div class="mt-1 text-sm text-slate-500">Switch between the live operations queue and the completed-booking history without leaving this workspace.</div>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ $activeTabUrl }}" class="booking-tab-pill inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold {{ $activeTab === 'active' ? 'booking-tab-pill--active' : '' }}">
                    <span>Active Bookings</span>
                    <span class="rounded-full bg-white/90 px-2 py-0.5 text-xs text-blue-700">{{ number_format($queueCounts['active']) }}</span>
                </a>
                <a href="{{ $completedTabUrl }}" class="booking-tab-pill inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold {{ $activeTab === 'completed' ? 'booking-tab-pill--active' : '' }}">
                    <span>Completed Bookings</span>
                    <span class="rounded-full bg-white/90 px-2 py-0.5 text-xs text-blue-700">{{ number_format($queueCounts['completed']) }}</span>
                </a>
            </div>
        </div>
        @if($activeTab === 'active')
            <div class="mt-4 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                @foreach($filterLabels as $filterValue => $filterLabel)
                    <a href="{{ route('admin.bookings', array_merge(request()->except(['active_page', 'completed_page', 'filter']), ['tab' => 'active'], $filterValue === '' ? [] : ['filter' => $filterValue])) }}"
                       class="booking-filter-pill inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold {{ $activeFilter === $filterValue ? 'booking-filter-pill--active' : '' }}">
                        {{ $filterLabel }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if($activeTab === 'active')
        <div class="booking-queue-panel overflow-hidden">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                <div>
                    <div class="text-lg font-bold text-slate-900">Active Booking Queue</div>
                    <div class="mt-1 text-sm text-slate-500">Pending, confirmed, and in-progress work that still needs operational attention.</div>
                </div>
                <div class="text-right text-xs leading-6 text-slate-400">
                    <div>{{ number_format($queueCounts['today']) }} scheduled today</div>
                    <div>{{ number_format($queueCounts['review_pending']) }} awaiting manual review</div>
                    <div>{{ number_format($queueCounts['upcoming']) }} upcoming &bull; {{ number_format($queueCounts['in_progress']) }} in progress</div>
                    <div>{{ number_format($queueCounts['unassigned']) }} unassigned &bull; {{ number_format($pendingEscalationSummary['critical']) }} critical pending</div>
                </div>
            </div>

            @if($activeBookings->count())
                <div class="divide-y divide-slate-100">
                    @foreach($activeBookings as $booking)
                        @php
                            $allowedStatuses = $booking->allowedTransitions();
                            $scheduledDate = \Carbon\Carbon::parse($booking->scheduled_date);
                            $bookingIsToday = $scheduledDate->isToday();
                            $reviewLocked = in_array($booking->manual_review_status, ['pending', 'blocked'], true);
                            $requestedCleaner = $booking->preferredStaff;
                            $scheduleMeta = match (true) {
                                $scheduledDate->isToday() => ['label' => 'Today', 'class' => 'bg-blue-50 text-blue-700'],
                                $scheduledDate->isPast() => ['label' => 'Overdue', 'class' => 'bg-danger-50 text-danger-700'],
                                $scheduledDate->isTomorrow() => ['label' => 'Tomorrow', 'class' => 'bg-blue-50 text-blue-700'],
                                default => ['label' => 'Upcoming', 'class' => 'bg-slate-100 text-slate-600'],
                            };
                        @endphp
                        <article class="grid gap-5 px-5 py-5 transition hover:bg-slate-50/70 xl:grid-cols-[minmax(0,1.25fr)_minmax(280px,0.8fr)_minmax(300px,0.75fr)] xl:px-6">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="font-mono text-sm font-bold text-blue-700">CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</div>
                                        <div class="mt-2 text-base font-bold text-slate-900">{{ $booking->user->display_name }}</div>
                                        <div class="mt-1 break-all text-xs leading-5 text-slate-500">{{ $booking->user->email }}</div>
                                    </div>
                                    <div class="text-left xl:text-right">
                                        <div class="font-semibold text-slate-900">{{ $scheduledDate->format('M d, Y') }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ \Carbon\Carbon::parse($booking->scheduled_time)->format('h:i A') }}</div>
                                        <span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $scheduleMeta['class'] }}">{{ $scheduleMeta['label'] }}</span>
                                    </div>
                                </div>

                                <div class="mt-4 rounded-2xl border border-slate-100 bg-white p-4">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="font-semibold text-slate-900">{{ $booking->service_label }}</div>
                                            <div class="mt-1 text-xs leading-5 text-slate-500">{{ $booking->street_address }}, {{ $booking->barangay }}</div>
                                        </div>
                                        <div class="flex flex-wrap gap-1.5">
                                            <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700">
                                                {{ number_format($booking->duration_minutes ?? \App\Models\Service::DEFAULT_DURATION_MINUTES) }} min
                                            </span>
                                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                                                @if($booking->isSubscription())
                                                    {{ $booking->subscriptionSummary() }} &middot; Visit {{ $booking->subscription_sequence }}
                                                @else
                                                    One-time
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-3 break-words text-xs text-slate-500">
                                        {{ \App\Models\Booking::paymentMethodLabel($booking->payment_method) }}@if($booking->payment_reference) &middot; Ref {{ $booking->payment_reference }}@endif
                                    </div>
                                    @if($booking->expected_started_at || in_array($booking->status, ['confirmed', 'in_progress'], true))
                                        @php
                                            $expectedStartedAt = $booking->expected_started_at ? \Carbon\Carbon::parse($booking->expected_started_at)->timezone(config('cleanflow.attendance_timezone', 'Asia/Manila')) : $booking->expectedServiceStart()->timezone(config('cleanflow.attendance_timezone', 'Asia/Manila'));
                                            $expectedCompletedAt = $booking->expected_completed_at ? \Carbon\Carbon::parse($booking->expected_completed_at)->timezone(config('cleanflow.attendance_timezone', 'Asia/Manila')) : $booking->expectedServiceCompletion()->timezone(config('cleanflow.attendance_timezone', 'Asia/Manila'));
                                        @endphp
                                        <div class="mt-3 rounded-xl border border-slate-100 bg-slate-50 px-3 py-2 text-xs leading-5 text-slate-600">
                                            <div class="font-bold text-slate-700">Service timing</div>
                                            <div>Expected: {{ $expectedStartedAt->format('h:i A') }} - {{ $expectedCompletedAt->format('h:i A') }}</div>
                                            @if($booking->started_at)
                                                <div>Actual start: {{ \Carbon\Carbon::parse($booking->started_at)->timezone(config('cleanflow.attendance_timezone', 'Asia/Manila'))->format('h:i A') }}</div>
                                            @endif
                                            @if($booking->on_time_status)
                                                <span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $booking->timelinessBadgeClass() }}">
                                                    {{ $booking->timelinessLabel() }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$booking->status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $statusLabels[$booking->status] ?? ucfirst(str_replace('_', ' ', $booking->status)) }}
                                    </span>
                                    <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold {{ $paymentStatusClasses[$booking->payment_status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ \App\Models\Booking::paymentStatusLabel($booking->payment_status) }}
                                    </span>
                                    @if($booking->pending_escalation)
                                        <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold {{ $booking->pending_escalation['class'] }}">
                                            {{ $booking->pending_escalation['label'] }} &bull; {{ $booking->pending_escalation['age_label'] }}
                                        </span>
                                    @endif
                                    @if(isset($reviewLabels[$booking->manual_review_status]))
                                        <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold {{ $reviewClasses[$booking->manual_review_status] }}">
                                            {{ $reviewLabels[$booking->manual_review_status] }}
                                        </span>
                                    @endif
                                    @if($showMarketplaceProvider && $booking->effectiveProviderAssignmentStatus() === 'declined')
                                        <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-[11px] font-semibold text-red-700">
                                            Provider declined
                                        </span>
                                    @elseif($showMarketplaceProvider && $booking->effectiveProviderAssignmentStatus() === 'accepted')
                                        <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-semibold text-emerald-700">
                                            Provider accepted
                                        </span>
                                    @elseif($showMarketplaceProvider && $booking->effectiveProviderAssignmentStatus() === 'pending')
                                        <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-[11px] font-semibold text-amber-700">
                                            Provider pending
                                        </span>
                                    @endif
                                    @if($booking->dispute_status)
                                        <span class="inline-flex rounded-full {{ $booking->hasOpenDispute() ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600' }} px-3 py-1 text-[11px] font-semibold">
                                            {{ \App\Models\Booking::disputeStatusLabel($booking->dispute_status) }}
                                        </span>
                                    @endif
                                </div>

                                @if(! empty($booking->risk_reasons))
                                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-[11px] leading-5 text-amber-800">
                                        <div class="font-semibold uppercase tracking-[0.14em]">Risk signals</div>
                                        @foreach($booking->risk_reasons as $reason)
                                            <div class="mt-1">{{ $reason }}</div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <div class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">Assignment</div>
                                @if($requestedCleaner)
                                    <div class="mt-3 rounded-xl border border-blue-100 bg-blue-50 px-3 py-2">
                                        <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-blue-700">Preferred cleaner</div>
                                        <div class="mt-1 text-sm font-semibold leading-5 text-slate-900">{{ $requestedCleaner->display_name }}</div>
                                        @if(isset($preferredStatusLabels[$booking->preferred_staff_status]))
                                            <div class="mt-2">
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $preferredStatusClasses[$booking->preferred_staff_status] }}">
                                                    {{ $preferredStatusLabels[$booking->preferred_staff_status] }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                                            <i class="fas fa-user-tie text-xs"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">Internal Staff</div>
                                            <div class="mt-1 text-sm font-bold text-slate-900">{{ $booking->staff?->display_name ?? 'Unassigned' }}</div>
                                            <div class="mt-1 text-xs leading-5 text-slate-500">Staff account used for portal access, status updates, attendance, and live operations.</div>
                                        </div>
                                    </div>
                                </div>
                                @if($showMarketplaceProvider)
                                <div class="mt-4 rounded-xl border border-emerald-100 bg-emerald-50/70 p-3">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                            <i class="fas fa-store text-xs"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="text-[10px] font-bold uppercase tracking-[0.14em] text-emerald-700">Marketplace Provider</div>
                                            <div class="mt-1 text-sm font-bold text-slate-900">{{ $booking->cleanerApplication?->business_name ?? 'Not assigned' }}</div>
                                            <div class="mt-1 text-xs leading-5 text-slate-600">
                                                @if($booking->cleanerApplication)
                                                    {{ $booking->cleanerApplication->isTeam() ? 'Approved cleaning team' : 'Approved individual cleaner' }}
                                                    @if($booking->cleanerApplication->isTeam() && $booking->cleanerApplication->team_size)
                                                        &bull; {{ $booking->cleanerApplication->team_size }} cleaners
                                                    @endif
                                                @else
                                                    Approved external provider connected to the marketplace workflow.
                                                @endif
                                            </div>
                                            @if($booking->cleanerApplication)
                                                <div class="mt-2">
                                                    <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-bold {{ $booking->providerAssignmentBadgeClass() }}">
                                                        {{ \App\Models\Booking::providerAssignmentStatusLabel($booking->effectiveProviderAssignmentStatus()) }}
                                                    </span>
                                                    @if($booking->provider_assignment_responded_at)
                                                        <span class="ml-2 text-[11px] text-slate-500">{{ $booking->provider_assignment_responded_at->format('M d, h:i A') }}</span>
                                                    @endif
                                                </div>
                                                @if($booking->provider_gross_amount !== null)
                                                    <div class="mt-3 grid grid-cols-2 gap-2 rounded-xl border border-emerald-100 bg-white/80 p-3 text-xs">
                                                        <div>
                                                            <div class="font-bold uppercase text-slate-400">Gross</div>
                                                            <div class="mt-1 font-black text-slate-900">&#8369;{{ number_format((float) $booking->provider_gross_amount, 2) }}</div>
                                                        </div>
                                                        <div>
                                                            <div class="font-bold uppercase text-slate-400">Commission</div>
                                                            <div class="mt-1 font-black text-blue-700">&#8369;{{ number_format((float) $booking->platform_commission_amount, 2) }}</div>
                                                        </div>
                                                        <div>
                                                            <div class="font-bold uppercase text-slate-400">Payout</div>
                                                            <div class="mt-1 font-black text-emerald-700">&#8369;{{ number_format((float) $booking->provider_payout_amount, 2) }}</div>
                                                        </div>
                                                        <div>
                                                            <div class="font-bold uppercase text-slate-400">Status</div>
                                                            <div class="mt-1 font-black text-slate-700">{{ \App\Models\Booking::providerPayoutStatusLabel($booking->provider_payout_status) }}</div>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <form action="{{ route('admin.bookings.provider', $booking->id) }}" method="POST" class="mt-2 space-y-2">
                                    @csrf
                                    @method('PATCH')
                                    <div class="px-1 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Assign marketplace provider</div>
                                    <div class="grid grid-cols-[minmax(0,1fr)_auto] gap-2">
                                        <select name="cleaner_application_id" {{ $reviewLocked ? 'disabled' : '' }} class="min-w-0 rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-blue-500 focus:outline-hidden {{ $reviewLocked ? 'bg-slate-100 text-slate-400' : '' }}">
                                            <option value="">No marketplace provider</option>
                                            @foreach($approvedCleanerApplications as $provider)
                                                @php
                                                    $providerCoversBooking = $provider->coversBarangay($booking->barangay);
                                                    $providerAvailable = $provider->isAvailableForAssignment();
                                                    $providerHasCapacity = $provider->hasDailyCapacityFor($booking->scheduled_date, $booking->id);
                                                    $providerDisabled = (! $providerCoversBooking || ! $providerAvailable || ! $providerHasCapacity) && $booking->cleaner_application_id !== $provider->id;
                                                    $providerOptionNote = '';

                                                    if (! $providerCoversBooking) {
                                                        $providerOptionNote = ' - outside service area';
                                                    } elseif (! $providerAvailable) {
                                                        $providerOptionNote = ' - '.$provider->availabilityLabel();
                                                    } elseif (! $providerHasCapacity) {
                                                        $providerOptionNote = ' - at daily limit';
                                                    }
                                                @endphp
                                                <option value="{{ $provider->id }}" {{ $booking->cleaner_application_id === $provider->id ? 'selected' : '' }} {{ $providerDisabled ? 'disabled' : '' }}>
                                                    {{ $provider->business_name }} - {{ $provider->isTeam() ? 'Team' : 'Individual' }}{{ $providerOptionNote }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit" {{ $reviewLocked ? 'disabled' : '' }} class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-600 text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300" title="Save marketplace provider">
                                            <i class="fas fa-store text-xs"></i>
                                        </button>
                                    </div>
                                    @if($approvedCleanerApplications->isEmpty())
                                        <div class="text-xs text-amber-700">Approve at least one cleaner application before assigning a marketplace provider.</div>
                                    @elseif($reviewLocked)
                                        <div class="text-xs text-amber-700">Clear manual review before assigning a marketplace provider.</div>
                                    @elseif($approvedCleanerApplications->every(fn ($provider) => ! $provider->coversBarangay($booking->barangay)))
                                        <div class="text-xs text-red-700">No approved marketplace provider covers {{ $booking->barangay }}.</div>
                                    @elseif($approvedCleanerApplications->filter(fn ($provider) => $provider->coversBarangay($booking->barangay))->every(fn ($provider) => ! $provider->isAvailableForAssignment()))
                                        <div class="text-xs text-amber-700">Approved providers for {{ $booking->barangay }} are currently paused or unavailable.</div>
                                    @elseif($approvedCleanerApplications->filter(fn ($provider) => $provider->coversBarangay($booking->barangay) && $provider->isAvailableForAssignment())->every(fn ($provider) => ! $provider->hasDailyCapacityFor($booking->scheduled_date, $booking->id)))
                                        <div class="text-xs text-amber-700">Approved providers for {{ $booking->barangay }} have reached their daily booking limit.</div>
                                    @endif
                                </form>
                                @endif
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">Actions</div>
                                    <a href="{{ route('bookings.show', $booking->id) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-blue-200 bg-white px-3 py-2 text-xs font-bold text-blue-700 transition hover:bg-blue-50">
                                        <i class="fas fa-eye"></i>
                                        View
                                    </a>
                                </div>
                                @if($booking->manual_review_status === 'pending')
                                    <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3">
                                        <div class="text-[11px] font-bold uppercase tracking-[0.14em] text-amber-800">Manual review required</div>
                                        <div class="mt-2 grid grid-cols-2 gap-2">
                                            <form action="{{ route('admin.bookings.review', $booking->id) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="review_status" value="approved">
                                                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-2 py-2 text-[11px] font-bold text-white">
                                                    <i class="fas fa-circle-check"></i>
                                                    Approve
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.bookings.review', $booking->id) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="review_status" value="blocked">
                                                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-red-600 px-2 py-2 text-[11px] font-bold text-white">
                                                    <i class="fas fa-ban"></i>
                                                    Block
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endif
                                <form action="{{ route('admin.bookings.status', $booking->id) }}" method="POST" class="mt-3 space-y-3 rounded-xl border border-slate-200 bg-white p-3">
                                    @csrf
                                    @method('PATCH')
                                    <div>
                                        <label class="mb-1 block px-1 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Staff</label>
                                        <select name="staff_id" {{ $reviewLocked ? 'disabled' : '' }} class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs focus:border-blue-500 focus:outline-hidden {{ $reviewLocked ? 'bg-slate-100 text-slate-400' : '' }}">
                                            <option value="" {{ $booking->staff_id ? '' : 'selected' }}>Unassigned</option>
                                            @foreach($staffList as $staff)
                                                @php
                                                    $staffBusyForSlot = in_array($staff->id, $booking->busy_staff_ids ?? [], true);
                                                    $staffAvailableForAssignment = ! $staffBusyForSlot
                                                        && (! $bookingIsToday || $staff->is_present);
                                                @endphp
                                                @if($staffAvailableForAssignment)
                                                    <option value="{{ $staff->id }}" {{ $booking->staff_id === $staff->id ? 'selected' : '' }}>
                                                        {{ $staff->display_name }}{{ $booking->preferred_staff_id === $staff->id ? ' (Requested)' : '' }}
                                                    </option>
                                                @elseif($staffBusyForSlot)
                                                    <option value="{{ $staff->id }}" disabled>
                                                        {{ $staff->display_name }}{{ $booking->preferred_staff_id === $staff->id ? ' (Requested, Busy)' : ' (Busy)' }}
                                                    </option>
                                                @elseif($booking->staff_id === $staff->id)
                                                    <option value="{{ $staff->id }}" selected>
                                                        {{ $staff->display_name }}{{ $booking->preferred_staff_id === $staff->id ? ' (Requested, Absent)' : ' (Absent)' }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 block px-1 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Status</label>
                                        <select name="status" {{ $reviewLocked ? 'disabled' : '' }} class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs focus:border-blue-500 focus:outline-hidden {{ $reviewLocked ? 'bg-slate-100 text-slate-400' : '' }}">
                                            @foreach($allowedStatuses as $statusOption)
                                                <option value="{{ $statusOption }}" {{ $booking->status === $statusOption ? 'selected' : '' }}>
                                                    {{ $statusLabels[$statusOption] ?? ucfirst(str_replace('_', ' ', $statusOption)) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-1 block px-1 text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">Payment</label>
                                        <select name="payment_status" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs focus:border-blue-500 focus:outline-hidden">
                                            @foreach(\App\Models\Booking::paymentStatuses() as $paymentStatusOption)
                                                <option value="{{ $paymentStatusOption }}" {{ $booking->payment_status === $paymentStatusOption ? 'selected' : '' }}>
                                                    {{ \App\Models\Booking::paymentStatusLabel($paymentStatusOption) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @if($booking->manual_review_status === 'pending')
                                        <div class="text-xs text-amber-700">Approve or block the manual review before changing status or staff.</div>
                                    @elseif($booking->manual_review_status === 'blocked')
                                        <div class="text-xs text-red-700">Blocked bookings stay out of the staffing queue.</div>
                                    @elseif($bookingIsToday && $presentStaffCount === 0)
                                        <div class="text-xs text-red-700">No cleaners are marked present for today's operations.</div>
                                    @elseif($bookingIsToday && ($booking->available_present_staff_count ?? 0) === 0 && ! $booking->staff_id)
                                        <div class="text-xs text-amber-700">All available cleaners are already booked or inside the 1-hour rest buffer for this schedule.</div>
                                    @elseif(! $bookingIsToday && ($booking->available_staff_count ?? 0) === 0 && ! $booking->staff_id)
                                        <div class="text-xs text-amber-700">All cleaners are already booked or inside the 1-hour rest buffer for this schedule.</div>
                                    @endif
                                    @if($booking->status === 'in_progress')
                                        <div class="rounded-lg border border-blue-100 bg-blue-50 px-2.5 py-2 text-[11px] text-blue-800">
                                            <div class="font-bold">Completion proof</div>
                                            <div class="mt-1">
                                                Before photos: {{ $booking->before_service_proofs_count > 0 ? 'Uploaded' : 'Missing' }}
                                                &bull;
                                                After photos: {{ $booking->after_service_proofs_count > 0 ? 'Uploaded' : 'Missing' }}
                                            </div>
                                            <div class="mt-1">The assigned staff member must upload both before and after photos before completion.</div>
                                            <a href="{{ route('bookings.show', $booking->id) }}#proof-of-service" class="mt-2 inline-flex items-center gap-1 font-bold text-blue-700 underline hover:text-blue-900">
                                                <i class="fas fa-arrow-up-right-from-square"></i>
                                                View proof files
                                            </a>
                                        </div>
                                    @endif
                                    <button type="submit" {{ $reviewLocked ? 'disabled' : '' }} class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-700 px-3 py-2.5 text-xs font-bold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:bg-slate-300">
                                        <i class="fas fa-check"></i>
                                        Confirm changes
                                    </button>
                                </form>
                                @if($showMarketplaceProvider && $booking->provider_gross_amount !== null && $booking->payment_method === 'on_site_cash')
                                    <form action="{{ route('admin.bookings.provider-commission', $booking->id) }}" method="POST" enctype="multipart/form-data" class="mt-3 rounded-xl border border-orange-100 bg-orange-50/70 p-2">
                                        @csrf
                                        @method('PATCH')
                                        <div class="mb-1 px-1 text-[10px] font-bold uppercase tracking-[0.14em] text-orange-700">Cash commission</div>
                                        <div class="grid grid-cols-[minmax(0,1fr)_auto] gap-2">
                                            <select name="provider_commission_status" class="min-w-0 rounded-lg border border-slate-300 px-2.5 py-2 text-xs focus:border-orange-500 focus:outline-hidden">
                                                @foreach(\App\Models\Booking::providerCommissionStatuses() as $commissionStatusOption)
                                                    @continue($commissionStatusOption === 'not_applicable')
                                                    <option value="{{ $commissionStatusOption }}" {{ ($booking->provider_commission_status ?: 'unpaid') === $commissionStatusOption ? 'selected' : '' }}>
                                                        {{ \App\Models\Booking::providerCommissionStatusLabel($commissionStatusOption) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-orange-600 text-white transition hover:bg-orange-700" title="Save cash commission">
                                                <i class="fas fa-check text-xs"></i>
                                            </button>
                                        </div>
                                        <div class="mt-2 grid gap-2">
                                            <input type="text" name="provider_commission_reference" value="{{ old('provider_commission_reference', $booking->provider_commission_reference) }}" placeholder="Commission payment reference" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs focus:border-orange-500 focus:outline-hidden">
                                            <input type="datetime-local" name="provider_commission_paid_at" value="{{ old('provider_commission_paid_at', $booking->provider_commission_paid_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs focus:border-orange-500 focus:outline-hidden">
                                            <input type="file" name="provider_commission_proof" accept=".jpg,.jpeg,.png,.pdf" class="w-full rounded-lg border border-dashed border-orange-200 bg-white/70 px-2.5 py-2 text-[11px] text-slate-600 file:mr-2 file:rounded-md file:border-0 file:bg-orange-600 file:px-2 file:py-1 file:text-[11px] file:font-bold file:text-white">
                                            @if($booking->provider_commission_proof_path)
                                                <a href="{{ route('admin.bookings.provider-commission-proof', $booking->id) }}" class="inline-flex items-center gap-2 px-1 text-[11px] font-bold text-orange-700 hover:text-orange-900">
                                                    <i class="fas fa-paperclip"></i>
                                                    Download commission proof
                                                </a>
                                            @endif
                                        </div>
                                        <div class="mt-2 px-1 text-[11px] leading-5 text-slate-500">
                                            Cleaner keeps &#8369;{{ number_format((float) $booking->provider_payout_amount, 2) }} and remits &#8369;{{ number_format((float) $booking->provider_commission_due, 2) }} to CleanFlow.
                                        </div>
                                    </form>
                                @endif
                                @if($showMarketplaceProvider && $booking->provider_gross_amount !== null && $booking->payment_method !== 'on_site_cash')
                                    <form action="{{ route('admin.bookings.payout', $booking->id) }}" method="POST" enctype="multipart/form-data" class="mt-3 rounded-xl border border-emerald-100 bg-white p-2">
                                        @csrf
                                        @method('PATCH')
                                        <div class="mb-1 px-1 text-[10px] font-bold uppercase tracking-[0.14em] text-emerald-700">Provider payout</div>
                                        <div class="grid grid-cols-[minmax(0,1fr)_auto] gap-2">
                                            <select name="provider_payout_status" class="min-w-0 rounded-lg border border-slate-300 px-2.5 py-2 text-xs focus:border-emerald-500 focus:outline-hidden">
                                                @foreach(\App\Models\Booking::providerPayoutStatuses() as $payoutStatusOption)
                                                    <option value="{{ $payoutStatusOption }}" {{ ($booking->provider_payout_status ?: 'pending') === $payoutStatusOption ? 'selected' : '' }}>
                                                        {{ \App\Models\Booking::providerPayoutStatusLabel($payoutStatusOption) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-700 text-white transition hover:bg-emerald-800" title="Save payout status">
                                                <i class="fas fa-check text-xs"></i>
                                            </button>
                                        </div>
                                        <div class="mt-2 grid gap-2">
                                            <input type="text" name="provider_payout_reference" value="{{ old('provider_payout_reference', $booking->provider_payout_reference) }}" placeholder="Payout reference for paid" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs focus:border-emerald-500 focus:outline-hidden">
                                            <input type="datetime-local" name="provider_payout_paid_at" value="{{ old('provider_payout_paid_at', $booking->provider_payout_paid_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs focus:border-emerald-500 focus:outline-hidden">
                                            <input type="file" name="provider_payout_proof" accept=".jpg,.jpeg,.png,.pdf" class="w-full rounded-lg border border-dashed border-emerald-200 bg-emerald-50/50 px-2.5 py-2 text-[11px] text-slate-600 file:mr-2 file:rounded-md file:border-0 file:bg-emerald-700 file:px-2 file:py-1 file:text-[11px] file:font-bold file:text-white">
                                            @if($booking->provider_payout_proof_path)
                                                <a href="{{ route('admin.bookings.payout-proof', $booking->id) }}" class="inline-flex items-center gap-2 px-1 text-[11px] font-bold text-emerald-700 hover:text-emerald-900">
                                                    <i class="fas fa-paperclip"></i>
                                                    Download payout proof
                                                </a>
                                            @endif
                                        </div>
                                        <div class="mt-2 px-1 text-[11px] leading-5 text-slate-500">Paid requires customer payment paid, payout date, and reference.</div>
                                    </form>
                                @endif
                                @if($booking->hasOpenDispute())
                                    <form action="{{ route('admin.bookings.dispute', $booking->id) }}" method="POST" class="mt-3 rounded-xl border border-red-100 bg-red-50/70 p-3">
                                        @csrf
                                        @method('PATCH')
                                        <div class="text-[10px] font-bold uppercase tracking-[0.14em] text-red-700">Open dispute</div>
                                        <div class="mt-2 text-xs font-bold text-slate-900">{{ $booking->disputeReasonLabel() }}</div>
                                        <div class="mt-1 text-xs leading-5 text-slate-600">{{ $booking->dispute_description }}</div>
                                        <select name="dispute_resolution" class="mt-3 w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs focus:border-red-500 focus:outline-hidden">
                                            @foreach(\App\Models\Booking::disputeResolutions() as $resolution => $label)
                                                <option value="{{ $resolution }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <textarea name="dispute_admin_notes" rows="2" class="mt-2 w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs focus:border-red-500 focus:outline-hidden" placeholder="Admin resolution notes"></textarea>
                                        <button type="submit" class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-red-700 px-3 py-2 text-xs font-bold text-white transition hover:bg-red-800">
                                            <i class="fas fa-check"></i>
                                            Resolve dispute
                                        </button>
                                    </form>
                                @endif
                                @if($booking->status === 'in_progress' && !is_null($booking->current_latitude) && !is_null($booking->current_longitude))
                                    <a href="{{ route('bookings.show', $booking->id) }}" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-blue-200 bg-white px-3 py-2 text-[11px] font-bold text-blue-700">
                                        <i class="fas fa-location-dot"></i>
                                        Live Location
                                    </a>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $activeBookings->links('pagination::tailwind') }}
                </div>
            @else
                <div class="px-6 py-14 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <i class="fas fa-calendar-check text-2xl"></i>
                    </div>
                    <div class="mt-4 text-lg font-bold text-slate-900">No bookings need attention right now</div>
                    <div class="mx-auto mt-2 max-w-md text-sm text-slate-500">Pending, confirmed, and in-progress bookings will appear here when the operations queue has work to review.</div>
                </div>
            @endif
        </div>
    @else
        <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                <div>
                    <div class="text-lg font-bold text-slate-900">Completed Booking History</div>
                    <div class="mt-1 text-sm text-slate-500">Completed services and other closed records kept separate from the operational queue.</div>
                </div>
                <div class="text-xs text-slate-400">{{ number_format($queueCounts['completed']) }} historical record{{ $queueCounts['completed'] === 1 ? '' : 's' }}</div>
            </div>

            @if($completedBookings->count())
                <div class="overflow-x-auto">
                    <table class="min-w-[980px] w-full border-separate border-spacing-0 text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                                <th class="px-6 py-3">Booking</th>
                                <th class="px-6 py-3">Customer</th>
                                <th class="px-6 py-3">Service</th>
                                <th class="px-6 py-3">Assigned Cleaner</th>
                                <th class="px-6 py-3">Closed Date</th>
                                <th class="px-6 py-3">Service Timing</th>
                                <th class="px-6 py-3">Final Price</th>
                                <th class="px-6 py-3">Rating</th>
                                <th class="px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($completedBookings as $booking)
                                @php
                                    $closedAt = optional($booking->updated_at);
                                @endphp
                                <tr class="transition hover:bg-slate-50">
                                    <td class="border-t border-slate-100 px-6 py-4 align-top">
                                        <div class="font-mono text-sm font-bold text-blue-700">CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</div>
                                        <div class="mt-1 text-xs text-slate-400">{{ optional($booking->created_at)->diffForHumans() }}</div>
                                    </td>
                                    <td class="border-t border-slate-100 px-6 py-4 align-top">
                                        <div class="font-semibold text-slate-900">{{ $booking->user->display_name }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $booking->user->email }}</div>
                                    </td>
                                    <td class="border-t border-slate-100 px-6 py-4 align-top">
                                        <div class="font-semibold text-slate-900">{{ $booking->service_label }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $booking->street_address }}, {{ $booking->barangay }}</div>
                                        <div class="mt-2 text-xs text-slate-500">
                                            @if($booking->isSubscription())
                                            {{ $booking->subscriptionSummary() }} &bull; Visit {{ $booking->subscription_sequence }}
                                            @else
                                            One-time booking
                                            @endif
                                        </div>
                                    </td>
                                    <td class="border-t border-slate-100 px-6 py-4 align-top">
                                        <div class="font-semibold text-slate-900">{{ $booking->staff?->display_name ?? 'Unassigned' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $booking->staff ? 'Assigned staff record' : 'No staff assigned' }}</div>
                                        @if($showMarketplaceProvider)
                                        <div class="mt-3 rounded-xl border border-blue-100 bg-blue-50 px-3 py-2">
                                            <div class="text-[10px] font-bold uppercase tracking-[0.14em] text-blue-700">Marketplace</div>
                                            <div class="mt-1 text-xs font-bold text-slate-900">{{ $booking->cleanerApplication?->business_name ?? 'Not assigned' }}</div>
                                            @if($booking->cleanerApplication)
                                                <span class="mt-2 inline-flex rounded-full px-2 py-1 text-[10px] font-bold {{ $booking->providerAssignmentBadgeClass() }}">
                                                    {{ \App\Models\Booking::providerAssignmentStatusLabel($booking->effectiveProviderAssignmentStatus()) }}
                                                </span>
                                            @endif
                                        </div>
                                        @endif
                                    </td>
                                    <td class="border-t border-slate-100 px-6 py-4 align-top">
                                        <div class="font-semibold text-slate-900">{{ $closedAt ? $closedAt->format('M d, Y') : 'Not available' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $closedAt ? $closedAt->format('h:i A') : '' }}</div>
                                        <span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$booking->status] ?? 'bg-slate-100 text-slate-600' }}">
                                            {{ $statusLabels[$booking->status] ?? ucfirst(str_replace('_', ' ', $booking->status)) }}
                                        </span>
                                        @if(isset($reviewLabels[$booking->manual_review_status]))
                                            <div class="mt-2">
                                                <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold {{ $reviewClasses[$booking->manual_review_status] }}">
                                                    {{ $reviewLabels[$booking->manual_review_status] }}
                                                </span>
                                            </div>
                                        @endif
                                        @if($booking->dispute_status)
                                            <div class="mt-2">
                                                <span class="inline-flex rounded-full {{ $booking->hasOpenDispute() ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600' }} px-3 py-1 text-[11px] font-semibold">
                                                    {{ \App\Models\Booking::disputeStatusLabel($booking->dispute_status) }}
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="border-t border-slate-100 px-6 py-4 align-top">
                                        @if($booking->status === 'cancelled')
                                            <span class="text-xs italic text-slate-400">Not applicable</span>
                                        @else
                                            @php
                                                $expectedStartedAt = $booking->expected_started_at ? \Carbon\Carbon::parse($booking->expected_started_at)->timezone(config('cleanflow.attendance_timezone', 'Asia/Manila')) : null;
                                                $expectedCompletedAt = $booking->expected_completed_at ? \Carbon\Carbon::parse($booking->expected_completed_at)->timezone(config('cleanflow.attendance_timezone', 'Asia/Manila')) : null;
                                                $actualStartedAt = $booking->started_at ? \Carbon\Carbon::parse($booking->started_at)->timezone(config('cleanflow.attendance_timezone', 'Asia/Manila')) : null;
                                                $actualCompletedAt = $booking->completed_at ? \Carbon\Carbon::parse($booking->completed_at)->timezone(config('cleanflow.attendance_timezone', 'Asia/Manila')) : null;
                                            @endphp
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $booking->timelinessBadgeClass() }}">
                                                {{ $booking->timelinessLabel() }}
                                            </span>
                                            <div class="mt-2 text-xs leading-5 text-slate-500">
                                                @if($expectedStartedAt && $expectedCompletedAt)
                                                    <div>Expected: {{ $expectedStartedAt->format('h:i A') }} - {{ $expectedCompletedAt->format('h:i A') }}</div>
                                                @endif
                                                @if($actualStartedAt && $actualCompletedAt)
                                                    <div>Actual: {{ $actualStartedAt->format('h:i A') }} - {{ $actualCompletedAt->format('h:i A') }}</div>
                                                @endif
                                                <div>{{ $booking->on_time_notes ?? $booking->timelinessSummary() }}</div>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="border-t border-slate-100 px-6 py-4 align-top">
                                        <div class="font-semibold text-slate-900">&#8369;{{ number_format($booking->price, 2) }}</div>
                                        <div class="mt-2">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $paymentStatusClasses[$booking->payment_status] ?? 'bg-slate-100 text-slate-600' }}">
                                                {{ \App\Models\Booking::paymentStatusLabel($booking->payment_status) }}
                                            </span>
                                        </div>
                                        <div class="mt-1 text-xs text-slate-500">{{ \App\Models\Booking::paymentMethodLabel($booking->payment_method) }}</div>
                                    </td>
                                    <td class="border-t border-slate-100 px-6 py-4 align-top">
                                        @if($booking->status === 'cancelled')
                                            <span class="text-xs italic text-slate-400">Not applicable</span>
                                        @elseif($booking->rating)
                                            <div class="inline-flex items-center gap-2 text-xs font-bold text-amber-500">
                                                <i class="fas fa-star"></i>
                                                <span>{{ number_format((float) $booking->rating->stars, 1) }} / 5</span>
                                            </div>
                                            @if($booking->rating->comment)
                                                <div class="mt-1 max-w-[180px] text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($booking->rating->comment, 42) }}</div>
                                            @endif
                                        @else
                                            <span class="text-xs italic text-slate-400">No rating yet</span>
                                        @endif
                                    </td>
                                    <td class="border-t border-slate-100 px-6 py-4 align-top">
                                        <a href="{{ route('bookings.show', $booking->id) }}" class="inline-flex items-center gap-2 rounded-xl border border-secondary-200 bg-secondary-50 px-3 py-2 text-xs font-bold text-secondary-700">
                                            <i class="fas fa-eye"></i>
                                            View Details
                                        </a>
                                        @if($showMarketplaceProvider && $booking->provider_gross_amount !== null && $booking->payment_method === 'on_site_cash')
                                            <form action="{{ route('admin.bookings.provider-commission', $booking->id) }}" method="POST" enctype="multipart/form-data" class="mt-3 min-w-[240px] rounded-xl border border-orange-100 bg-orange-50/70 p-2">
                                                @csrf
                                                @method('PATCH')
                                                <div class="mb-1 px-1 text-[10px] font-bold uppercase tracking-[0.14em] text-orange-700">Cash commission</div>
                                                <select name="provider_commission_status" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs focus:border-orange-500 focus:outline-hidden">
                                                    @foreach(\App\Models\Booking::providerCommissionStatuses() as $commissionStatusOption)
                                                        @continue($commissionStatusOption === 'not_applicable')
                                                        <option value="{{ $commissionStatusOption }}" {{ ($booking->provider_commission_status ?: 'unpaid') === $commissionStatusOption ? 'selected' : '' }}>
                                                            {{ \App\Models\Booking::providerCommissionStatusLabel($commissionStatusOption) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div class="mt-2 grid gap-2">
                                                    <input type="text" name="provider_commission_reference" value="{{ old('provider_commission_reference', $booking->provider_commission_reference) }}" placeholder="Commission payment reference" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs focus:border-orange-500 focus:outline-hidden">
                                                    <input type="datetime-local" name="provider_commission_paid_at" value="{{ old('provider_commission_paid_at', $booking->provider_commission_paid_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs focus:border-orange-500 focus:outline-hidden">
                                                    <input type="file" name="provider_commission_proof" accept=".jpg,.jpeg,.png,.pdf" class="w-full rounded-lg border border-dashed border-orange-200 bg-white/70 px-2.5 py-2 text-[11px] text-slate-600 file:mr-2 file:rounded-md file:border-0 file:bg-orange-600 file:px-2 file:py-1 file:text-[11px] file:font-bold file:text-white">
                                                    @if($booking->provider_commission_proof_path)
                                                        <a href="{{ route('admin.bookings.provider-commission-proof', $booking->id) }}" class="inline-flex items-center gap-2 px-1 text-[11px] font-bold text-orange-700 hover:text-orange-900">
                                                            <i class="fas fa-paperclip"></i>
                                                            Download commission proof
                                                        </a>
                                                    @endif
                                                </div>
                                                <button type="submit" class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-orange-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-orange-700">
                                                    <i class="fas fa-check"></i>
                                                    Save commission
                                                </button>
                                                <div class="mt-2 px-1 text-[11px] leading-5 text-slate-500">Paid requires commission date and reference.</div>
                                            </form>
                                        @endif
                                        @if($showMarketplaceProvider && $booking->provider_gross_amount !== null && $booking->payment_method !== 'on_site_cash')
                                            <form action="{{ route('admin.bookings.payout', $booking->id) }}" method="POST" enctype="multipart/form-data" class="mt-3 min-w-[240px] rounded-xl border border-emerald-100 bg-emerald-50/60 p-2">
                                                @csrf
                                                @method('PATCH')
                                                <div class="mb-1 px-1 text-[10px] font-bold uppercase tracking-[0.14em] text-emerald-700">Provider payout</div>
                                                <select name="provider_payout_status" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs focus:border-emerald-500 focus:outline-hidden">
                                                    @foreach(\App\Models\Booking::providerPayoutStatuses() as $payoutStatusOption)
                                                        <option value="{{ $payoutStatusOption }}" {{ ($booking->provider_payout_status ?: 'pending') === $payoutStatusOption ? 'selected' : '' }}>
                                                            {{ \App\Models\Booking::providerPayoutStatusLabel($payoutStatusOption) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div class="mt-2 grid gap-2">
                                                    <input type="text" name="provider_payout_reference" value="{{ old('provider_payout_reference', $booking->provider_payout_reference) }}" placeholder="Payout reference for paid" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs focus:border-emerald-500 focus:outline-hidden">
                                                    <input type="datetime-local" name="provider_payout_paid_at" value="{{ old('provider_payout_paid_at', $booking->provider_payout_paid_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs focus:border-emerald-500 focus:outline-hidden">
                                                    <input type="file" name="provider_payout_proof" accept=".jpg,.jpeg,.png,.pdf" class="w-full rounded-lg border border-dashed border-emerald-200 bg-white/70 px-2.5 py-2 text-[11px] text-slate-600 file:mr-2 file:rounded-md file:border-0 file:bg-emerald-700 file:px-2 file:py-1 file:text-[11px] file:font-bold file:text-white">
                                                    @if($booking->provider_payout_proof_path)
                                                        <a href="{{ route('admin.bookings.payout-proof', $booking->id) }}" class="inline-flex items-center gap-2 px-1 text-[11px] font-bold text-emerald-700 hover:text-emerald-900">
                                                            <i class="fas fa-paperclip"></i>
                                                            Download payout proof
                                                        </a>
                                                    @endif
                                                </div>
                                                <button type="submit" class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-700 px-3 py-2 text-xs font-bold text-white transition hover:bg-emerald-800">
                                                    <i class="fas fa-check"></i>
                                                    Save payout
                                                </button>
                                                <div class="mt-2 px-1 text-[11px] leading-5 text-slate-500">Paid requires payment date and reference.</div>
                                            </form>
                                        @endif
                                        @if($booking->hasOpenDispute())
                                            <form action="{{ route('admin.bookings.dispute', $booking->id) }}" method="POST" class="mt-3 min-w-[220px] rounded-xl border border-red-100 bg-red-50/70 p-2 text-left">
                                                @csrf
                                                @method('PATCH')
                                                <div class="mb-1 px-1 text-[10px] font-bold uppercase tracking-[0.14em] text-red-700">Open dispute</div>
                                                <div class="px-1 text-xs font-bold text-slate-900">{{ $booking->disputeReasonLabel() }}</div>
                                                <select name="dispute_resolution" class="mt-2 w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs focus:border-red-500 focus:outline-hidden">
                                                    @foreach(\App\Models\Booking::disputeResolutions() as $resolution => $label)
                                                        <option value="{{ $resolution }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <textarea name="dispute_admin_notes" rows="2" class="mt-2 w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs focus:border-red-500 focus:outline-hidden" placeholder="Admin notes"></textarea>
                                                <button type="submit" class="mt-2 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-red-700 px-3 py-2 text-xs font-bold text-white transition hover:bg-red-800">
                                                    <i class="fas fa-check"></i>
                                                    Resolve dispute
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $completedBookings->links('pagination::tailwind') }}
                </div>
            @else
                <div class="px-6 py-14 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <i class="fas fa-box-archive text-2xl"></i>
                    </div>
                    <div class="mt-4 text-lg font-bold text-slate-900">No closed bookings yet</div>
                    <div class="mx-auto mt-2 max-w-md text-sm text-slate-500">Completed and cancelled bookings will appear here once jobs begin moving through the workflow.</div>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
