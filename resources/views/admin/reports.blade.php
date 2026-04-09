@extends('layouts.admin')
@section('title', 'Reports & Analytics')
@section('page-title', 'Reports & Analytics')
@section('page-subtitle', 'Performance, revenue, and activity overview')

@section('content')

@php
    $serviceBookingsTotal = $bookingsByType->sum('total');
@endphp

@if($invalidServiceBookings > 0)
<div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
    {{ $invalidServiceBookings }} booking {{ $invalidServiceBookings === 1 ? 'record was' : 'records were' }} excluded from service analytics because the stored service type no longer matches the active service catalog.
</div>
@endif

<div class="grid grid-cols-2 gap-4 mb-6 md:grid-cols-4">
    <div class="bg-white rounded-xl shadow p-5 border-l-4 border-emerald-500">
        <div class="text-sm text-gray-500 font-medium">Total Bookings</div>
        <div class="text-3xl font-bold text-gray-800 mt-1">{{ $totalBookings }}</div>
    </div>
    <div class="bg-white rounded-xl shadow p-5 border-l-4 border-green-500">
        <div class="text-sm text-gray-500 font-medium">Completed</div>
        <div class="text-3xl font-bold text-green-600 mt-1">{{ $completedBookings }}</div>
    </div>
    <div class="bg-white rounded-xl shadow p-5 border-l-4 border-yellow-500">
        <div class="text-sm text-gray-500 font-medium">Pending</div>
        <div class="text-3xl font-bold text-yellow-600 mt-1">{{ $pendingBookings }}</div>
    </div>
    <div class="bg-white rounded-xl shadow p-5 border-l-4 border-red-500">
        <div class="text-sm text-gray-500 font-medium">Cancelled</div>
        <div class="text-3xl font-bold text-red-600 mt-1">{{ $cancelledBookings }}</div>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-3">
    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow p-6 text-white col-span-1">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-green-100 text-sm font-medium">Total Revenue</div>
                <div class="text-4xl font-bold mt-1">&#8369;{{ number_format($totalRevenue, 2) }}</div>
                <div class="text-green-200 text-xs mt-1">From completed bookings</div>
            </div>
            <i class="fas fa-coins text-5xl text-green-300"></i>
        </div>
    </div>
    @foreach($revenueByType as $rev)
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center justify-between mb-2">
            <div class="text-sm font-semibold text-gray-600">{{ $rev->service_name }}</div>
            <span class="text-xs bg-emerald-100 text-emerald-600 px-2 py-1 rounded-full">{{ $rev->total }} jobs</span>
        </div>
        <div class="text-2xl font-bold text-gray-800">&#8369;{{ number_format($rev->revenue, 2) }}</div>
        <div class="text-xs text-gray-400 mt-1">Revenue from completed</div>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2">
    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            <i class="fas fa-chart-pie text-emerald-500 mr-2"></i>Bookings by Service Type
        </h3>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100">
                    <th class="text-left py-2 text-gray-500 font-medium">Service Type</th>
                    <th class="text-center py-2 text-gray-500 font-medium">Total</th>
                    <th class="text-right py-2 text-gray-500 font-medium">Percentage</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bookingsByType as $type)
                <tr class="border-b border-gray-50">
                    <td class="py-3 capitalize font-medium text-gray-700">
                        @if($type->service_type == 'basic')
                            <i class="fas fa-home text-emerald-500 mr-2"></i>
                        @elseif($type->service_type == 'deep')
                            <i class="fas fa-soap text-orange-500 mr-2"></i>
                        @else
                            <i class="fas fa-truck-moving text-green-500 mr-2"></i>
                        @endif
                        {{ $type->service_name }}
                    </td>
                    <td class="py-3 text-center font-bold text-gray-800">{{ $type->total }}</td>
                    <td class="py-3 text-right">
                        <span class="bg-emerald-100 text-emerald-700 px-2 py-1 rounded-full text-xs font-semibold">
                            {{ $serviceBookingsTotal > 0 ? round(($type->total / $serviceBookingsTotal) * 100, 1) : 0 }}%
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="py-6 text-center text-gray-400">Service analytics will appear here once bookings use active service catalog entries.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            <i class="fas fa-chart-bar text-purple-500 mr-2"></i>Booking Status Summary
        </h3>
        <div class="space-y-4">
            @php
                $statuses = [
                    ['label' => 'Completed', 'count' => $statusSummary['completed'], 'color' => 'bg-green-500'],
                    ['label' => 'Confirmed', 'count' => $statusSummary['confirmed'], 'color' => 'bg-emerald-600'],
                    ['label' => 'Pending', 'count' => $statusSummary['pending'], 'color' => 'bg-yellow-500'],
                    ['label' => 'In Progress', 'count' => $statusSummary['in_progress'], 'color' => 'bg-purple-500'],
                    ['label' => 'Cancelled', 'count' => $statusSummary['cancelled'], 'color' => 'bg-red-500'],
                ];
            @endphp
            @foreach($statuses as $status)
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-600">{{ $status['label'] }}</span>
                    <span class="font-semibold text-gray-800">{{ $status['count'] }}</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="{{ $status['color'] }} h-2 rounded-full"
                        style="width: {{ $totalBookings > 0 ? ($status['count'] / $totalBookings) * 100 : 0 }}%">
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">
        <i class="fas fa-users text-orange-500 mr-2"></i>Staff Performance
    </h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100">
                    <th class="text-left py-3 text-gray-500 font-medium">Staff Name</th>
                    <th class="text-left py-3 text-gray-500 font-medium">Barangay</th>
                    <th class="text-center py-3 text-gray-500 font-medium">Assigned</th>
                    <th class="text-center py-3 text-gray-500 font-medium">Completed</th>
                    <th class="text-center py-3 text-gray-500 font-medium">Completion Rate</th>
                    <th class="text-center py-3 text-gray-500 font-medium">Avg Rating</th>
                    <th class="text-center py-3 text-gray-500 font-medium">Reviews</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staffPerformance as $staff)
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="py-3 font-medium text-gray-800">
                        {{ $staff->first_name }} {{ $staff->last_name }}
                    </td>
                    <td class="py-3 text-gray-600">{{ ucfirst($staff->barangay) }}</td>
                    <td class="py-3 text-center text-gray-800 font-semibold">{{ $staff->total_assigned }}</td>
                    <td class="py-3 text-center text-green-600 font-semibold">{{ $staff->total_completed }}</td>
                    <td class="py-3 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                            {{ $staff->completion_rate >= 70 ? 'bg-green-100 text-green-700' :
                               ($staff->completion_rate >= 40 ? 'bg-yellow-100 text-yellow-700' :
                               'bg-red-100 text-red-700') }}">
                            {{ $staff->completion_rate }}%
                        </span>
                    </td>
                    <td class="py-3 text-center">
                        @if($staff->avg_rating)
                            <div class="flex items-center justify-center gap-1">
                                <i class="fas fa-star text-yellow-400 text-xs"></i>
                                <span class="font-semibold text-gray-800">{{ $staff->avg_rating }}</span>
                            </div>
                        @else
                            <span class="text-gray-400 text-xs">No reviews yet</span>
                        @endif
                    </td>
                    <td class="py-3 text-center text-gray-600">{{ $staff->total_ratings }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-6 text-center text-gray-400">No staff performance data is available yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white rounded-xl shadow p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-800">
            <i class="fas fa-history text-gray-500 mr-2"></i>Recent Service History
        </h3>
        <span class="text-xs text-gray-400">Last 10 bookings</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-100">
                    <th class="text-left py-3 text-gray-500 font-medium">Booking #</th>
                    <th class="text-left py-3 text-gray-500 font-medium">Client</th>
                    <th class="text-left py-3 text-gray-500 font-medium">Service</th>
                    <th class="text-left py-3 text-gray-500 font-medium">Date</th>
                    <th class="text-left py-3 text-gray-500 font-medium">Staff</th>
                    <th class="text-right py-3 text-gray-500 font-medium">Price</th>
                    <th class="text-center py-3 text-gray-500 font-medium">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentBookings as $booking)
                <tr class="border-b border-gray-50 hover:bg-gray-50">
                    <td class="py-3 font-mono text-emerald-600 font-semibold">
                        CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}
                    </td>
                    <td class="py-3 text-gray-800">{{ $booking->user->first_name }} {{ $booking->user->last_name }}</td>
                    <td class="py-3 text-gray-600">{{ $booking->service_label }}</td>
                    <td class="py-3 text-gray-600">{{ \Carbon\Carbon::parse($booking->scheduled_date)->format('M d, Y') }}</td>
                    <td class="py-3 text-gray-600">
                        {{ $booking->staff ? $booking->staff->first_name . ' ' . $booking->staff->last_name : 'Not assigned' }}
                    </td>
                    <td class="py-3 text-right font-semibold text-gray-800">&#8369;{{ number_format($booking->price, 2) }}</td>
                    <td class="py-3 text-center">
                        @php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-700',
                                'confirmed' => 'bg-emerald-100 text-emerald-700',
                                'in_progress' => 'bg-purple-100 text-purple-700',
                                'completed' => 'bg-green-100 text-green-700',
                                'cancelled' => 'bg-red-100 text-red-700',
                            ];
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $statusColors[$booking->status] ?? 'bg-gray-100 text-gray-700' }}">
                            {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="py-6 text-center text-gray-400">Recent service history will appear here once bookings are recorded.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="flex justify-end mb-6">
    <button onclick="window.print()"
        class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2 rounded-lg font-medium transition flex items-center gap-2">
        <i class="fas fa-print"></i> Print / Export Report
    </button>
</div>

@endsection
