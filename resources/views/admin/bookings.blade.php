@extends('layouts.admin')
@section('title', 'Bookings')
@section('page-title', 'Booking Management')
@section('page-subtitle', 'Review service requests, assign available staff, and update booking progress')

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
        'confirmed' => 'bg-accent-50 text-accent-700',
        'in_progress' => 'bg-primary-100 text-primary-700',
        'completed' => 'bg-accent-100 text-accent-800',
        'cancelled' => 'bg-danger-100 text-danger-700',
    ];
    $reviewLabels = [
        'pending' => 'Manual Review',
        'approved' => 'Review Approved',
        'blocked' => 'Review Blocked',
    ];
    $reviewClasses = [
        'pending' => 'bg-amber-100 text-amber-700',
        'approved' => 'bg-accent-100 text-accent-800',
        'blocked' => 'bg-danger-100 text-danger-700',
    ];
    $preferredStatusLabels = [
        'requested' => 'Requested',
        'unavailable' => 'Unavailable',
        'assigned' => 'Preferred Cleaner Assigned',
        'alternate_assigned' => 'Alternate Cleaner Assigned',
    ];
    $preferredStatusClasses = [
        'requested' => 'bg-accent-50 text-accent-700',
        'unavailable' => 'bg-amber-100 text-amber-700',
        'assigned' => 'bg-accent-100 text-accent-800',
        'alternate_assigned' => 'bg-slate-100 text-slate-600',
    ];
    $paymentStatusClasses = [
        'pending' => 'bg-amber-100 text-amber-700',
        'paid' => 'bg-accent-100 text-accent-800',
    ];
    $presentStaffCount = $staffList->where('is_present', true)->count();
    $activeTab = $tab === 'completed' ? 'completed' : 'active';
    $activeTabUrl = route('admin.bookings', array_merge(request()->except(['tab', 'active_page', 'completed_page']), ['tab' => 'active']));
    $completedTabUrl = route('admin.bookings', array_merge(request()->except(['tab', 'active_page', 'completed_page']), ['tab' => 'completed']));
    $filterLabels = [
        '' => 'All Active',
        'today' => 'Today',
        'unassigned' => 'Unassigned',
        'overdue' => 'Overdue',
        'review' => 'Manual Review',
        'in_progress' => 'In Progress',
    ];
@endphp

<div class="admin-page-content cleanflow-page-shell space-y-6 p-6">
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

    <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8">
        <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl">
                <span class="cleanflow-kicker">
                    <i class="fas fa-calendar-days"></i>
                    Operations Queue
                </span>
                <h2 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Keep the booking pipeline moving without digging through noisy tables.</h2>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-white/82 sm:text-base">
                    Review active service requests, assign available cleaners, clear manual review flags, and track completed work from one calmer dispatch view.
                </p>
            </div>
            <div class="grid gap-3 rounded-3xl border border-white/18 bg-white/10 px-5 py-4 shadow-[0_18px_40px_rgba(15,23,42,0.15)] backdrop-blur sm:grid-cols-2 xl:min-w-[320px]">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Active Queue</div>
                    <div class="mt-2 text-4xl font-black leading-none">{{ number_format($queueCounts['active']) }}</div>
                    <div class="mt-2 text-sm text-white/72">{{ number_format($queueCounts['today']) }} scheduled today</div>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Cleaners Present</div>
                    <div class="mt-2 text-4xl font-black leading-none">{{ number_format($presentStaffCount) }}</div>
                    <div class="mt-2 text-sm text-white/72">{{ number_format($queueCounts['review_pending']) }} bookings awaiting review</div>
                </div>
            </div>
        </div>
    </section>

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Total Bookings</div>
                    <div class="mt-2 text-3xl font-bold text-slate-800">{{ number_format($stats['total']) }}</div>
                    <div class="mt-2 text-sm text-slate-500">All service requests currently stored in the system.</div>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-secondary-50 text-secondary-700">
                    <i class="fas fa-calendar-days"></i>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Pending</div>
                    <div class="mt-2 text-3xl font-bold text-slate-800">{{ number_format($stats['pending']) }}</div>
                    <div class="mt-2 text-sm text-slate-500">
                        Requests waiting for confirmation or staffing.
                        @if(($pendingEscalationSummary['warning'] + $pendingEscalationSummary['critical']) > 0)
                            <span class="block pt-1 text-amber-600">
                                {{ $pendingEscalationSummary['critical'] }} critical &bull; {{ $pendingEscalationSummary['warning'] }} warning
                            </span>
                        @endif
                    </div>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-700">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Confirmed</div>
                    <div class="mt-2 text-3xl font-bold text-slate-800">{{ number_format($stats['confirmed']) }}</div>
                    <div class="mt-2 text-sm text-slate-500">Bookings approved and ready for dispatch planning.</div>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-accent-50 text-accent-700">
                    <i class="fas fa-circle-check"></i>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Completed</div>
                    <div class="mt-2 text-3xl font-bold text-slate-800">{{ number_format($stats['completed']) }}</div>
                    <div class="mt-2 text-sm text-slate-500">Closed jobs that are ready for reporting and review.</div>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-accent-100 text-accent-800">
                    <i class="fas fa-check-double"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="text-lg font-bold text-slate-900">Booking Workflow</div>
                <div class="mt-1 text-sm text-slate-500">Switch between the live operations queue and the completed-booking history without leaving this workspace.</div>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ $activeTabUrl }}" class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold {{ $activeTab === 'active' ? 'border-accent-200 bg-accent-50 text-accent-700' : 'border-slate-200 bg-slate-50 text-slate-600' }}">
                    <span>Active Bookings</span>
                    <span class="rounded-full bg-white px-2 py-0.5 text-xs">{{ number_format($queueCounts['active']) }}</span>
                </a>
                <a href="{{ $completedTabUrl }}" class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold {{ $activeTab === 'completed' ? 'border-accent-300 bg-accent-100 text-accent-800' : 'border-slate-200 bg-slate-50 text-slate-600' }}">
                    <span>Completed Bookings</span>
                    <span class="rounded-full bg-white px-2 py-0.5 text-xs">{{ number_format($queueCounts['completed']) }}</span>
                </a>
            </div>
        </div>
        @if($activeTab === 'active')
            <div class="mt-4 flex flex-wrap gap-2 border-t border-slate-100 pt-4">
                @foreach($filterLabels as $filterValue => $filterLabel)
                    <a href="{{ route('admin.bookings', array_merge(request()->except(['active_page', 'completed_page', 'filter']), ['tab' => 'active'], $filterValue === '' ? [] : ['filter' => $filterValue])) }}"
                       class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition {{ $activeFilter === $filterValue ? 'border-primary-200 bg-primary-50 text-primary-700' : 'border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100' }}">
                        {{ $filterLabel }}
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if($activeTab === 'active')
        <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
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
                <div class="overflow-x-auto">
                    <table class="min-w-[1100px] w-full border-separate border-spacing-0 text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                                <th class="px-6 py-3">Booking</th>
                                <th class="px-6 py-3">Customer</th>
                                <th class="px-6 py-3">Service</th>
                                <th class="px-6 py-3">Schedule</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3">Staff Assignment</th>
                                <th class="px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activeBookings as $booking)
                                @php
                                    $allowedStatuses = $booking->allowedTransitions();
                                    $scheduledDate = \Carbon\Carbon::parse($booking->scheduled_date);
                                    $reviewLocked = in_array($booking->manual_review_status, ['pending', 'blocked'], true);
                                    $requestedCleaner = $booking->preferredStaff;
                                    $scheduleMeta = match (true) {
                                        $scheduledDate->isToday() => ['label' => 'Today', 'class' => 'bg-accent-50 text-accent-700'],
                                        $scheduledDate->isPast() => ['label' => 'Overdue', 'class' => 'bg-danger-50 text-danger-700'],
                                        $scheduledDate->isTomorrow() => ['label' => 'Tomorrow', 'class' => 'bg-primary-50 text-primary-700'],
                                        default => ['label' => 'Upcoming', 'class' => 'bg-slate-100 text-slate-600'],
                                    };
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
                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ \App\Models\Booking::paymentMethodLabel($booking->payment_method) }}
                                            @if($booking->payment_reference)
                                            &bull; Ref {{ $booking->payment_reference }}
                                            @endif
                                        </div>
                                    </td>
                                    <td class="border-t border-slate-100 px-6 py-4 align-top">
                                        <div class="font-semibold text-slate-900">{{ $scheduledDate->format('M d, Y') }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $booking->scheduled_time }}</div>
                                        <span class="mt-3 inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $scheduleMeta['class'] }}">{{ $scheduleMeta['label'] }}</span>
                                    </td>
                                    <td class="border-t border-slate-100 px-6 py-4 align-top">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$booking->status] ?? 'bg-slate-100 text-slate-600' }}">
                                            {{ $statusLabels[$booking->status] ?? ucfirst(str_replace('_', ' ', $booking->status)) }}
                                        </span>
                                        @if($booking->pending_escalation)
                                            <div class="mt-2">
                                                <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold {{ $booking->pending_escalation['class'] }}">
                                                    {{ $booking->pending_escalation['label'] }} &bull; {{ $booking->pending_escalation['age_label'] }}
                                                </span>
                                            </div>
                                        @endif
                                        <div class="mt-2">
                                            <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold {{ $paymentStatusClasses[$booking->payment_status] ?? 'bg-slate-100 text-slate-600' }}">
                                                {{ \App\Models\Booking::paymentStatusLabel($booking->payment_status) }}
                                            </span>
                                        </div>
                                        @if(isset($reviewLabels[$booking->manual_review_status]))
                                            <div class="mt-2">
                                                <span class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold {{ $reviewClasses[$booking->manual_review_status] }}">
                                                    {{ $reviewLabels[$booking->manual_review_status] }}
                                                </span>
                                            </div>
                                        @endif
                                        @if(! empty($booking->risk_reasons))
                                            <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-[11px] leading-5 text-amber-800">
                                                <div class="font-semibold uppercase tracking-[0.14em]">Risk signals</div>
                                                @foreach($booking->risk_reasons as $reason)
                                                    <div class="mt-1">{{ $reason }}</div>
                                                @endforeach
                                            </div>
                                        @endif
                                        @if($booking->reviewed_at)
                                            <div class="mt-2 text-[11px] text-slate-500">
                                                Reviewed {{ $booking->reviewed_at->diffForHumans() }}
                                                @if($booking->reviewedBy)
                                                    by {{ $booking->reviewedBy->display_name }}
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="border-t border-slate-100 px-6 py-4 align-top">
                                        @if($requestedCleaner)
                                            <div class="mb-3 rounded-xl border border-accent-100 bg-accent-50 px-3 py-2">
                                                <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-accent-700">Preferred cleaner</div>
                                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ $requestedCleaner->display_name }}</div>
                                                @if(isset($preferredStatusLabels[$booking->preferred_staff_status]))
                                                    <div class="mt-2">
                                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $preferredStatusClasses[$booking->preferred_staff_status] }}">
                                                            {{ $preferredStatusLabels[$booking->preferred_staff_status] }}
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                        <div class="mb-2 text-xs text-slate-500">
                                            Assigned cleaner:
                                            <span class="font-bold text-slate-900">
                                                {{ $booking->staff?->display_name ?? 'Unassigned' }}
                                            </span>
                                        </div>
                                        <form action="{{ route('admin.bookings.status', $booking->id) }}" method="POST" class="space-y-2">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $booking->status }}">
                                            <select name="staff_id" {{ $reviewLocked ? 'disabled' : '' }} class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-primary-500 focus:outline-hidden {{ $reviewLocked ? 'bg-slate-100 text-slate-400' : '' }}">
                                                @if($booking->status === 'pending')
                                                    <option value="">Unassigned</option>
                                                @endif
                                                @foreach($staffList as $staff)
                                                    @php
                                                        $staffBusyForSlot = in_array($staff->id, $booking->busy_staff_ids ?? [], true);
                                                    @endphp
                                                    @if($staff->is_present && ! $staffBusyForSlot)
                                                        <option value="{{ $staff->id }}" {{ $booking->staff_id === $staff->id ? 'selected' : '' }}>
                                                            {{ $staff->display_name }}{{ $booking->preferred_staff_id === $staff->id ? ' (Requested)' : '' }}
                                                        </option>
                                                    @elseif($staff->is_present && $staffBusyForSlot)
                                                        <option value="{{ $staff->id }}" disabled>
                                                            {{ $staff->display_name }}{{ $booking->preferred_staff_id === $staff->id ? ' (Requested, Busy at this time)' : ' (Busy at this time)' }}
                                                        </option>
                                                    @elseif($booking->staff_id === $staff->id)
                                                        <option value="{{ $staff->id }}" selected>
                                                            {{ $staff->display_name }}{{ $booking->preferred_staff_id === $staff->id ? ' (Requested, Absent)' : ' (Absent)' }}
                                                    </option>
                                                @endif
                                            @endforeach
                                            </select>
                                            <button type="submit" {{ $reviewLocked ? 'disabled' : '' }} class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-3 py-2 text-[11px] font-bold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:bg-slate-300">
                                                <i class="fas fa-save"></i>
                                                Save Staff
                                            </button>
                                            @if($booking->manual_review_status === 'pending')
                                                <div class="text-xs text-amber-700">Approve or block the manual review before assigning a cleaner.</div>
                                            @elseif($booking->manual_review_status === 'blocked')
                                                <div class="text-xs text-red-700">Blocked bookings stay out of the staffing queue.</div>
                                            @elseif($presentStaffCount === 0)
                                                <div class="text-xs text-red-700">No cleaners are marked present for today's operations.</div>
                                            @elseif(($booking->available_present_staff_count ?? 0) === 0 && ! $booking->staff_id)
                                                <div class="text-xs text-amber-700">All available cleaners are already booked during this time slot.</div>
                                            @endif
                                        </form>
                                    </td>
                                    <td class="border-t border-slate-100 px-6 py-4 align-top">
                                        <div class="flex flex-col items-start gap-2">
                                            <a href="{{ route('bookings.show', $booking->id) }}" class="inline-flex items-center gap-2 rounded-xl border border-secondary-200 bg-secondary-50 px-3 py-2 text-xs font-bold text-secondary-700">
                                                <i class="fas fa-eye"></i>
                                                View Booking
                                            </a>
                                            @if($booking->manual_review_status === 'pending')
                                                <div class="w-full rounded-xl border border-amber-200 bg-amber-50 p-3">
                                                    <div class="text-[11px] font-bold uppercase tracking-[0.14em] text-amber-800">Manual review required</div>
                                                    <div class="mt-2 flex flex-wrap gap-2">
                                                        <form action="{{ route('admin.bookings.review', $booking->id) }}" method="POST">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="review_status" value="approved">
                                                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-accent-600 px-3 py-2 text-[11px] font-bold text-white">
                                                                <i class="fas fa-circle-check"></i>
                                                                Approve
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('admin.bookings.review', $booking->id) }}" method="POST">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="review_status" value="blocked">
                                                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-3 py-2 text-[11px] font-bold text-white">
                                                                <i class="fas fa-ban"></i>
                                                                Block
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endif
                                            <form action="{{ route('admin.bookings.status', $booking->id) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <div class="flex flex-wrap gap-2">
                                                <select name="status" {{ $reviewLocked ? 'disabled' : '' }} class="rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-primary-500 focus:outline-hidden {{ $reviewLocked ? 'bg-slate-100 text-slate-400' : '' }}">
                                                    @foreach($allowedStatuses as $statusOption)
                                                        <option value="{{ $statusOption }}" {{ $booking->status === $statusOption ? 'selected' : '' }}>
                                                            {{ $statusLabels[$statusOption] ?? ucfirst(str_replace('_', ' ', $statusOption)) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" {{ $reviewLocked ? 'disabled' : '' }} class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-3 py-2 text-[11px] font-bold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:bg-slate-300">Save</button>
                                                </div>
                                            </form>
                                            <form action="{{ route('admin.bookings.payment', $booking->id) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <div class="flex flex-wrap gap-2">
                                                <select name="payment_status" class="rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-primary-500 focus:outline-hidden">
                                                    @foreach(\App\Models\Booking::paymentStatuses() as $paymentStatusOption)
                                                        <option value="{{ $paymentStatusOption }}" {{ $booking->payment_status === $paymentStatusOption ? 'selected' : '' }}>
                                                            {{ \App\Models\Booking::paymentStatusLabel($paymentStatusOption) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-3 py-2 text-[11px] font-bold text-white transition hover:bg-slate-700">Save</button>
                                                </div>
                                            </form>
                                            @if($booking->status === 'in_progress' && !is_null($booking->current_latitude) && !is_null($booking->current_longitude))
                                                <a href="{{ route('bookings.show', $booking->id) }}" class="inline-flex items-center gap-2 rounded-xl border border-accent-200 bg-accent-50 px-3 py-2 text-[11px] font-bold text-accent-700">
                                                    <i class="fas fa-location-dot"></i>
                                                    Live Location
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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
