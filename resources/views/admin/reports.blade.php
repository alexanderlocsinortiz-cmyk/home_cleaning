@extends('layouts.admin')
@section('title', 'Reports & Analytics')
@section('page-title', 'Reports & Analytics')
@section('page-subtitle', 'Performance, revenue, and activity overview')

@section('content')
@php
    $serviceBookingsTotal = $bookingsByType->sum('total');
    $maxMonthlyBookings = max(1, (int) $monthlyBookingTrend->max('total'));
    $maxTimeSlotDemand = max(1, (int) $timeSlotTrends->max('total'));
    $maxWeekdayDemand = max(1, (int) $weekdayTrends->max('total'));
    $maxReviewCount = max(1, (int) $satisfactionTrend->max('reviews'));
    $maxServiceRevenue = max(1, (float) $revenueByType->max('revenue'));
    $bookingGrowth = $analyticsOverview['booking_growth'];

    $overviewCards = [
        [
            'label' => 'Total Bookings',
            'value' => number_format($totalBookings),
            'description' => 'All recorded service requests',
            'accent' => 'border-l-slate-400',
            'icon' => 'fa-calendar-days',
            'iconWrap' => 'bg-slate-50 text-slate-500',
        ],
        [
            'label' => 'Completed',
            'value' => number_format($completedBookings),
            'description' => 'Finished service visits',
            'accent' => 'border-l-emerald-500',
            'icon' => 'fa-circle-check',
            'iconWrap' => 'bg-emerald-50 text-emerald-600',
        ],
        [
            'label' => 'Pending',
            'value' => number_format($pendingBookings),
            'description' => 'Awaiting action or staffing',
            'accent' => 'border-l-amber-400',
            'icon' => 'fa-clock',
            'iconWrap' => 'bg-amber-50 text-amber-600',
        ],
        [
            'label' => 'Cancelled',
            'value' => number_format($cancelledBookings),
            'description' => 'Closed without completion',
            'accent' => 'border-l-red-400',
            'icon' => 'fa-ban',
            'iconWrap' => 'bg-red-50 text-red-600',
        ],
        [
            'label' => 'Avg Completed Revenue',
            'value' => '&#8369;' . number_format($reportInsights['average_completed_revenue'], 0),
            'description' => 'Average revenue per completed visit',
            'accent' => 'border-l-cyan-500',
            'icon' => 'fa-receipt',
            'iconWrap' => 'bg-cyan-50 text-cyan-600',
        ],
        [
            'label' => 'Needs Action',
            'value' => number_format($reportInsights['pending_older_than_day'] + $reportInsights['unassigned_active_bookings']),
            'description' => 'Old pending plus unassigned active bookings',
            'accent' => 'border-l-orange-400',
            'icon' => 'fa-triangle-exclamation',
            'iconWrap' => 'bg-orange-50 text-orange-600',
        ],
    ];

    $statusBars = [
        ['label' => 'Completed', 'count' => $statusSummary['completed'], 'color' => 'bg-accent-600'],
        ['label' => 'Confirmed', 'count' => $statusSummary['confirmed'], 'color' => 'bg-accent-400'],
        ['label' => 'Pending', 'count' => $statusSummary['pending'], 'color' => 'bg-amber-500'],
        ['label' => 'In Progress', 'count' => $statusSummary['in_progress'], 'color' => 'bg-primary-500'],
        ['label' => 'Cancelled', 'count' => $statusSummary['cancelled'], 'color' => 'bg-danger-500'],
    ];

    $bookingGrowthLabel = $bookingGrowth === null
        ? 'No previous-month baseline yet'
        : (($bookingGrowth >= 0 ? '+' : '') . number_format($bookingGrowth, 1) . '% vs previous month');
@endphp

<div class="admin-page-content cleanflow-page-shell space-y-5 p-4 lg:p-6">
    @if($invalidServiceBookings > 0)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ $invalidServiceBookings }} booking {{ $invalidServiceBookings === 1 ? 'record was' : 'records were' }} excluded from service analytics because the stored service type no longer matches the active service catalog.
        </div>
    @endif

    <section class="rounded-[24px] border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_520px] xl:items-center">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500">
                    <i class="fas fa-chart-line text-accent-600"></i>
                    {{ $reportInsights['date_label'] }}
                </div>
                <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-900">Reports overview</h2>
                <p class="mt-1 max-w-2xl text-sm text-slate-500">
                    Booking demand, revenue, service mix, staff performance, and customer satisfaction in one operational view.
                </p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                    <div class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Total Revenue</div>
                    <div class="mt-2 text-3xl font-black leading-none text-slate-900">&#8369;{{ number_format($totalRevenue, 0) }}</div>
                    <div class="mt-1 text-xs text-slate-500">Completed booking revenue</div>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                    <div class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Current Growth</div>
                    <div class="mt-2 text-3xl font-black leading-none text-slate-900">{{ $analyticsOverview['current_month_bookings'] }}</div>
                    <div class="mt-1 text-xs {{ $bookingGrowth !== null && $bookingGrowth < 0 ? 'text-red-500' : 'text-emerald-600' }}">{{ $bookingGrowthLabel }}</div>
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-[28px] border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.reports') }}" class="flex flex-col gap-3 2xl:flex-row 2xl:items-end 2xl:justify-between">
            <div class="flex flex-wrap gap-2 2xl:flex-nowrap">
                @foreach(['all' => 'All Time', 'today' => 'Today', 'this_week' => 'This Week', 'this_month' => 'This Month', 'last_month' => 'Last Month'] as $period => $label)
                    <a href="{{ route('admin.reports', ['period' => $period]) }}"
                       class="whitespace-nowrap rounded-full border px-4 py-2 text-sm font-bold transition {{ $filters['period'] === $period ? 'border-accent-600 bg-accent-600 text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="grid gap-3 sm:grid-cols-[150px_150px_150px_auto] sm:items-end 2xl:shrink-0">
                <div>
                    <label for="period" class="mb-1 block text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">Mode</label>
                    <select id="period" name="period" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 outline-hidden transition focus:border-accent-500 focus:ring-4 focus:ring-accent-100">
                        <option value="custom" @selected($filters['period'] === 'custom')>Custom range</option>
                        <option value="all" @selected($filters['period'] === 'all')>All time</option>
                    </select>
                </div>
                <div>
                    <label for="date_from" class="mb-1 block text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">From</label>
                    <input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] }}" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 outline-hidden transition focus:border-accent-500 focus:ring-4 focus:ring-accent-100">
                </div>
                <div>
                    <label for="date_to" class="mb-1 block text-[11px] font-extrabold uppercase tracking-[0.14em] text-slate-500">To</label>
                    <input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] }}" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 outline-hidden transition focus:border-accent-500 focus:ring-4 focus:ring-accent-100">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-accent-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-accent-700">
                        <i class="fas fa-filter"></i>
                        Apply
                    </button>
                    <a href="{{ route('admin.reports') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-50">Clear</a>
                </div>
            </div>
        </form>
    </section>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
        @foreach($overviewCards as $card)
            <div class="rounded-2xl border border-slate-100 border-l-4 {{ $card['accent'] }} bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">{{ $card['label'] }}</div>
                        <div class="mt-2 text-2xl font-black leading-none text-slate-900">{!! $card['value'] !!}</div>
                        <div class="mt-1 text-xs leading-5 text-slate-500">{{ $card['description'] }}</div>
                    </div>
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $card['iconWrap'] }}">
                        <i class="fas {{ $card['icon'] }}"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <section class="cleanflow-panel p-4">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div>
                <h3 class="text-lg font-extrabold text-slate-900">Operating Signals</h3>
                <p class="mt-1 text-sm text-slate-500">The numbers an admin needs to act on first.</p>
            </div>
        </div>
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Current Month Growth</div>
            <div class="mt-3 text-3xl font-black leading-none text-slate-900">{{ $analyticsOverview['current_month_bookings'] }}</div>
            <div class="mt-2 text-sm {{ $bookingGrowth !== null && $bookingGrowth < 0 ? 'text-red-500' : 'text-emerald-600' }}">{{ $bookingGrowthLabel }}</div>
            <div class="mt-1 text-xs text-slate-400">Booking requests recorded this month</div>
        </div>

        <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Completion Rate</div>
            <div class="mt-3 text-3xl font-black leading-none text-slate-900">{{ number_format($analyticsOverview['completion_rate'], 1) }}%</div>
            <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-slate-100">
                <div class="admin-dashboard-progress-fill bg-emerald-500" data-fill-width="{{ min(100, max(0, $analyticsOverview['completion_rate'])) }}"></div>
            </div>
            <div class="mt-2 text-xs text-slate-400">Completed bookings compared with all booking records</div>
        </div>

        <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Average Satisfaction</div>
            <div class="mt-3 text-3xl font-black leading-none text-slate-900">{{ $analyticsOverview['average_satisfaction'] !== null ? number_format($analyticsOverview['average_satisfaction'], 1) : 'N/A' }}</div>
            <div class="mt-2 text-sm text-slate-500">{{ $analyticsOverview['total_reviews'] }} total review{{ $analyticsOverview['total_reviews'] === 1 ? '' : 's' }}</div>
            <div class="mt-1 text-xs text-slate-400">Average score from completed-booking ratings</div>
        </div>

        <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
            <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Busiest Booking Time</div>
            <div class="mt-3 text-2xl font-black leading-tight text-slate-900">{{ $analyticsOverview['peak_time_label'] ?? 'N/A' }}</div>
            <div class="mt-2 text-sm text-slate-500">{{ $analyticsOverview['peak_time_total'] }} booking{{ $analyticsOverview['peak_time_total'] === 1 ? '' : 's' }}</div>
            <div class="mt-1 text-xs text-slate-400">{{ $analyticsOverview['peak_day_label'] ?? 'No day trend yet' }}</div>
        </div>
        </div>
    </section>

    <div class="grid gap-3 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Cancellation Rate</div>
            <div class="mt-3 text-3xl font-black leading-none text-slate-900">{{ number_format($reportInsights['cancellation_rate'], 1) }}%</div>
            <div class="mt-2 text-sm text-slate-500">{{ number_format($cancelledBookings) }} cancelled out of {{ number_format($totalBookings) }} bookings</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Old Pending</div>
            <div class="mt-3 text-3xl font-black leading-none {{ $reportInsights['pending_older_than_day'] > 0 ? 'text-amber-600' : 'text-slate-900' }}">{{ number_format($reportInsights['pending_older_than_day']) }}</div>
            <div class="mt-2 text-sm text-slate-500">Pending bookings older than 24 hours</div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Unassigned Active</div>
            <div class="mt-3 text-3xl font-black leading-none {{ $reportInsights['unassigned_active_bookings'] > 0 ? 'text-red-600' : 'text-slate-900' }}">{{ number_format($reportInsights['unassigned_active_bookings']) }}</div>
            <div class="mt-2 text-sm text-slate-500">Pending, confirmed, or in-progress bookings without staff</div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
        <section class="cleanflow-panel overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-lg font-extrabold text-slate-900">Booking Trends</h3>
                <p class="mt-1 text-sm text-slate-500">A six-month view of booking demand, time-slot volume, and the busiest operating days.</p>
            </div>

            <div class="grid gap-5 px-5 py-5 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
                <div>
                    <div class="mb-3 text-xs font-extrabold uppercase tracking-[0.16em] text-slate-400">Last 6 Months</div>
                    <div class="grid grid-cols-6 items-end gap-3">
                        @foreach($monthlyBookingTrend as $month)
                            <div class="text-center">
                                <div class="mx-auto flex h-40 items-end justify-center rounded-2xl bg-slate-50 px-2 py-2">
                                    <div class="w-full rounded-t-2xl bg-primary-600" data-fill-height="{{ max(10, round(($month->total / $maxMonthlyBookings) * 100)) }}"></div>
                                </div>
                                <div class="mt-3 text-sm font-bold text-slate-800">{{ $month->total }}</div>
                                <div class="text-xs text-slate-400">{{ $month->short_label }}</div>
                                <div class="mt-1 text-[11px] text-slate-400">&#8369;{{ number_format($month->revenue, 0) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-5">
                    <div>
                        <div class="mb-3 text-xs font-extrabold uppercase tracking-[0.16em] text-slate-400">Peak Time Slots</div>
                        <div class="space-y-3">
                            @forelse($timeSlotTrends as $slot)
                                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-sm font-semibold text-slate-800">{{ $slot->label }}</div>
                                        <div class="text-sm font-bold text-emerald-600">{{ $slot->total }}</div>
                                    </div>
                                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-white">
                                        <div class="admin-dashboard-progress-fill bg-emerald-500" data-fill-width="{{ $maxTimeSlotDemand > 0 ? round(($slot->total / $maxTimeSlotDemand) * 100) : 0 }}"></div>
                                    </div>
                                    <div class="mt-2 text-xs text-slate-400">{{ $slot->completed }} completed booking{{ $slot->completed === 1 ? '' : 's' }}</div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-sm text-slate-500">
                                    Peak time slot data will appear here after schedules are recorded.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div>
                        <div class="mb-3 text-xs font-extrabold uppercase tracking-[0.16em] text-slate-400">Busiest Days</div>
                        <div class="space-y-3">
                            @foreach($weekdayTrends as $day)
                                <div>
                                    <div class="mb-1 flex items-center justify-between text-sm">
                                        <span class="font-medium text-slate-700">{{ $day->label }}</span>
                                        <span class="text-slate-500">{{ $day->total }}</span>
                                    </div>
                                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                        <div class="admin-dashboard-progress-fill bg-cyan-500" data-fill-width="{{ $maxWeekdayDemand > 0 ? round(($day->total / $maxWeekdayDemand) * 100) : 0 }}"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="cleanflow-panel overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-lg font-extrabold text-slate-900">Customer Satisfaction Trends</h3>
                <p class="mt-1 text-sm text-slate-500">Track review volume, average score, and positive-share movement by month.</p>
            </div>

            <div class="space-y-3 px-5 py-5">
                @forelse($satisfactionTrend as $month)
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-slate-800">{{ $month->label }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ $month->reviews }} review{{ $month->reviews === 1 ? '' : 's' }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-amber-500">{{ $month->average !== null ? number_format($month->average, 1) : 'N/A' }}</div>
                                <div class="text-[11px] text-slate-400">{{ number_format($month->positive_share, 0) }}% positive</div>
                            </div>
                        </div>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-white">
                            <div class="admin-dashboard-progress-fill bg-amber-400" data-fill-width="{{ $month->average !== null ? round(($month->average / 5) * 100) : 0 }}"></div>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-white">
                            <div class="admin-dashboard-progress-fill bg-emerald-500" data-fill-width="{{ $maxReviewCount > 0 ? round(($month->reviews / $maxReviewCount) * 100) : 0 }}"></div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-sm text-slate-500">
                        Customer satisfaction trends will appear here after completed bookings receive ratings.
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        <section class="cleanflow-panel overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-lg font-extrabold text-slate-900">Revenue by Service</h3>
                <p class="mt-1 text-sm text-slate-500">Completed-booking revenue grouped by active service catalog item.</p>
            </div>
            <div class="space-y-3 px-5 py-5">
                @forelse($revenueByType as $type)
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm font-bold text-slate-900">{{ $type->service_name }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $type->total }} completed booking{{ $type->total == 1 ? '' : 's' }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-black text-emerald-600">&#8369;{{ number_format($type->revenue, 0) }}</div>
                                <div class="text-[11px] font-semibold text-slate-400">
                                    Avg &#8369;{{ $type->total > 0 ? number_format($type->revenue / $type->total, 0) : 0 }}
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-white">
                            <div class="admin-dashboard-progress-fill bg-emerald-500" data-fill-width="{{ round(($type->revenue / $maxServiceRevenue) * 100) }}"></div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-sm text-slate-500">
                        Revenue by service will appear after completed bookings use active service catalog entries.
                    </div>
                @endforelse
            </div>
        </section>

        <section class="cleanflow-panel overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-lg font-extrabold text-slate-900">Bookings by Service Type</h3>
                <p class="mt-1 text-sm text-slate-500">Service mix based on bookings that still match the active service catalog.</p>
            </div>
            <div class="overflow-x-auto px-5 py-4">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                            <th class="py-3">Service Type</th>
                            <th class="py-3 text-center">Total</th>
                            <th class="py-3 text-right">Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bookingsByType as $type)
                            <tr class="border-b border-slate-50">
                                <td class="py-3 font-medium text-slate-700">
                                    @if($type->service_type === 'basic')
                                        <i class="fas fa-home mr-2 text-emerald-500"></i>
                                    @elseif($type->service_type === 'deep')
                                        <i class="fas fa-soap mr-2 text-orange-500"></i>
                                    @else
                                        <i class="fas fa-truck-moving mr-2 text-green-500"></i>
                                    @endif
                                    {{ $type->service_name }}
                                </td>
                                <td class="py-3 text-center font-bold text-slate-800">{{ $type->total }}</td>
                                <td class="py-3 text-right">
                                    <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">
                                        {{ $serviceBookingsTotal > 0 ? round(($type->total / $serviceBookingsTotal) * 100, 1) : 0 }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-8 text-center text-sm text-slate-500">Service analytics will appear here once bookings use active service catalog entries.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="cleanflow-panel overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-lg font-extrabold text-slate-900">Booking Status Summary</h3>
                <p class="mt-1 text-sm text-slate-500">Relative volume of each booking state across the current report set.</p>
            </div>
            <div class="space-y-3 px-5 py-5">
                @foreach($statusBars as $status)
                    <div>
                        <div class="mb-1 flex justify-between text-sm">
                            <span class="text-slate-600">{{ $status['label'] }}</span>
                            <span class="font-semibold text-slate-800">{{ $status['count'] }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="{{ $status['color'] }} h-2 rounded-full" data-fill-width="{{ $totalBookings > 0 ? ($status['count'] / $totalBookings) * 100 : 0 }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <section class="cleanflow-panel overflow-hidden">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-lg font-extrabold text-slate-900">Top Staff Trends</h3>
            <p class="mt-1 text-sm text-slate-500">Compare assignment volume, completions, ratings, and month-over-month trend movement.</p>
        </div>
        <div class="overflow-x-auto px-5 py-4">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                        <th class="py-3">Staff Name</th>
                        <th class="py-3">Barangay</th>
                        <th class="py-3 text-center">Assigned</th>
                        <th class="py-3 text-center">Completed</th>
                        <th class="py-3 text-center">This Month</th>
                        <th class="py-3 text-center">Trend</th>
                        <th class="py-3 text-center">Completion Rate</th>
                        <th class="py-3 text-center">Avg Rating</th>
                        <th class="py-3 text-center">Month Rating</th>
                        <th class="py-3 text-center">Reviews</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staffPerformance as $staff)
                        <tr class="border-b border-slate-50 hover:bg-slate-50">
                            <td class="py-3 font-medium text-slate-800">{{ $staff->first_name }} {{ $staff->last_name }}</td>
                            <td class="py-3 text-slate-600">{{ ucfirst($staff->barangay) }}</td>
                            <td class="py-3 text-center font-semibold text-slate-800">{{ $staff->total_assigned }}</td>
                            <td class="py-3 text-center font-semibold text-green-600">{{ $staff->total_completed }}</td>
                            <td class="py-3 text-center font-semibold text-cyan-600">{{ $staff->current_month_completed }}</td>
                            <td class="py-3 text-center">
                                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $staff->trend_change < 0 ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ $staff->trend_change >= 0 ? '+' : '' }}{{ $staff->trend_change }}
                                </span>
                            </td>
                            <td class="py-3 text-center">
                                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $staff->completion_rate >= 70 ? 'bg-green-100 text-green-700' : ($staff->completion_rate >= 40 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                    {{ $staff->completion_rate }}%
                                </span>
                            </td>
                            <td class="py-3 text-center">
                                @if($staff->avg_rating)
                                    <div class="flex items-center justify-center gap-1">
                                        <i class="fas fa-star text-xs text-yellow-400"></i>
                                        <span class="font-semibold text-slate-800">{{ $staff->avg_rating }}</span>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">No reviews yet</span>
                                @endif
                            </td>
                            <td class="py-3 text-center">
                                @if($staff->current_month_avg_rating)
                                    <span class="font-semibold text-amber-500">{{ $staff->current_month_avg_rating }}</span>
                                @else
                                    <span class="text-xs text-slate-400">No month data</span>
                                @endif
                            </td>
                            <td class="py-3 text-center text-slate-600">{{ $staff->total_ratings }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-8 text-center text-sm text-slate-500">No staff performance data is available yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="cleanflow-panel overflow-hidden">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
            <div>
                <h3 class="text-lg font-extrabold text-slate-900">Recent Service History</h3>
                <p class="mt-1 text-sm text-slate-500">Last 10 booking records included in the latest reporting snapshot.</p>
            </div>
            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Last 10 bookings</span>
        </div>
        <div class="overflow-x-auto px-5 py-4">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                        <th class="py-3">Booking #</th>
                        <th class="py-3">Client</th>
                        <th class="py-3">Service</th>
                        <th class="py-3">Date</th>
                        <th class="py-3">Staff</th>
                        <th class="py-3 text-right">Price</th>
                        <th class="py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentBookings as $booking)
                        @php
                            $statusColors = [
                                'pending' => 'bg-amber-100 text-amber-700',
                                'confirmed' => 'bg-accent-50 text-accent-700',
                                'in_progress' => 'bg-primary-100 text-primary-700',
                                'completed' => 'bg-accent-100 text-accent-800',
                                'cancelled' => 'bg-danger-100 text-danger-700',
                            ];
                        @endphp
                        <tr class="border-b border-slate-50 hover:bg-slate-50">
                            <td class="py-3 font-mono font-semibold text-primary-700">CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td class="py-3 text-slate-800">{{ $booking->user->first_name }} {{ $booking->user->last_name }}</td>
                            <td class="py-3 text-slate-600">{{ $booking->service_label }}</td>
                            <td class="py-3 text-slate-600">{{ \Carbon\Carbon::parse($booking->scheduled_date)->format('M d, Y') }}</td>
                            <td class="py-3 text-slate-600">{{ $booking->staff ? $booking->staff->first_name . ' ' . $booking->staff->last_name : 'Not assigned' }}</td>
                            <td class="py-3 text-right font-semibold text-slate-800">&#8369;{{ number_format($booking->price, 2) }}</td>
                            <td class="py-3 text-center">
                                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $statusColors[$booking->status] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-sm text-slate-500">Recent service history will appear here once bookings are recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="flex justify-end">
        <button onclick="window.print()" class="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700">
            <i class="fas fa-print"></i>
            Print / Export Report
        </button>
    </div>
</div>
@endsection
