@extends('layouts.client')
@section('title', 'My Bookings - Home Cleaning Service')

@section('content')
@php
    $totalCount = $bookings->total();
    $pendingCount = $bookings->getCollection()->where('status', 'pending')->count();
    $confirmedCount = $bookings->getCollection()->where('status', 'confirmed')->count();
    $inProgressCount = $bookings->getCollection()->where('status', 'in_progress')->count();
    $completedCount = $bookings->getCollection()->where('status', 'completed')->count();
    $statusClasses = [
        'pending' => 'bg-yellow-100 text-yellow-700',
        'confirmed' => 'bg-blue-100 text-blue-700',
        'in_progress' => 'bg-orange-100 text-orange-700',
        'completed' => 'bg-green-100 text-green-700',
        'cancelled' => 'bg-red-100 text-red-700',
    ];
    $stats = [
        [
            'label' => 'Total',
            'count' => $totalCount,
            'cardClass' => 'border-l-4 border-blue-400 bg-blue-50/50',
            'iconBackground' => '#dbeafe',
            'iconColor' => '#2563eb',
            'icon' => 'fa-calendar-days',
        ],
        [
            'label' => 'Pending',
            'count' => $pendingCount,
            'cardClass' => 'border-l-4 border-yellow-400 bg-yellow-50/50',
            'iconBackground' => '#fef3c7',
            'iconColor' => '#ca8a04',
            'icon' => 'fa-clock',
        ],
        [
            'label' => 'Confirmed',
            'count' => $confirmedCount,
            'cardClass' => 'border-l-4 border-green-400 bg-green-50/50',
            'iconBackground' => '#dcfce7',
            'iconColor' => '#16a34a',
            'icon' => 'fa-circle-check',
        ],
        [
            'label' => 'In Progress',
            'count' => $inProgressCount,
            'cardClass' => 'border-l-4 border-purple-400 bg-purple-50/50',
            'iconBackground' => '#f3e8ff',
            'iconColor' => '#9333ea',
            'icon' => 'fa-spinner',
        ],
        [
            'label' => 'Completed',
            'count' => $completedCount,
            'cardClass' => 'border-l-4 border-emerald-400 bg-emerald-50/50',
            'iconBackground' => '#d1fae5',
            'iconColor' => '#059669',
            'icon' => 'fa-check-double',
        ],
    ];
@endphp

<div class="min-h-[calc(100vh-81px)] bg-gray-50 px-6 py-8" style="font-family: 'DM Sans', sans-serif;">
    <div class="mx-auto max-w-6xl space-y-6">

        @if(session('success'))
        <div class="flex items-start gap-3 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-green-700 shadow-sm">
            <i class="fas fa-circle-check mt-0.5"></i>
            <div>
                <div class="text-sm font-semibold">Action completed</div>
                <div class="text-sm">{{ session('success') }}</div>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-red-700 shadow-sm">
            <i class="fas fa-circle-xmark mt-0.5"></i>
            <div>
                <div class="text-sm font-semibold">Action blocked</div>
                <div class="text-sm">{{ session('error') }}</div>
            </div>
        </div>
        @endif

        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">My Bookings</h1>
                <p class="mt-1 text-sm text-slate-500">Review your booking history, current requests, and service updates.</p>
            </div>
            <a href="{{ route('bookings.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-green-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-green-700">
                <i class="fas fa-plus"></i>
                New Booking
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach($stats as $stat)
            <div class="rounded-2xl border border-slate-200 px-5 py-5 shadow-sm {{ $stat['cardClass'] }}">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $stat['label'] }}</div>
                        <div class="mt-3 text-4xl font-bold text-slate-900">{{ $stat['count'] }}</div>
                    </div>
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl" style="background: {{ $stat['iconBackground'] }}; color: {{ $stat['iconColor'] }};">
                        <i class="fas {{ $stat['icon'] }}"></i>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-gray-100 px-6 py-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Booking History</h2>
                    <p class="mt-1 text-sm text-slate-500">All recorded cleaning requests tied to your account.</p>
                </div>
                <div class="text-sm text-slate-500">{{ $bookings->total() }} booking{{ $bookings->total() === 1 ? '' : 's' }}</div>
            </div>

            @if($bookings->count())
            <div class="overflow-x-auto">
                <table class="min-w-[980px] w-full border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-slate-50">
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Booking #</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Service</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Address</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Schedule</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Staff</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bookings as $booking)
                        <tr class="border-b border-gray-100 transition hover:bg-green-50/30">
                            <td class="px-6 py-4 align-top">
                                <a href="{{ route('bookings.show', $booking->id) }}" class="font-mono text-sm font-bold text-green-600 hover:underline">
                                    CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}
                                </a>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-100 text-green-700">
                                        <i class="fas fa-broom text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-900">{{ $booking->service_label }}</div>
                                        <div class="text-xs text-slate-500">Cleaning service</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <div class="font-medium text-slate-900">{{ $booking->street_address }}</div>
                                <div class="text-xs text-slate-500">{{ ucfirst($booking->barangay) }}</div>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <div class="font-semibold text-slate-900">{{ \Carbon\Carbon::parse($booking->scheduled_date)->format('M d, Y') }}</div>
                                <div class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($booking->scheduled_time)->format('h:i A') }}</div>
                            </td>
                            <td class="px-6 py-4 align-top">
                                @if($booking->staff)
                                <div class="flex items-center gap-2">
                                    <div class="flex h-7 w-7 items-center justify-center rounded-full bg-purple-100 text-[11px] font-bold text-purple-700">
                                        {{ strtoupper(substr($booking->staff->first_name, 0, 1)) }}
                                    </div>
                                    <span class="text-sm text-slate-700">{{ $booking->staff->first_name }}</span>
                                </div>
                                @else
                                <span class="text-sm italic text-slate-400">Not assigned</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 align-top">
                                <span class="font-semibold text-slate-900">&#8369;{{ number_format($booking->price, 0) }}</span>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$booking->status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ ucwords(str_replace('_', ' ', $booking->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('bookings.show', $booking->id) }}" class="text-sm font-medium text-green-600 hover:underline">View &rarr;</a>
                                    @if($booking->status === 'pending' && !$booking->staff_id)
                                    <form action="{{ route('bookings.cancel', $booking->id) }}" method="POST" onsubmit="return confirm('Cancel this booking?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="flex h-8 w-8 items-center justify-center rounded-full bg-red-50 text-red-400 transition hover:bg-red-100" aria-label="Cancel booking">
                                            <i class="fas fa-xmark text-xs"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($bookings->hasPages())
            <div class="border-t border-gray-100 px-6 py-4">
                {{ $bookings->links('pagination::tailwind') }}
            </div>
            @endif
            @else
            <div class="px-6 py-14 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-green-50 text-green-600">
                    <i class="fas fa-broom text-xl"></i>
                </div>
                <h3 class="mt-4 text-lg font-bold text-slate-900">No bookings to show yet</h3>
                <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">Your submitted booking requests will appear here once you create one.</p>
                <a href="{{ route('bookings.create') }}" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-green-600 px-5 py-2.5 text-sm font-medium text-white transition hover:bg-green-700">
                    <i class="fas fa-plus"></i>
                    Book Your First Service
                </a>
            </div>
            @endif
        </section>
    </div>
</div>
@endsection
