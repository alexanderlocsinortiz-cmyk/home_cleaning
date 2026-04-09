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
        'confirmed' => 'bg-emerald-100 text-emerald-700',
        'in_progress' => 'bg-purple-100 text-purple-700',
        'completed' => 'bg-emerald-100 text-emerald-700',
        'cancelled' => 'bg-red-100 text-red-600',
    ];
    $presentStaffCount = $staffList->where('is_present', true)->count();
    $activeTab = $tab === 'completed' ? 'completed' : 'active';
    $activeTabUrl = route('admin.bookings', array_merge(request()->except(['tab', 'active_page', 'completed_page']), ['tab' => 'active']));
    $completedTabUrl = route('admin.bookings', array_merge(request()->except(['tab', 'active_page', 'completed_page']), ['tab' => 'completed']));
@endphp

<div class="space-y-6" style="padding: 1.5rem 2rem; font-family: 'DM Sans', sans-serif;">
    @if(session('success'))
        <div class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
            <i class="fas fa-check-circle mt-0.5"></i>
            <div>
                <div class="text-sm font-bold">Action completed</div>
                <div class="text-sm">{{ session('success') }}</div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
            <i class="fas fa-exclamation-triangle mt-0.5"></i>
            <div>
                <div class="text-sm font-bold">Action blocked</div>
                <div class="text-sm">{{ session('error') }}</div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
            <div class="text-sm font-bold">Please review the booking update form.</div>
            <div class="mt-1 text-sm">One or more values need attention before the change can be saved.</div>
            <div class="mt-3 space-y-1 text-sm">
                @foreach($errors->all() as $error)
                    <div>&bull; {{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Total Bookings</div>
                    <div class="mt-2 text-3xl font-bold text-slate-800">{{ number_format($stats['total']) }}</div>
                    <div class="mt-2 text-sm text-slate-500">All service requests currently stored in the system.</div>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">
                    <i class="fas fa-calendar-days"></i>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Pending</div>
                    <div class="mt-2 text-3xl font-bold text-slate-800">{{ number_format($stats['pending']) }}</div>
                    <div class="mt-2 text-sm text-slate-500">Requests waiting for confirmation or staffing.</div>
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
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
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
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                    <i class="fas fa-check-double"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="text-lg font-bold text-slate-900">Booking Workflow</div>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ $activeTabUrl }}" class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold {{ $activeTab === 'active' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-600' }}">
                    <span>Active Bookings</span>
                    <span class="rounded-full bg-white px-2 py-0.5 text-xs">{{ number_format($queueCounts['active']) }}</span>
                </a>
                <a href="{{ $completedTabUrl }}" class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-semibold {{ $activeTab === 'completed' ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-slate-200 bg-slate-50 text-slate-600' }}">
                    <span>Completed Bookings</span>
                    <span class="rounded-full bg-white px-2 py-0.5 text-xs">{{ number_format($queueCounts['completed']) }}</span>
                </a>
            </div>
        </div>
    </div>

    @if($activeTab === 'active')
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                <div>
                    <div class="text-lg font-bold text-slate-900">Active Booking Queue</div>
                    <div class="mt-1 text-sm text-slate-500">Pending, confirmed, and in-progress work that still needs operational attention.</div>
                </div>
                <div class="text-right text-xs leading-6 text-slate-400">
                    <div>{{ number_format($queueCounts['today']) }} scheduled today</div>
                    <div>{{ number_format($queueCounts['upcoming']) }} upcoming • {{ number_format($queueCounts['in_progress']) }} in progress</div>
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
                                    $scheduleMeta = match (true) {
                                        $scheduledDate->isToday() => ['label' => 'Today', 'class' => 'bg-emerald-50 text-emerald-700'],
                                        $scheduledDate->isPast() => ['label' => 'Overdue', 'class' => 'bg-red-50 text-red-600'],
                                        $scheduledDate->isTomorrow() => ['label' => 'Tomorrow', 'class' => 'bg-blue-50 text-blue-700'],
                                        default => ['label' => 'Upcoming', 'class' => 'bg-slate-100 text-slate-600'],
                                    };
                                @endphp
                                <tr class="transition hover:bg-slate-50">
                                    <td class="border-t border-slate-100 px-6 py-4 align-top">
                                        <div class="font-mono text-sm font-bold text-blue-700">CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</div>
                                        <div class="mt-1 text-xs text-slate-400">{{ optional($booking->created_at)->diffForHumans() }}</div>
                                    </td>
                                    <td class="border-t border-slate-100 px-6 py-4 align-top">
                                        <div class="font-semibold text-slate-900">{{ $booking->user->first_name }} {{ $booking->user->last_name }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $booking->user->email }}</div>
                                    </td>
                                    <td class="border-t border-slate-100 px-6 py-4 align-top">
                                        <div class="font-semibold text-slate-900">{{ $booking->service_label }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $booking->street_address }}, {{ $booking->barangay }}</div>
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
                                    </td>
                                    <td class="border-t border-slate-100 px-6 py-4 align-top">
                                        <div class="mb-2 text-xs text-slate-500">
                                            Current staff:
                                            <span class="font-bold text-slate-900">
                                                {{ $booking->staff ? $booking->staff->first_name . ' ' . $booking->staff->last_name : 'Unassigned' }}
                                            </span>
                                        </div>
                                        <form action="{{ route('admin.bookings.status', $booking->id) }}" method="POST" class="space-y-2">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $booking->status }}">
                                            <select name="staff_id" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
                                                @if($booking->status === 'pending')
                                                    <option value="">Unassigned</option>
                                                @endif
                                                @foreach($staffList as $staff)
                                                    @if($staff->is_present)
                                                        <option value="{{ $staff->id }}" {{ $booking->staff_id === $staff->id ? 'selected' : '' }}>
                                                            {{ $staff->first_name }} {{ $staff->last_name }}
                                                        </option>
                                                    @elseif($booking->staff_id === $staff->id)
                                                        <option value="{{ $staff->id }}" selected>
                                                            {{ $staff->first_name }} {{ $staff->last_name }} (Absent)
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            @if($presentStaffCount === 0)
                                                <div class="text-xs text-red-700">No staff members are marked present today.</div>
                                            @endif
                                        </form>
                                    </td>
                                    <td class="border-t border-slate-100 px-6 py-4 align-top">
                                        <div class="flex flex-col items-start gap-2">
                                            <a href="{{ route('bookings.show', $booking->id) }}" class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700">
                                                <i class="fas fa-eye"></i>
                                                View Booking
                                            </a>
                                            <form action="{{ route('admin.bookings.status', $booking->id) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" onchange="this.form.submit()" class="rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-emerald-500 focus:outline-none">
                                                    @foreach($allowedStatuses as $statusOption)
                                                        <option value="{{ $statusOption }}" {{ $booking->status === $statusOption ? 'selected' : '' }}>
                                                            {{ $statusLabels[$statusOption] ?? ucfirst(str_replace('_', ' ', $statusOption)) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </form>
                                            @if($booking->status === 'in_progress' && !is_null($booking->current_latitude) && !is_null($booking->current_longitude))
                                                <a href="{{ route('bookings.show', $booking->id) }}" class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] font-bold text-emerald-700">
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
                    <div class="mt-4 text-lg font-bold text-slate-900">No active bookings in queue</div>
                    <div class="mx-auto mt-2 max-w-md text-sm text-slate-500">Pending, confirmed, and in-progress bookings will appear here once operational work needs attention.</div>
                </div>
            @endif
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
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
                                <th class="px-6 py-3">Assigned Staff</th>
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
                                        <div class="font-semibold text-slate-900">{{ $booking->user->first_name }} {{ $booking->user->last_name }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $booking->user->email }}</div>
                                    </td>
                                    <td class="border-t border-slate-100 px-6 py-4 align-top">
                                        <div class="font-semibold text-slate-900">{{ $booking->service_label }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $booking->street_address }}, {{ $booking->barangay }}</div>
                                    </td>
                                    <td class="border-t border-slate-100 px-6 py-4 align-top">
                                        <div class="font-semibold text-slate-900">{{ $booking->staff ? $booking->staff->first_name . ' ' . $booking->staff->last_name : 'Unassigned' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $booking->staff ? 'Assigned staff record' : 'No staff assigned' }}</div>
                                    </td>
                                    <td class="border-t border-slate-100 px-6 py-4 align-top">
                                        <div class="font-semibold text-slate-900">{{ $closedAt ? $closedAt->format('M d, Y') : 'Not available' }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $closedAt ? $closedAt->format('h:i A') : '' }}</div>
                                        <span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$booking->status] ?? 'bg-slate-100 text-slate-600' }}">
                                            {{ $statusLabels[$booking->status] ?? ucfirst(str_replace('_', ' ', $booking->status)) }}
                                        </span>
                                    </td>
                                    <td class="border-t border-slate-100 px-6 py-4 align-top">
                                        <div class="font-semibold text-slate-900">&#8369;{{ number_format($booking->price, 2) }}</div>
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
                                        <a href="{{ route('bookings.show', $booking->id) }}" class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700">
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
                    <div class="mt-4 text-lg font-bold text-slate-900">No completed history yet</div>
                    <div class="mx-auto mt-2 max-w-md text-sm text-slate-500">Completed services and other closed booking records will appear here after operational work is finalized.</div>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection
