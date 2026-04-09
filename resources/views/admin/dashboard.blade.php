@extends('layouts.admin')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Monitor bookings, customer readiness, staff activity, and business performance')

@section('content')
@php
    $dashboardNow = \Illuminate\Support\Carbon::now(config('cleanflow.attendance_timezone', config('app.timezone')));
    $dashboardStats = [
        'total_bookings' => \App\Models\Booking::count(),
        'pending_bookings' => \App\Models\Booking::where('status', 'pending')->count(),
        'completed_bookings' => \App\Models\Booking::where('status', 'completed')->count(),
        'in_progress_bookings' => \App\Models\Booking::where('status', 'in_progress')->count(),
        'customers' => \App\Models\User::where('role', 'client')->count(),
        'staff' => \App\Models\User::where('role', 'staff')->count(),
        'verified_customers' => \App\Models\User::where('role', 'client')->whereNotNull('email_verified_at')->count(),
        'active_devices' => \App\Models\Device::where('is_active', true)->count(),
    ];

    $topStatCards = [
        [
            'label' => 'Total Bookings',
            'value' => number_format($dashboardStats['total_bookings']),
            'description' => 'All recorded service requests in the system.',
            'icon' => 'fa-calendar-days',
            'border' => 'border-l-slate-400',
            'iconBackground' => 'bg-slate-50',
            'iconColor' => 'text-slate-500',
        ],
        [
            'label' => 'Pending Bookings',
            'value' => number_format($dashboardStats['pending_bookings']),
            'description' => 'Requests awaiting confirmation, staffing, or action.',
            'icon' => 'fa-clock',
            'border' => 'border-l-amber-400',
            'iconBackground' => 'bg-slate-50',
            'iconColor' => 'text-amber-600',
        ],
        [
            'label' => 'Completed Bookings',
            'value' => number_format($dashboardStats['completed_bookings']),
            'description' => 'Bookings finished and ready for reporting.',
            'icon' => 'fa-circle-check',
            'border' => 'border-l-emerald-500',
            'iconBackground' => 'bg-slate-50',
            'iconColor' => 'text-emerald-600',
        ],
        [
            'label' => 'In Progress',
            'value' => number_format($dashboardStats['in_progress_bookings']),
            'description' => 'Active jobs that are currently being delivered.',
            'icon' => 'fa-spinner',
            'border' => 'border-l-purple-400',
            'iconBackground' => 'bg-slate-50',
            'iconColor' => 'text-purple-600',
        ],
    ];

    $bottomStatCards = [
        [
            'label' => 'Total Customers',
            'value' => number_format($dashboardStats['customers']),
            'description' => 'Registered client accounts across the platform.',
            'icon' => 'fa-users',
            'border' => 'border-l-blue-400',
            'iconBackground' => 'bg-slate-50',
            'iconColor' => 'text-blue-600',
        ],
        [
            'label' => 'Staff Members',
            'value' => number_format($dashboardStats['staff']),
            'description' => 'Team members available for assignment and delivery.',
            'icon' => 'fa-user-gear',
            'border' => 'border-l-cyan-400',
            'iconBackground' => 'bg-slate-50',
            'iconColor' => 'text-cyan-600',
        ],
        [
            'label' => 'Verified Customers',
            'value' => number_format($dashboardStats['verified_customers']),
            'description' => 'Accounts currently ready for verified customer access.',
            'icon' => 'fa-shield-halved',
            'border' => 'border-l-teal-400',
            'iconBackground' => 'bg-slate-50',
            'iconColor' => 'text-slate-500',
        ],
        [
            'label' => 'Active Devices',
            'value' => number_format($dashboardStats['active_devices']),
            'description' => 'Attendance devices marked active for operations.',
            'icon' => 'fa-microchip',
            'border' => 'border-l-indigo-400',
            'iconBackground' => 'bg-slate-50',
            'iconColor' => 'text-indigo-600',
        ],
    ];

    $quickActions = [
        [
            'route' => route('admin.bookings'),
            'title' => 'Manage Bookings',
            'description' => 'Review requests, staffing, and status updates.',
            'icon' => 'fa-calendar-check',
            'iconBackground' => 'bg-blue-50',
            'iconColor' => 'text-blue-600',
        ],
        [
            'route' => route('admin.staff.index'),
            'title' => 'Manage Staff',
            'description' => 'Check staffing coverage and team readiness.',
            'icon' => 'fa-user-gear',
            'iconBackground' => 'bg-orange-50',
            'iconColor' => 'text-orange-600',
        ],
        [
            'route' => route('admin.customers'),
            'title' => 'Manage Customers',
            'description' => 'Review accounts, verification, and booking history.',
            'icon' => 'fa-users',
            'iconBackground' => 'bg-emerald-50',
            'iconColor' => 'text-emerald-600',
        ],
        [
            'route' => route('admin.reports'),
            'title' => 'Review Reports',
            'description' => 'Open revenue, service, and staff summaries.',
            'icon' => 'fa-chart-column',
            'iconBackground' => 'bg-purple-50',
            'iconColor' => 'text-purple-600',
        ],
    ];

    $statusClasses = [
        'pending' => 'bg-amber-100 text-amber-700',
        'completed' => 'bg-emerald-100 text-emerald-700',
        'confirmed' => 'bg-blue-100 text-blue-700',
        'in_progress' => 'bg-purple-100 text-purple-700',
        'cancelled' => 'bg-red-100 text-red-600',
    ];

    $rankClasses = [
        0 => 'bg-yellow-400 text-white',
        1 => 'bg-slate-300 text-slate-700',
        2 => 'bg-orange-300 text-white',
    ];
@endphp

<div class="space-y-5 bg-slate-50 p-6" style="font-family: 'DM Sans', sans-serif;">

    <div style="background: linear-gradient(135deg, #0F6E56 0%, #1D9E75 55%, #0891b2 100%); border-radius: 18px; padding: 2rem 2.25rem; position: relative; overflow: hidden; box-shadow: 0 14px 40px rgba(15, 110, 86, 0.18);">
        <div style="position: absolute; right: -20px; top: -40px; width: 200px; height: 200px; border-radius: 50%; background: rgba(255,255,255,0.07);"></div>
        <div style="position: absolute; right: 120px; bottom: -60px; width: 150px; height: 150px; border-radius: 50%; background: rgba(255,255,255,0.05);"></div>

        <div class="admin-welcome-inner flex items-center justify-between gap-6">
            <div style="position: relative; z-index: 1; max-width: 680px;">
                <div style="color: rgba(255,255,255,0.8); font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px;">{{ $dashboardNow->format('l, F d, Y') }}</div>
                <div style="color: white; font-size: 28px; font-weight: 800; line-height: 1.15;">
                    Good {{ $dashboardNow->hour < 12 ? 'morning' : ($dashboardNow->hour < 17 ? 'afternoon' : 'evening') }}, {{ auth()->user()->first_name }}.
                </div>
                <div style="color: rgba(255,255,255,0.78); font-size: 14px; line-height: 1.7; margin-top: 10px;">
                    Review the current booking queue, customer verification readiness, staff activity, and revenue performance from one operational snapshot.
                </div>
            </div>

            <div style="position: relative; z-index: 1; min-width: 220px; background: rgba(255,255,255,0.14); border: 1px solid rgba(255,255,255,0.24); border-radius: 16px; padding: 16px 18px;">
                <div style="color: rgba(255,255,255,0.82); font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em;">Total Revenue</div>
                <div style="color: white; font-size: 30px; font-weight: 800; margin-top: 8px;">&#8369;{{ number_format($totalEarnings, 0) }}</div>
                <div style="color: rgba(255,255,255,0.7); font-size: 12px; margin-top: 6px;">Completed booking revenue to date.</div>
            </div>
        </div>
    </div>

    <div class="admin-stats-row-1 grid items-stretch gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach($topStatCards as $card)
        <div class="h-full rounded-2xl border border-slate-100 border-l-4 {{ $card['border'] }} bg-white p-5 shadow-sm">
            <div class="flex h-full flex-col">
                <div class="flex items-start justify-between gap-4">
                    <div class="mb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $card['label'] }}</div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl {{ $card['iconBackground'] }} {{ $card['iconColor'] }} text-lg">
                        <i class="fas {{ $card['icon'] }}"></i>
                    </div>
                </div>
                <div class="mt-3 text-3xl font-bold text-slate-800">{{ $card['value'] }}</div>
                <div class="mt-2 text-sm text-slate-400">{{ $card['description'] }}</div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="admin-stats-row-2 grid items-stretch gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach($bottomStatCards as $card)
        <div class="h-full rounded-2xl border border-slate-100 border-l-4 {{ $card['border'] }} bg-white p-5 shadow-sm">
            <div class="flex h-full flex-col">
                <div class="flex items-start justify-between gap-4">
                    <div class="mb-1 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $card['label'] }}</div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl {{ $card['iconBackground'] }} {{ $card['iconColor'] }} text-lg">
                        <i class="fas {{ $card['icon'] }}"></i>
                    </div>
                </div>
                <div class="mt-3 text-3xl font-bold text-slate-800">{{ $card['value'] }}</div>
                <div class="mt-2 text-sm text-slate-400">{{ $card['description'] }}</div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="admin-bottom-grid grid gap-5 xl:grid-cols-3">

        <div class="col-span-2 overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                <div>
                    <div class="text-lg font-bold text-slate-800">Recent Booking Activity</div>
                    <div class="mt-1 text-sm text-slate-500">A quick view of the latest service requests entering the booking queue.</div>
                </div>
                <a href="{{ route('admin.bookings') }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">
                    <i class="fas fa-arrow-right"></i>
                    Open Booking Queue
                </a>
            </div>

            <div class="admin-table-wrap overflow-hidden">
                <table class="w-full table-fixed overflow-hidden border-collapse text-sm">
                    <colgroup>
                        <col class="w-28">
                        <col class="w-36">
                        <col class="w-48">
                        <col class="w-28">
                    </colgroup>
                    <thead>
                        <tr class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">
                            <th class="w-28 border-b border-slate-100 px-6 pb-3 pt-3">Booking</th>
                            <th class="w-36 border-b border-slate-100 px-6 pb-3 pt-3">Customer</th>
                            <th class="w-48 border-b border-slate-100 px-6 pb-3 pt-3">Service</th>
                            <th class="w-28 border-b border-slate-100 px-6 pb-3 pt-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($recentBookings as $booking)
                        <tr class="cursor-pointer transition hover:bg-slate-50" onclick="window.location='{{ route('bookings.show', $booking->id) }}'">
                            <td class="px-6 py-4 align-top">
                                <a href="{{ route('bookings.show', $booking->id) }}" class="block truncate font-mono text-sm font-semibold text-emerald-600 transition hover:text-emerald-700" onclick="event.stopPropagation()">
                                    CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}
                                </a>
                                <div class="mt-1 text-xs text-slate-400">{{ optional($booking->created_at)->diffForHumans() }}</div>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <div class="truncate text-sm font-medium text-slate-800">{{ trim(($booking->user->first_name ?? '') . ' ' . ($booking->user->last_name ?? '')) ?: 'Unknown customer' }}</div>
                                <div class="mt-1 truncate text-xs text-slate-400">{{ $booking->barangay ?: 'Barangay not set' }}</div>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <div class="whitespace-normal break-words text-sm text-slate-700">{{ $booking->service_label }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ \Carbon\Carbon::parse($booking->scheduled_date)->format('M d, Y') }}</div>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $statusClasses[$booking->status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ ucwords(str_replace('_', ' ', $booking->status)) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="text-sm font-semibold text-slate-600">No recent bookings yet</div>
                                <div class="mt-1 text-xs text-slate-400">New customer requests will appear here once the booking queue becomes active.</div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-span-1 space-y-5">
            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                <div class="text-lg font-bold text-slate-800">Quick Actions</div>
                <div class="mt-1 text-sm text-slate-500">Fast access to the admin workflows used most often during operations.</div>

                <div class="admin-quick-grid mt-4 grid gap-3 md:grid-cols-2">
                    @foreach($quickActions as $action)
                    <a href="{{ $action['route'] }}" class="block h-full rounded-2xl border border-slate-100 bg-white p-4 shadow-sm transition hover:border-slate-200 hover:shadow-md">
                        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl {{ $action['iconBackground'] }} {{ $action['iconColor'] }}">
                            <i class="fas {{ $action['icon'] }}"></i>
                        </div>
                        <div class="text-sm font-semibold text-slate-800">{{ $action['title'] }}</div>
                        <div class="mt-1 text-xs leading-relaxed text-slate-500">{{ $action['description'] }}</div>
                    </a>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                <div class="text-lg font-bold text-slate-800">Top Rated Staff</div>
                <div class="mt-1 text-sm text-slate-500">A ranking based on customer review data from completed bookings.</div>
                <div class="mb-3 mt-4 text-xs uppercase tracking-wider text-slate-400">Top 3</div>

                <div class="space-y-1">
                    @forelse($topStaff as $index => $member)
                    <div class="flex items-center gap-3 border-b border-slate-50 py-3 last:border-0">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold {{ $rankClasses[$index] ?? 'bg-slate-200 text-slate-700' }}">
                                {{ $index + 1 }}
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-800">{{ $member->first_name }} {{ $member->last_name }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ $member->total_ratings }} review{{ $member->total_ratings === 1 ? '' : 's' }}</div>
                            </div>
                        </div>

                        @if($member->avg_rating)
                        <div class="ml-auto flex items-center gap-2 text-sm font-bold text-amber-400">
                            <i class="fas fa-star"></i>
                            <span>{{ $member->avg_rating }}</span>
                        </div>
                        @else
                        <span class="ml-auto text-xs italic text-slate-400">No reviews yet</span>
                        @endif
                    </div>
                    @empty
                    <div class="py-4 text-center text-xs text-slate-400">
                        Staff review data will appear here after completed bookings receive ratings.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
