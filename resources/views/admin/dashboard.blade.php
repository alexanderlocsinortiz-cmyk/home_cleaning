@extends('layouts.admin')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Today: ' . number_format($dashboardStats['total_bookings']) . ' bookings | ' . number_format($dashboardStats['pending_bookings']) . ' pending | ' . number_format($dashboardStats['in_progress_bookings']) . ' in progress | ' . number_format($dashboardStats['staff']) . ' staff')

@section('content')
@php
    $criticalEscalations = (int) ($pendingEscalationSummary['critical'] ?? 0);
    $warningEscalations = (int) ($pendingEscalationSummary['warning'] ?? 0);
    $overduePending = $criticalEscalations + $warningEscalations;
    $completionRate = $dashboardStats['total_bookings'] > 0
        ? round(($dashboardStats['completed_bookings'] / $dashboardStats['total_bookings']) * 100, 1)
        : 0;

    $operationsKpis = [
        [
            'label' => 'Pending',
            'value' => number_format($dashboardStats['pending_bookings']),
            'description' => 'Awaiting review or assignment',
            'href' => route('admin.bookings', ['tab' => 'active']),
            'tone' => $dashboardStats['pending_bookings'] > 0 ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-slate-200 bg-white text-slate-800',
        ],
        [
            'label' => 'Unassigned',
            'value' => number_format($unassignedBookings),
            'description' => 'Need cleaner assignment',
            'href' => route('admin.bookings', ['tab' => 'active', 'filter' => 'unassigned']),
            'tone' => $unassignedBookings > 0 ? 'border-blue-200 bg-blue-50 text-blue-800' : 'border-slate-200 bg-white text-slate-800',
        ],
        [
            'label' => 'In Progress',
            'value' => number_format($dashboardStats['in_progress_bookings']),
            'description' => 'Active services now',
            'href' => route('admin.bookings', ['tab' => 'active', 'filter' => 'in_progress']),
            'tone' => 'border-slate-200 bg-white text-slate-800',
        ],
        [
            'label' => 'Completed',
            'value' => number_format($dashboardStats['completed_bookings']),
            'description' => number_format($completionRate, 1) . '% completion rate',
            'href' => route('admin.reports'),
            'tone' => 'border-slate-200 bg-white text-slate-800',
        ],
        [
            'label' => 'Staff Present',
            'value' => number_format($presentStaffCount) . ' / ' . number_format($dashboardStats['staff']),
            'description' => 'Checked in today',
            'href' => route('admin.attendance'),
            'tone' => 'border-slate-200 bg-white text-slate-800',
        ],
    ];

    $quickActions = [
        [
            'route' => route('admin.bookings', ['tab' => 'active']),
            'title' => 'Manage Bookings',
            'description' => 'Review queue and update statuses',
            'icon' => 'fa-calendar-check',
        ],
        [
            'route' => route('admin.bookings', ['tab' => 'active', 'filter' => 'unassigned']),
            'title' => 'Assign Staff',
            'description' => 'Handle unassigned active jobs',
            'icon' => 'fa-user-check',
        ],
        [
            'route' => route('admin.staff.index'),
            'title' => 'Staff Readiness',
            'description' => 'Check coverage and attendance',
            'icon' => 'fa-user-gear',
        ],
        [
            'route' => route('admin.reports'),
            'title' => 'View Reports',
            'description' => 'Revenue and demand trends',
            'icon' => 'fa-chart-column',
        ],
    ];

    $statusClasses = [
        'pending'     => 'bg-amber-100 text-amber-700',
        'confirmed'   => 'bg-accent-50 text-accent-700',
        'in_progress' => 'bg-primary-100 text-primary-700',
        'completed'   => 'bg-accent-100 text-accent-800',
        'cancelled'   => 'bg-danger-100 text-danger-700',
    ];

    $rankClasses = [
        0 => 'bg-primary-600 text-white',
        1 => 'bg-slate-200 text-slate-700',
        2 => 'bg-slate-100 text-slate-600',
    ];
@endphp

<div class="admin-page-content max-w-7xl space-y-5">
    @if($overduePending > 0)
    <section class="rounded-xl border {{ $criticalEscalations > 0 ? 'border-red-200 bg-red-50 text-red-800' : 'border-amber-200 bg-amber-50 text-amber-800' }} px-5 py-4 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <div class="text-xs font-bold uppercase tracking-wide">{{ $criticalEscalations > 0 ? 'Critical' : 'Attention Needed' }}</div>
                <div class="mt-1 text-base font-bold">
                    {{ number_format($overduePending) }} pending booking{{ $overduePending === 1 ? '' : 's' }} need assignment.
                </div>
                <div class="mt-1 text-sm opacity-80">
                    {{ number_format($criticalEscalations) }} critical over 7 days, {{ number_format($warningEscalations) }} warning over 24 hours.
                </div>
            </div>
            <a href="{{ route('admin.bookings', ['tab' => 'active']) }}" class="inline-flex w-fit items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">
                <i class="fas fa-arrow-right"></i>
                Assign now
            </a>
        </div>
    </section>
    @endif

    <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
        @foreach($operationsKpis as $card)
        <a href="{{ $card['href'] }}" class="rounded-xl border px-4 py-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $card['tone'] }}">
            <div class="text-sm font-semibold">{{ $card['label'] }}</div>
            <div class="mt-3 text-3xl font-bold leading-none">{{ $card['value'] }}</div>
            <div class="mt-2 text-sm opacity-70">{{ $card['description'] }}</div>
        </a>
        @endforeach
    </section>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Active Queue - Recent Bookings</h2>
                    <p class="mt-1 text-sm text-slate-500">Pending, confirmed, and in-progress requests that need operational attention.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.bookings', ['tab' => 'active']) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-primary-300 hover:text-primary-700">Active</a>
                    <a href="{{ route('admin.bookings', ['tab' => 'active', 'filter' => 'unassigned']) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-primary-300 hover:text-primary-700">Unassigned</a>
                    <a href="{{ route('admin.bookings', ['tab' => 'active']) }}" class="rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-primary-700">Open Queue</a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                            <th class="border-b border-slate-100 px-5 py-3">Booking</th>
                            <th class="border-b border-slate-100 px-5 py-3">Customer</th>
                            <th class="border-b border-slate-100 px-5 py-3">Service</th>
                            <th class="border-b border-slate-100 px-5 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentBookings as $booking)
                        <tr class="cursor-pointer transition hover:bg-slate-50" onclick="window.location='{{ route('bookings.show', $booking->id) }}'">
                            <td class="px-5 py-4 align-top">
                                <a href="{{ route('bookings.show', $booking->id) }}" class="block font-mono text-sm font-semibold text-primary-700 transition hover:text-primary-800" onclick="event.stopPropagation()">
                                    CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}
                                </a>
                                <div class="mt-1 text-xs text-slate-400">{{ optional($booking->created_at)->diffForHumans() }}</div>
                            </td>
                            <td class="px-5 py-4 align-top">
                                <div class="font-medium text-slate-800">{{ $booking->user?->display_name ?? 'Unknown customer' }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ $booking->barangay ?: 'Barangay not set' }}</div>
                            </td>
                            <td class="px-5 py-4 align-top">
                                <div class="text-slate-700">{{ $booking->service_label }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ \Carbon\Carbon::parse($booking->scheduled_date)->format('M d, Y') }}</div>
                            </td>
                            <td class="px-5 py-4 align-top">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $statusClasses[$booking->status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ ucwords(str_replace('_', ' ', $booking->status)) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center">
                                <div class="text-sm font-semibold text-slate-600">No active bookings yet</div>
                                <div class="mt-1 text-xs text-slate-400">New pending, confirmed, and in-progress requests will appear here.</div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="space-y-5">
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-800">Quick Actions</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                    @foreach($quickActions as $action)
                    <a href="{{ $action['route'] }}" class="flex items-start gap-3 rounded-lg border border-slate-200 px-4 py-3 transition hover:border-primary-300 hover:bg-primary-50">
                        <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
                            <i class="fas {{ $action['icon'] }}"></i>
                        </span>
                        <span>
                            <span class="block text-sm font-semibold text-slate-800">{{ $action['title'] }}</span>
                            <span class="mt-1 block text-xs leading-relaxed text-slate-500">{{ $action['description'] }}</span>
                        </span>
                    </a>
                    @endforeach
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-800">Today Snapshot</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Tomorrow jobs</dt>
                        <dd class="font-semibold text-slate-800">{{ number_format($tomorrowJobs) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Revenue to date</dt>
                        <dd class="font-semibold text-slate-800">&#8369;{{ number_format($totalEarnings, 0) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-slate-500">Updated</dt>
                        <dd class="font-semibold text-slate-800">{{ $dashboardNow->format('h:i A') }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-slate-800">Staff Trends</h2>
                <div class="mt-4 space-y-1">
                    @forelse($topStaff as $index => $member)
                    <div class="flex items-center gap-3 border-b border-slate-100 py-3 last:border-0">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ $rankClasses[$index] ?? 'bg-slate-100 text-slate-600' }}">
                            {{ $index + 1 }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-semibold text-slate-800">{{ $member->display_name }}</div>
                            <div class="mt-1 text-xs text-slate-400">
                                {{ $member->current_month_completed }} completed this month
                            </div>
                        </div>
                        <div class="text-right text-xs font-semibold {{ $member->trend_change < 0 ? 'text-red-600' : 'text-emerald-600' }}">
                            {{ $member->trend_change >= 0 ? '+' : '' }}{{ $member->trend_change }}
                        </div>
                    </div>
                    @empty
                    <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-center text-sm text-slate-500">
                        No staff completions recorded for {{ $dashboardNow->format('F Y') }} yet.
                    </div>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
