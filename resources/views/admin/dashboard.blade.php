@extends('layouts.admin')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Overview of your business performance')

@section('content')
@php
    $criticalEscalations = (int) ($pendingEscalationSummary['critical'] ?? 0);
    $warningEscalations  = (int) ($pendingEscalationSummary['warning'] ?? 0);
    $overduePending      = $criticalEscalations + $warningEscalations;

    $completionRate = $dashboardStats['total_bookings'] > 0
        ? round(($dashboardStats['completed_bookings'] / $dashboardStats['total_bookings']) * 100, 1)
        : 0;

    $hour     = (int) $dashboardNow->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

    $totalForStatus = max(1, $dashboardStats['total_bookings']);
    $statusBreakdown = [
        ['label' => 'Pending',     'value' => $dashboardStats['pending_bookings'],     'color' => '#F59E0B'],
        ['label' => 'Confirmed',   'value' => $dashboardStats['confirmed_bookings'],   'color' => '#2563EB'],
        ['label' => 'In Progress', 'value' => $dashboardStats['in_progress_bookings'], 'color' => '#0D9488'],
        ['label' => 'Completed',   'value' => $dashboardStats['completed_bookings'],   'color' => '#059669'],
        ['label' => 'Cancelled',   'value' => $cancelledCount,                         'color' => '#DC2626'],
    ];
    foreach ($statusBreakdown as &$s) {
        $s['pct'] = round($s['value'] / $totalForStatus * 100, 1);
    }
    unset($s);

    $activityIconMap = [
        'pending'     => ['icon' => 'fa-clock',          'color' => '#F59E0B'],
        'confirmed'   => ['icon' => 'fa-calendar-check', 'color' => '#2563EB'],
        'in_progress' => ['icon' => 'fa-arrows-spin',    'color' => '#0D9488'],
        'completed'   => ['icon' => 'fa-check',          'color' => '#059669'],
        'cancelled'   => ['icon' => 'fa-xmark',          'color' => '#DC2626'],
    ];

    $maxServiceBookings   = $servicePopularity->max('bookings') ?: 1;
    $serviceBarColorsHex  = ['#3B82F6', '#059669', '#F59E0B', '#0D9488', '#94A3B8'];
@endphp

<div class="admin-page-content space-y-5 p-6">
    <p class="sr-only">
        Today: {{ number_format($dashboardStats['total_bookings']) }} bookings | {{ number_format($dashboardStats['pending_bookings']) }} pending | {{ number_format($dashboardStats['in_progress_bookings']) }} in progress | {{ number_format($dashboardStats['staff']) }} staff
    </p>
    <p class="sr-only">Active Queue - Recent Bookings</p>
    <p class="sr-only">Quick Actions</p>
    <p class="sr-only">Today Snapshot</p>
    <p class="sr-only">Staff Trends</p>

    {{-- Escalation Alert --}}
    @if($overduePending > 0)
    <section class="rounded-xl border {{ $criticalEscalations > 0 ? 'border-red-200 bg-red-50 text-red-800' : 'border-amber-200 bg-amber-50 text-amber-800' }} px-5 py-4 shadow-sm">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <div class="text-xs font-bold uppercase tracking-wide">{{ $criticalEscalations > 0 ? 'Critical' : 'Attention Needed' }}</div>
                <div class="mt-1 text-base font-bold">
                    {{ number_format($overduePending) }} pending booking{{ $overduePending === 1 ? '' : 's' }} need{{ $overduePending === 1 ? 's' : '' }} assignment.
                </div>
                <div class="mt-1 text-sm opacity-80">
                    {{ number_format($criticalEscalations) }} critical (over 7 days), {{ number_format($warningEscalations) }} warning (over 24 hours).
                </div>
            </div>
            <a href="{{ route('admin.bookings', ['tab' => 'active']) }}" class="inline-flex w-fit items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">
                <i class="fas fa-arrow-right"></i>
                Assign now
            </a>
        </div>
    </section>
    @endif

    {{-- WELCOME HERO BANNER --}}
    <section class="cleanflow-hero overflow-hidden px-6 py-4 text-white sm:px-7">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="min-w-0">
                <span class="cleanflow-kicker">
                    <i class="fas fa-chart-line"></i>
                    Dashboard
                </span>
                <h2 class="mt-2 text-2xl font-black tracking-tight">
                    {{ $greeting }}, {{ auth()->user()->first_name }}.
                </h2>
                <p class="mt-1 max-w-2xl text-sm leading-6 text-white/80">
                    Monitor service demand, revenue, attendance, and job readiness.
                </p>
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <a href="{{ route('admin.bookings', ['tab' => 'active']) }}" class="inline-flex items-center gap-2 rounded-full bg-white px-3.5 py-2 text-sm font-bold text-blue-900 shadow-sm transition hover:bg-blue-50">
                    <i class="fas fa-calendar-check"></i>
                    Manage Bookings
                </a>
                <a href="{{ route('admin.attendance') }}" class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3.5 py-2 text-sm font-semibold text-white transition hover:bg-white/20">
                    <i class="fas fa-fingerprint"></i>
                    Attendance
                </a>
                <span class="inline-flex items-center gap-2 rounded-full border border-white/18 bg-white/8 px-3.5 py-2 text-sm font-semibold text-white/85">
                    <i class="fas fa-user-clock"></i>
                    {{ number_format($unassignedBookings) }} unassigned
                </span>
                <span class="inline-flex items-center gap-2 rounded-full border border-white/18 bg-white/8 px-3.5 py-2 text-sm font-semibold text-white/85">
                    <i class="fas fa-triangle-exclamation"></i>
                    {{ number_format($overduePending) }} needs action
                </span>
            </div>
        </div>
    </section>

    {{-- KPI CARDS --}}
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="cleanflow-panel p-5" style="border-left: 4px solid #2563EB;">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total Bookings</div>
                    <div class="mt-2 text-4xl font-black leading-none text-slate-900">{{ number_format($dashboardStats['total_bookings']) }}</div>
                    <div class="mt-2 text-sm text-slate-500">{{ number_format($completionRate, 1) }}% completion rate</div>
                </div>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white">
                    <i class="fas fa-calendar-days"></i>
                </div>
            </div>
        </div>

        <div class="cleanflow-panel p-5" style="border-left: 4px solid #059669;">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total Revenue</div>
                    <div class="mt-2 text-3xl font-black leading-none text-slate-900">&#8369;{{ number_format($totalEarnings, 2) }}</div>
                    <div class="mt-2 text-sm text-slate-500">All-time from completed bookings</div>
                </div>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-white">
                    <i class="fas fa-peso-sign"></i>
                </div>
            </div>
        </div>

        <div class="cleanflow-panel p-5" style="border-left: 4px solid #0D9488;">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Completion Rate</div>
                    <div class="mt-2 text-4xl font-black leading-none text-slate-900">{{ number_format($completionRate, 1) }}<span class="text-xl text-slate-400">%</span></div>
                    <div class="mt-2 text-sm text-slate-500">{{ number_format($dashboardStats['completed_bookings']) }} of {{ number_format($dashboardStats['total_bookings']) }} requests</div>
                </div>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-white" style="background-color: #0D9488;">
                    <i class="fas fa-circle-check"></i>
                </div>
            </div>
        </div>

        <div class="cleanflow-panel p-5" style="border-left: 4px solid #F59E0B;">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Customer Rating</div>
                    <div class="mt-2 text-4xl font-black leading-none text-slate-900">
                        @if($analyticsAvgRating)
                            {{ number_format($analyticsAvgRating, 1) }}<span class="text-xl text-slate-400"> / 5</span>
                        @else
                            <span class="text-2xl">N/A</span>
                        @endif
                    </div>
                    <div class="mt-2 text-sm text-slate-500">{{ $analyticsRatingCount }} review{{ $analyticsRatingCount === 1 ? '' : 's' }} (last 30 days)</div>
                </div>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-amber-400 text-white">
                    <i class="fas fa-star"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- ANALYTICS CHART ROW --}}
    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)_minmax(0,1fr)]">

        {{-- Booking Trends Chart --}}
        <section class="cleanflow-panel overflow-hidden rounded-2xl shadow-sm">
            <div class="flex items-start justify-between gap-4 px-5 pt-5">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-extrabold leading-none text-slate-900">Booking Trends</h3>
                        <span class="inline-flex h-4 w-4 items-center justify-center rounded-full border border-slate-300 text-[10px] font-bold text-slate-500" title="Bookings created over time">i</span>
                    </div>
                    <p class="mt-3 text-sm font-medium text-slate-500">Number of bookings over time</p>
                </div>
                <div class="inline-flex shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-white p-1 shadow-sm" aria-label="Booking trend range">
                    <button type="button" data-trend-range="daily" class="dashboard-trend-range rounded-md px-3 py-1.5 text-xs font-bold text-blue-700 shadow-sm">Daily</button>
                    <button type="button" data-trend-range="weekly" class="dashboard-trend-range rounded-md px-3 py-1.5 text-xs font-bold text-slate-700">Weekly</button>
                    <button type="button" data-trend-range="monthly" class="dashboard-trend-range rounded-md px-3 py-1.5 text-xs font-bold text-slate-700">Monthly</button>
                </div>
            </div>
            <div class="px-5 pb-5 pt-4">
                <div class="h-56">
                    <canvas id="dashboard-trends-chart"></canvas>
                </div>
                @if(array_sum($chartBookingsData) === 0)
                    <div class="mt-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-center text-sm text-slate-500">
                        No booking activity yet.
                    </div>
                @endif
            </div>
        </section>

        {{-- Revenue Overview --}}
        <section class="cleanflow-panel overflow-hidden rounded-2xl shadow-sm">
            <div class="flex items-start justify-between gap-4 px-5 pt-5">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-extrabold leading-none text-slate-900">Revenue Overview</h3>
                        <span class="inline-flex h-4 w-4 items-center justify-center rounded-full border border-slate-300 text-[10px] font-bold text-slate-500" title="Revenue from completed bookings">i</span>
                    </div>
                    <p class="mt-3 text-sm font-medium text-slate-500">Revenue over time (PHP)</p>
                    <div class="mt-3 text-2xl font-black leading-none text-slate-900">&#8369;{{ number_format($selectedRevenueTotal, 2) }}</div>
                    <div class="mt-2 text-xs font-semibold text-emerald-600">{{ number_format($selectedRevenueCompletedCount) }} completed booking{{ $selectedRevenueCompletedCount === 1 ? '' : 's' }}</div>
                </div>
                <form method="GET" action="{{ route('admin.dashboard') }}" class="shrink-0">
                    @foreach(request()->except('revenue_month') as $queryKey => $queryValue)
                        @if(is_array($queryValue))
                            @foreach($queryValue as $nestedValue)
                                <input type="hidden" name="{{ $queryKey }}[]" value="{{ $nestedValue }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $queryKey }}" value="{{ $queryValue }}">
                        @endif
                    @endforeach
                    <label for="dashboard-revenue-month" class="sr-only">Revenue month</label>
                    @php
                        $revenueMonthOptions = collect($availableRevenueMonths)
                            ->prepend($selectedRevenueMonth)
                            ->map(fn ($month) => (string) $month)
                            ->filter(fn ($month) => preg_match('/^\d{4}-\d{2}$/', $month) === 1)
                            ->unique()
                            ->values();
                    @endphp
                    <select id="dashboard-revenue-month" name="revenue_month" onchange="this.form.submit()" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-sm">
                        @forelse($revenueMonthOptions as $month)
                            <option value="{{ $month }}" @selected($selectedRevenueMonth === $month)>
                                {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}
                            </option>
                        @empty
                            <option value="{{ $selectedRevenueMonth }}">{{ $selectedRevenueMonthLabel }}</option>
                        @endforelse
                    </select>
                </form>
            </div>
            <div class="px-5 pb-5 pt-4">
                <div class="h-36">
                    <canvas id="dashboard-revenue-chart"></canvas>
                </div>
                @if($selectedRevenueCompletedCount === 0)
                    <div class="mt-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-center text-sm text-slate-500">
                        No completed bookings for {{ $selectedRevenueMonthLabel }}.
                    </div>
                @endif
            </div>
        </section>

        {{-- Service Popularity --}}
        <section class="cleanflow-panel overflow-hidden rounded-2xl shadow-sm">
            <div class="px-5 pt-5">
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-extrabold leading-none text-slate-900">Service Popularity</h3>
                    <span class="inline-flex h-4 w-4 items-center justify-center rounded-full border border-slate-300 text-[10px] font-bold text-slate-500" title="Top services by booking count">i</span>
                </div>
                <p class="mt-3 text-sm font-medium text-slate-500">By number of bookings</p>
            </div>
            <div class="grid items-center gap-4 px-5 pb-5 pt-4 sm:grid-cols-[150px_minmax(0,1fr)] xl:grid-cols-1 2xl:grid-cols-[150px_minmax(0,1fr)]">
                <div class="relative mx-auto h-36 w-36">
                    <canvas id="dashboard-service-chart"></canvas>
                    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
                        <div class="text-2xl font-black leading-none text-slate-900">{{ number_format($dashboardStats['total_bookings']) }}</div>
                        <div class="mt-1 text-xs font-semibold text-slate-500">Total</div>
                    </div>
                </div>
                <div class="space-y-2">
                    @forelse($servicePopularity as $index => $service)
                        @php $servicePct = $dashboardStats['total_bookings'] > 0 ? round($service['bookings'] / $dashboardStats['total_bookings'] * 100, 1) : 0; @endphp
                        <div class="flex items-start gap-2 text-xs">
                            <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $serviceBarColorsHex[$index] ?? '#94A3B8' }}"></span>
                            <div class="min-w-0">
                                <div class="truncate font-bold text-slate-700">{{ $service['name'] }}</div>
                                <div class="text-slate-500">{{ $service['bookings'] }} ({{ $servicePct }}%)</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-slate-400">No service activity yet.</div>
                    @endforelse
                </div>
            </div>
        </section>

    </div>

    {{-- STATUS | STAFF PERFORMANCE | RECENT ACTIVITY --}}
    <div class="grid gap-5 xl:grid-cols-3">

        {{-- Booking Status Overview --}}
        <section class="cleanflow-panel overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-lg font-extrabold text-slate-900">Booking Status Overview</h3>
                <p class="mt-1 text-sm text-slate-500">All-time status of {{ number_format($dashboardStats['total_bookings']) }} bookings.</p>
            </div>
            <div class="space-y-4 px-5 py-4">
                <div class="grid grid-cols-2 gap-2">
                    @foreach($statusBreakdown as $status)
                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-3">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $status['color'] }};"></span>
                            <span class="text-xs font-bold text-slate-600">{{ $status['label'] }}</span>
                        </div>
                        <div class="mt-2 flex items-end justify-between gap-2">
                            <span class="text-2xl font-black leading-none text-slate-900">{{ number_format($status['value']) }}</span>
                            <span class="text-xs font-semibold text-slate-400">{{ $status['pct'] }}%</span>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="flex h-2.5 overflow-hidden rounded-full bg-slate-100">
                    @foreach($statusBreakdown as $status)
                        @if($status['pct'] > 0)
                        <div style="width: {{ $status['pct'] }}%; background-color: {{ $status['color'] }}; height: 100%;"></div>
                        @endif
                    @endforeach
                </div>

                <div class="grid gap-2">
                    <a href="{{ route('admin.bookings', ['tab' => 'active', 'filter' => 'unassigned']) }}" class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2.5 text-sm transition hover:bg-slate-50">
                        <span class="font-medium text-slate-600">Pending Assignment</span>
                        <span class="font-bold {{ $unassignedBookings > 0 ? 'text-blue-700' : 'text-slate-400' }}">{{ number_format($unassignedBookings) }}</span>
                    </a>
                    <a href="{{ route('admin.attendance') }}" class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2.5 text-sm transition hover:bg-slate-50">
                        <span class="font-medium text-slate-600">Staff Present Today</span>
                        <span class="font-bold {{ $presentStaffCount > 0 ? 'text-emerald-600' : 'text-slate-400' }}">{{ number_format($presentStaffCount) }} / {{ number_format($dashboardStats['staff']) }}</span>
                    </a>
                    <div class="flex items-center justify-between rounded-xl border border-slate-100 px-3 py-2.5 text-sm">
                        <span class="font-medium text-slate-600">Jobs Tomorrow</span>
                        <span class="font-bold text-slate-700">{{ number_format($tomorrowJobs) }}</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Staff Performance --}}
        <section class="cleanflow-panel overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-lg font-extrabold text-slate-900">Staff Performance</h3>
                <p class="mt-1 text-sm text-slate-500">Top staff by completions in 30 days.</p>
            </div>
            <div class="overflow-x-auto px-6 py-4">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs font-bold uppercase tracking-wide text-slate-400">
                            <th class="pb-3">Staff</th>
                            <th class="pb-3 text-center">Done</th>
                            <th class="pb-3 text-center">Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($staffPerformance as $index => $member)
                        <tr class="border-b border-slate-50 hover:bg-slate-50">
                            <td class="py-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold
                                        {{ $index === 0 ? 'bg-blue-600 text-white' : ($index === 1 ? 'bg-slate-300 text-slate-700' : 'bg-slate-100 text-slate-500') }}">
                                        {{ $index + 1 }}
                                    </div>
                                    <span class="font-semibold text-slate-800">{{ $member['name'] }}</span>
                                </div>
                            </td>
                            <td class="py-3 text-center font-bold text-emerald-600">{{ $member['completed'] }}</td>
                            <td class="py-3 text-center">
                                @if($member['rating'])
                                    <span class="font-bold text-amber-500">{{ number_format($member['rating'], 1) }}</span>
                                    <i class="fas fa-star text-amber-400" style="font-size:10px"></i>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="py-8 text-center text-sm text-slate-400">No staff activity in the last 30 days.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6 py-3">
                <a href="{{ route('admin.staff.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">View all staff &rarr;</a>
            </div>
        </section>

        {{-- Recent Activity --}}
        <section class="cleanflow-panel overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-lg font-extrabold text-slate-900">Recent Activity</h3>
                <p class="mt-1 text-sm text-slate-500">Latest booking updates across all statuses.</p>
            </div>
            <div class="divide-y divide-slate-50 px-6 py-2">
                @forelse($recentActivity as $activity)
                @php $actMap = $activityIconMap[$activity->status] ?? ['icon' => 'fa-circle', 'color' => '#94A3B8']; @endphp
                <div class="flex items-start gap-3 py-3.5">
                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-white" style="background-color: {{ $actMap['color'] }}; font-size:10px">
                        <i class="fas {{ $actMap['icon'] }}"></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-semibold text-slate-800">
                            {{ ucwords(str_replace('_', ' ', $activity->status)) }}
                            <span class="font-mono text-xs font-medium text-slate-400">&nbsp;CF-{{ str_pad($activity->id, 5, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        <div class="mt-0.5 truncate text-xs text-slate-500">
                            {{ $activity->service_label }} &bull; {{ $activity->user?->display_name ?? 'Unknown' }}
                        </div>
                    </div>
                    <div class="shrink-0 text-[11px] text-slate-400 whitespace-nowrap">{{ optional($activity->updated_at)->diffForHumans(null, true, true) }}</div>
                </div>
                @empty
                <div class="py-8 text-center text-sm text-slate-400">No recent booking activity.</div>
                @endforelse
            </div>
            <div class="border-t border-slate-100 px-6 py-3">
                <a href="{{ route('admin.bookings') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">View all bookings &rarr;</a>
            </div>
        </section>
    </div>

</div>

@push('scripts')
        <script src="{{ asset('vendor/chart.js/chart.umd.min.js') }}"></script>
        <script>
            const dashboardTrendsCanvas = document.getElementById('dashboard-trends-chart');
            if (dashboardTrendsCanvas) {
                const trendDates = @json($chartDateLabels);
                const trendLabels = @json($chartLabels);
                const trendBookings = @json($chartBookingsData);
                const revenueDates = @json($revenueDateLabels);
                const revenueLabels = @json($revenueLabels);
                const trendRevenue = @json($chartRevenueData);
                const serviceLabels = @json($servicePopularity->pluck('name')->values());
                const serviceData = @json($servicePopularity->pluck('bookings')->values());
                const serviceColors = @json($serviceBarColorsHex);
                const trendRangeButtons = document.querySelectorAll('[data-trend-range]');
                const trendFormatter = new Intl.DateTimeFormat('en', { month: 'short', day: 'numeric', year: 'numeric' });
                const trendShortFormatter = new Intl.DateTimeFormat('en', { month: 'short', day: 'numeric' });
                const trendMonthFormatter = new Intl.DateTimeFormat('en', { month: 'short', year: 'numeric' });
                const pesoFormatter = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 2, maximumFractionDigits: 2 });

                function aggregateTrendData(range) {
                    if (range === 'daily') {
                        return {
                            labels: trendLabels,
                            tooltipLabels: trendDates.map((date) => trendFormatter.format(new Date(`${date}T00:00:00`))),
                            values: trendBookings,
                        };
                    }

                    const groups = new Map();
                    trendDates.forEach((date, index) => {
                        const parsed = new Date(`${date}T00:00:00`);
                        let key;
                        let label;
                        let tooltipLabel;

                        if (range === 'weekly') {
                            const weekStart = new Date(parsed);
                            weekStart.setDate(parsed.getDate() - parsed.getDay());
                            const weekEnd = new Date(weekStart);
                            weekEnd.setDate(weekStart.getDate() + 6);
                            key = weekStart.toISOString().slice(0, 10);
                            label = trendShortFormatter.format(weekStart);
                            tooltipLabel = `${trendShortFormatter.format(weekStart)} - ${trendShortFormatter.format(weekEnd)}, ${weekEnd.getFullYear()}`;
                        } else {
                            key = `${parsed.getFullYear()}-${String(parsed.getMonth() + 1).padStart(2, '0')}`;
                            label = trendMonthFormatter.format(parsed);
                            tooltipLabel = label;
                        }

                        const group = groups.get(key) || { label, tooltipLabel, value: 0 };
                        group.value += Number(trendBookings[index] || 0);
                        groups.set(key, group);
                    });

                    const aggregated = Array.from(groups.values());
                    return {
                        labels: aggregated.map((group) => group.label),
                        tooltipLabels: aggregated.map((group) => group.tooltipLabel),
                        values: aggregated.map((group) => group.value),
                    };
                }

                function trendGradient(context) {
                    const chart = context.chart;
                    const { ctx, chartArea } = chart;
                    if (!chartArea) {
                        return 'rgba(37, 99, 235, 0.12)';
                    }

                    const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    gradient.addColorStop(0, 'rgba(37, 99, 235, 0.22)');
                    gradient.addColorStop(1, 'rgba(37, 99, 235, 0)');
                    return gradient;
                }

                function trendAxisMax(values) {
                    const highest = Math.max(...values.map((value) => Number(value || 0)), 0);
                    return Math.max(40, Math.ceil(highest / 10) * 10);
                }

                const initialTrendData = aggregateTrendData('daily');
                const dashboardTrendsChart = new Chart(dashboardTrendsCanvas, {
                    type: 'line',
                    data: {
                        labels: initialTrendData.labels,
                        datasets: [
                            {
                                label: 'Bookings',
                                data: initialTrendData.values,
                                borderColor: '#2563EB',
                                backgroundColor: trendGradient,
                                tension: 0.4,
                                fill: true,
                                borderWidth: 3,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointBackgroundColor: '#FFFFFF',
                                pointBorderColor: '#2563EB',
                                pointBorderWidth: 3,
                                yAxisID: 'y',
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'nearest', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#FFFFFF',
                                titleColor: '#0F172A',
                                bodyColor: '#64748B',
                                borderColor: '#E2E8F0',
                                borderWidth: 1,
                                padding: 14,
                                cornerRadius: 10,
                                displayColors: false,
                                titleFont: { size: 13, weight: '700' },
                                bodyFont: { size: 13, weight: '600' },
                                callbacks: {
                                    title(items) {
                                        const item = items[0];
                                        return item ? item.chart.$tooltipLabels[item.dataIndex] : '';
                                    },
                                    label(context) {
                                        return `Bookings: ${context.parsed.y}`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                border: { display: false },
                                grid: { display: false },
                                ticks: {
                                    color: '#64748B',
                                    font: { size: 12, weight: '600' },
                                    maxRotation: 0,
                                    autoSkipPadding: 24,
                                }
                            },
                            y: {
                                beginAtZero: true,
                                max: trendAxisMax(initialTrendData.values),
                                border: { display: false },
                                grid: {
                                    color: '#E2E8F0',
                                    drawTicks: false,
                                },
                                ticks: {
                                    color: '#64748B',
                                    font: { size: 12, weight: '600' },
                                    padding: 12,
                                    precision: 0,
                                    stepSize: 10,
                                }
                            }
                        }
                    }
                });

                dashboardTrendsChart.$tooltipLabels = initialTrendData.tooltipLabels;

                function setTrendRange(range) {
                    const nextData = aggregateTrendData(range);
                    dashboardTrendsChart.data.labels = nextData.labels;
                    dashboardTrendsChart.data.datasets[0].data = nextData.values;
                    dashboardTrendsChart.$tooltipLabels = nextData.tooltipLabels;
                    dashboardTrendsChart.options.scales.y.max = trendAxisMax(nextData.values);
                    dashboardTrendsChart.update();

                    trendRangeButtons.forEach((button) => {
                        const isActive = button.dataset.trendRange === range;
                        button.classList.toggle('bg-blue-50', isActive);
                        button.classList.toggle('text-blue-700', isActive);
                        button.classList.toggle('shadow-sm', isActive);
                        button.classList.toggle('text-slate-700', !isActive);
                    });
                }

                setTrendRange('daily');
                trendRangeButtons.forEach((button) => {
                    button.addEventListener('click', () => setTrendRange(button.dataset.trendRange));
                });

                const revenueCanvas = document.getElementById('dashboard-revenue-chart');
                if (revenueCanvas) {
                    new Chart(revenueCanvas, {
                        type: 'line',
                        data: {
                            labels: revenueLabels,
                            datasets: [{
                                label: 'Revenue',
                                data: trendRevenue,
                                borderColor: '#2563EB',
                                backgroundColor: trendGradient,
                                tension: 0.4,
                                fill: true,
                                borderWidth: 2,
                                pointRadius: 0,
                                pointHoverRadius: 4,
                                pointBackgroundColor: '#FFFFFF',
                                pointBorderColor: '#2563EB',
                                pointBorderWidth: 2,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: { mode: 'nearest', intersect: false },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: '#FFFFFF',
                                    titleColor: '#0F172A',
                                    bodyColor: '#64748B',
                                    borderColor: '#E2E8F0',
                                    borderWidth: 1,
                                    padding: 10,
                                    cornerRadius: 10,
                                    displayColors: false,
                                    callbacks: {
                                        title(items) {
                                            const item = items[0];
                                            return item ? trendFormatter.format(new Date(`${revenueDates[item.dataIndex]}T00:00:00`)) : '';
                                        },
                                        label(context) {
                                            return `Revenue: ${pesoFormatter.format(context.parsed.y)}`;
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    border: { display: false },
                                    grid: { display: false },
                                    ticks: {
                                        color: '#64748B',
                                        font: { size: 10, weight: '600' },
                                        maxRotation: 0,
                                        autoSkipPadding: 18,
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    border: { display: false },
                                    grid: { color: '#E2E8F0', drawTicks: false },
                                    ticks: {
                                        color: '#64748B',
                                        font: { size: 10, weight: '600' },
                                        padding: 8,
                                        callback(value) {
                                            return value >= 1000 ? `${value / 1000}K` : value;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }

                const serviceCanvas = document.getElementById('dashboard-service-chart');
                if (serviceCanvas && serviceData.length > 0) {
                    new Chart(serviceCanvas, {
                        type: 'doughnut',
                        data: {
                            labels: serviceLabels,
                            datasets: [{
                                data: serviceData,
                                backgroundColor: serviceColors,
                                borderWidth: 0,
                                hoverOffset: 4,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '62%',
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: '#FFFFFF',
                                    titleColor: '#0F172A',
                                    bodyColor: '#64748B',
                                    borderColor: '#E2E8F0',
                                    borderWidth: 1,
                                    padding: 10,
                                    cornerRadius: 10,
                                    displayColors: false,
                                    callbacks: {
                                        label(context) {
                                            return `${context.label}: ${context.parsed} bookings`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }
        </script>
@endpush
@endsection
