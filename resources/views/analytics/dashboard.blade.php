@extends('layouts.admin')

@section('title', 'Analytics Dashboard')
@section('page-title', 'Analytics Dashboard')
@section('page-subtitle', 'Date-range performance and operational trends')

@section('content')
@php
    $overviewCards = [
        [
            'label' => 'Total Bookings',
            'value' => number_format($bookingMetrics['total']),
            'description' => 'Requests created in the selected date range',
            'icon' => 'fa-calendar-days',
            'iconWrap' => 'bg-slate-50 text-slate-500',
            'border' => 'border-l-slate-400',
        ],
        [
            'label' => 'Completion Rate',
            'value' => number_format($bookingMetrics['completion_rate'], 1) . '%',
            'description' => 'Completed bookings against total requests',
            'icon' => 'fa-circle-check',
            'iconWrap' => 'bg-emerald-50 text-emerald-600',
            'border' => 'border-l-emerald-500',
        ],
        [
            'label' => 'Revenue',
            'value' => '&#8369;' . number_format($revenueMetrics['total_revenue'], 0),
            'description' => 'Completed-booking revenue in this window',
            'icon' => 'fa-wallet',
            'iconWrap' => 'bg-cyan-50 text-cyan-600',
            'border' => 'border-l-cyan-400',
        ],
        [
            'label' => 'Average Rating',
            'value' => $customerSatisfaction['average_rating'] !== null ? number_format($customerSatisfaction['average_rating'], 1) . ' / 5' : 'N/A',
            'description' => number_format($customerSatisfaction['satisfaction_percentage'], 1) . '% positive share',
            'icon' => 'fa-star',
            'iconWrap' => 'bg-amber-50 text-amber-500',
            'border' => 'border-l-amber-400',
        ],
    ];

    $statusCards = [
        ['label' => 'Pending', 'value' => $bookingMetrics['pending'], 'badge' => 'bg-amber-100 text-amber-700'],
        ['label' => 'Confirmed', 'value' => $bookingMetrics['confirmed'], 'badge' => 'bg-accent-50 text-accent-700'],
        ['label' => 'In Progress', 'value' => $bookingMetrics['in_progress'], 'badge' => 'bg-primary-100 text-primary-700'],
        ['label' => 'Completed', 'value' => $bookingMetrics['completed'], 'badge' => 'bg-accent-100 text-accent-800'],
        ['label' => 'Cancelled', 'value' => $bookingMetrics['cancelled'], 'badge' => 'bg-danger-100 text-danger-700'],
    ];

    $chartLabels = $dailyTrends->pluck('label');
    $chartBookings = $dailyTrends->pluck('bookings');
    $chartCompleted = $dailyTrends->pluck('completed');
    $chartRevenue = $dailyTrends->pluck('revenue');
    $topService = $servicePopularity->first();
    $topStaff = $staffPerformance->first();
@endphp

<div class="admin-page-content cleanflow-page-shell space-y-6 p-6">
    <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8">
        <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl">
                <span class="cleanflow-kicker">
                    <i class="fas fa-chart-line"></i>
                    Business Analytics
                </span>
                <h2 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Track demand, revenue, and service quality without leaving the admin workspace.</h2>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-white/82 sm:text-base">
                    Review booking volume, payment collection, service mix, staff output, and customer satisfaction for the last {{ $dateRange }} days.
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <form method="GET" action="{{ route('admin.analytics') }}" class="flex flex-wrap items-center gap-3">
                        <label for="date_range" class="text-xs font-semibold uppercase tracking-[0.18em] text-white/70">Window</label>
                        <select id="date_range" name="date_range" class="rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-semibold text-white outline-hidden transition focus:border-white/30">
                            @foreach([7, 30, 60, 90] as $rangeOption)
                                <option value="{{ $rangeOption }}" @selected($dateRange === $rangeOption) class="text-slate-900">
                                    Last {{ $rangeOption }} days
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-bold text-slate-900 transition hover:bg-slate-100">
                            <i class="fas fa-sliders"></i>
                            Apply
                        </button>
                    </form>

                    <a href="{{ route('admin.analytics.export', ['date_range' => $dateRange]) }}" class="inline-flex items-center gap-2 rounded-full border border-white/20 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-white/10">
                        <i class="fas fa-file-arrow-down"></i>
                        Export CSV
                    </a>

                    <a href="{{ route('admin.reports') }}" class="inline-flex items-center gap-2 rounded-full border border-white/20 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-white/10">
                        <i class="fas fa-chart-column"></i>
                        Open Reports
                    </a>
                </div>
            </div>

            <div class="grid gap-3 rounded-3xl border border-white/18 bg-white/10 px-5 py-4 shadow-[0_18px_40px_rgba(15,23,42,0.15)] backdrop-blur sm:grid-cols-2 xl:min-w-85">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Total Revenue</div>
                    <div class="mt-2 text-4xl font-black leading-none">&#8369;{{ number_format($revenueMetrics['total_revenue'], 0) }}</div>
                    <div class="mt-2 text-sm text-white/72">{{ $bookingMetrics['completed'] }} completed booking{{ $bookingMetrics['completed'] === 1 ? '' : 's' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Customer Rating</div>
                    <div class="mt-2 text-4xl font-black leading-none">{{ $customerSatisfaction['average_rating'] !== null ? number_format($customerSatisfaction['average_rating'], 1) : 'N/A' }}</div>
                    <div class="mt-2 text-sm text-white/72">{{ $customerSatisfaction['total_ratings'] }} review{{ $customerSatisfaction['total_ratings'] === 1 ? '' : 's' }} in range</div>
                </div>
            </div>
        </div>
    </section>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach($overviewCards as $card)
            <div class="rounded-2xl border border-slate-100 border-l-4 {{ $card['border'] }} bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">{{ $card['label'] }}</div>
                        <div class="mt-2 text-3xl font-black leading-none text-slate-900">{!! $card['value'] !!}</div>
                        <div class="mt-2 text-sm text-slate-500">{{ $card['description'] }}</div>
                    </div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl {{ $card['iconWrap'] }}">
                        <i class="fas {{ $card['icon'] }}"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
        <section class="cleanflow-panel overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-lg font-extrabold text-slate-900">Revenue and Booking Status</h3>
                <p class="mt-1 text-sm text-slate-500">Payment collection health and booking-state distribution for the selected window.</p>
            </div>

            <div class="grid gap-6 px-6 py-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                <div class="space-y-4">
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-medium text-slate-600">Average Booking Value</span>
                            <span class="text-lg font-bold text-slate-900">&#8369;{{ number_format($revenueMetrics['average_booking_value'], 2) }}</span>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-medium text-slate-600">Paid Bookings</span>
                            <span class="text-lg font-bold text-emerald-600">{{ $revenueMetrics['paid_bookings'] }}</span>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-medium text-slate-600">Pending Payments</span>
                            <span class="text-lg font-bold text-amber-600">{{ $revenueMetrics['pending_payments'] }}</span>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-medium text-slate-600">Outstanding Revenue</span>
                            <span class="text-lg font-bold text-red-500">&#8369;{{ number_format($revenueMetrics['outstanding_revenue'], 2) }}</span>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm font-medium text-emerald-800">Collection Rate</span>
                            <span class="text-lg font-bold text-emerald-700">{{ number_format($revenueMetrics['payment_collection_rate'], 1) }}%</span>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="mb-3 text-xs font-extrabold uppercase tracking-[0.16em] text-slate-400">Booking Status Summary</div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach($statusCards as $status)
                            <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-xs">
                                <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">{{ $status['label'] }}</div>
                                <div class="mt-3 flex items-center justify-between gap-3">
                                    <div class="text-2xl font-black leading-none text-slate-900">{{ number_format($status['value']) }}</div>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $status['badge'] }}">
                                        {{ $status['label'] }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="cleanflow-panel overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-lg font-extrabold text-slate-900">Customer Satisfaction</h3>
                <p class="mt-1 text-sm text-slate-500">Rating distribution, top service demand, and strongest staff contributor in the same date range.</p>
            </div>

            <div class="space-y-5 px-6 py-6">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Average Rating</div>
                        <div class="mt-2 text-3xl font-black leading-none text-slate-900">{{ $customerSatisfaction['average_rating'] !== null ? number_format($customerSatisfaction['average_rating'], 1) : 'N/A' }}</div>
                        <div class="mt-2 text-sm text-slate-500">{{ $customerSatisfaction['total_ratings'] }} submitted review{{ $customerSatisfaction['total_ratings'] === 1 ? '' : 's' }}</div>
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Positive Share</div>
                        <div class="mt-2 text-3xl font-black leading-none text-slate-900">{{ number_format($customerSatisfaction['satisfaction_percentage'], 1) }}%</div>
                        <div class="mt-2 text-sm text-slate-500">Ratings at 4 or 5 stars</div>
                    </div>
                </div>

                <div>
                    <div class="mb-3 text-xs font-extrabold uppercase tracking-[0.16em] text-slate-400">Rating Distribution</div>
                    <div class="space-y-3">
                        @forelse($customerSatisfaction['distribution'] as $band)
                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="text-sm font-semibold text-slate-800">{{ $band['stars'] }} star{{ $band['stars'] === 1 ? '' : 's' }}</div>
                                    <div class="text-sm font-bold text-slate-700">{{ $band['count'] }}</div>
                                </div>
                                <div class="mt-2 text-xs text-slate-400">{{ number_format($band['percentage'], 1) }}% of submitted ratings</div>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-sm text-slate-500">
                                Rating distribution will appear here once completed bookings receive reviews.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-100 bg-white p-4">
                        <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Top Service</div>
                        @if($topService)
                            <div class="mt-2 text-lg font-bold text-slate-900">{{ $topService['name'] }}</div>
                            <div class="mt-1 text-sm text-slate-500">{{ $topService['bookings'] }} booking{{ $topService['bookings'] === 1 ? '' : 's' }} recorded</div>
                        @else
                            <div class="mt-2 text-sm text-slate-500">No service activity in this date range.</div>
                        @endif
                    </div>
                    <div class="rounded-2xl border border-slate-100 bg-white p-4">
                        <div class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Top Staff</div>
                        @if($topStaff)
                            <div class="mt-2 text-lg font-bold text-slate-900">{{ $topStaff['name'] }}</div>
                            <div class="mt-1 text-sm text-slate-500">{{ $topStaff['completed'] }} completed booking{{ $topStaff['completed'] === 1 ? '' : 's' }}</div>
                        @else
                            <div class="mt-2 text-sm text-slate-500">No assigned staff activity in this date range.</div>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="cleanflow-panel overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-lg font-extrabold text-slate-900">Service Popularity</h3>
                <p class="mt-1 text-sm text-slate-500">Which services drove the most bookings and revenue in the selected date range.</p>
            </div>

            <div class="overflow-x-auto px-6 py-4">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                            <th class="py-3">Service</th>
                            <th class="py-3 text-center">Bookings</th>
                            <th class="py-3 text-center">Completion</th>
                            <th class="py-3 text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($servicePopularity as $service)
                            <tr class="border-b border-slate-50 hover:bg-slate-50">
                                <td class="py-3">
                                    <div class="font-semibold text-slate-800">{{ $service['name'] }}</div>
                                    <div class="mt-1 text-xs text-slate-400">&#8369;{{ number_format($service['average_price'], 2) }} average ticket</div>
                                </td>
                                <td class="py-3 text-center font-semibold text-slate-800">{{ $service['bookings'] }}</td>
                                <td class="py-3 text-center">
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                        {{ number_format($service['completion_rate'], 1) }}%
                                    </span>
                                </td>
                                <td class="py-3 text-right font-semibold text-slate-800">&#8369;{{ number_format($service['revenue'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-sm text-slate-500">No service activity is available for this date range.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="cleanflow-panel overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-lg font-extrabold text-slate-900">Staff Performance</h3>
                <p class="mt-1 text-sm text-slate-500">Assigned workload, completion rate, and rating strength for staff with bookings in range.</p>
            </div>

            <div class="overflow-x-auto px-6 py-4">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                            <th class="py-3">Staff</th>
                            <th class="py-3 text-center">Assigned</th>
                            <th class="py-3 text-center">Completed</th>
                            <th class="py-3 text-center">Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($staffPerformance as $staff)
                            <tr class="border-b border-slate-50 hover:bg-slate-50">
                                <td class="py-3">
                                    <div class="font-semibold text-slate-800">{{ $staff['name'] }}</div>
                                    <div class="mt-1 text-xs text-slate-400">{{ $staff['barangay'] ?: 'Coverage not set' }}</div>
                                </td>
                                <td class="py-3 text-center font-semibold text-slate-800">{{ $staff['assigned'] }}</td>
                                <td class="py-3 text-center">
                                    <div class="font-semibold text-emerald-600">{{ $staff['completed'] }}</div>
                                    <div class="mt-1 text-[11px] text-slate-400">{{ number_format($staff['completion_rate'], 1) }}%</div>
                                </td>
                                <td class="py-3 text-center">
                                    @if($staff['average_rating'] !== null)
                                        <div class="font-semibold text-amber-500">{{ number_format($staff['average_rating'], 1) }}</div>
                                        <div class="mt-1 text-[11px] text-slate-400">{{ $staff['reviews'] }} review{{ $staff['reviews'] === 1 ? '' : 's' }}</div>
                                    @else
                                        <div class="text-xs text-slate-400">No ratings yet</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-sm text-slate-500">No staff assignments were recorded in this date range.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="cleanflow-panel overflow-hidden">
        <div class="border-b border-slate-100 px-6 py-5">
            <h3 class="text-lg font-extrabold text-slate-900">Daily Trends</h3>
            <p class="mt-1 text-sm text-slate-500">Booking demand, completions, and completed-booking revenue by day across the selected range.</p>
        </div>

        <div class="px-6 py-6">
            @if($dailyTrends->sum('bookings') > 0)
                <div class="h-80 md:h-96">
                    <canvas id="analytics-trends-chart"></canvas>
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                    Daily trend charts will appear once bookings are created in the selected date range.
                </div>
            @endif
        </div>
    </section>
</div>

@push('scripts')
    @if($dailyTrends->sum('bookings') > 0)
        <script src="{{ asset('vendor/chart.js/chart.umd.min.js') }}"></script>
        <script>
            const analyticsTrendCanvas = document.getElementById('analytics-trends-chart');

            if (analyticsTrendCanvas) {
                new Chart(analyticsTrendCanvas, {
                    type: 'line',
                    data: {
                        labels: @json($chartLabels),
                        datasets: [
                            {
                                label: 'Bookings',
                                data: @json($chartBookings),
                                borderColor: '#09637e',
                                backgroundColor: 'rgba(9, 99, 126, 0.10)',
                                tension: 0.35,
                                fill: true,
                                borderWidth: 2,
                                pointRadius: 2,
                                yAxisID: 'y',
                            },
                            {
                                label: 'Completed',
                                data: @json($chartCompleted),
                                borderColor: '#088395',
                                backgroundColor: 'rgba(8, 131, 149, 0.10)',
                                tension: 0.35,
                                fill: true,
                                borderWidth: 2,
                                pointRadius: 2,
                                yAxisID: 'y',
                            },
                            {
                                label: 'Revenue (PHP)',
                                data: @json($chartRevenue),
                                borderColor: '#7ab2b2',
                                backgroundColor: 'rgba(122, 178, 178, 0.14)',
                                tension: 0.35,
                                fill: true,
                                borderWidth: 2,
                                pointRadius: 2,
                                yAxisID: 'y1',
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    boxWidth: 8,
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                }
                            },
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Bookings'
                                }
                            },
                            y1: {
                                beginAtZero: true,
                                position: 'right',
                                grid: {
                                    drawOnChartArea: false,
                                },
                                title: {
                                    display: true,
                                    text: 'Revenue (PHP)'
                                }
                            }
                        }
                    }
                });
            }
        </script>
    @endif
@endpush
@endsection
