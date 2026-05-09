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
        'pending' => 'border border-amber-200 bg-amber-50 text-amber-700',
        'confirmed' => 'border border-accent-200 bg-accent-50 text-accent-700',
        'in_progress' => 'border border-primary-200 bg-primary-50 text-primary-700',
        'completed' => 'border border-accent-300 bg-accent-100 text-accent-800',
        'cancelled' => 'border border-danger-200 bg-danger-50 text-danger-700',
    ];

    $paymentStatusClasses = [
        'pending' => 'border border-amber-200 bg-amber-50 text-amber-700',
        'paid' => 'border border-accent-300 bg-accent-100 text-accent-800',
    ];

    $stats = [
        [
            'label' => 'Total bookings',
            'count' => $totalCount,
            'cardClasses' => 'border-l-4 border-secondary-300 bg-secondary-50/80',
            'iconClasses' => 'bg-secondary-100 text-secondary-700',
            'icon' => 'fa-calendar-days',
        ],
        [
            'label' => 'Pending',
            'count' => $pendingCount,
            'cardClasses' => 'border-l-4 border-amber-300 bg-amber-50/80',
            'iconClasses' => 'bg-amber-100 text-amber-700',
            'icon' => 'fa-clock',
        ],
        [
            'label' => 'Confirmed',
            'count' => $confirmedCount,
            'cardClasses' => 'border-l-4 border-accent-300 bg-accent-50/80',
            'iconClasses' => 'bg-accent-100 text-accent-700',
            'icon' => 'fa-circle-check',
        ],
        [
            'label' => 'In progress',
            'count' => $inProgressCount,
            'cardClasses' => 'border-l-4 border-primary-300 bg-primary-50/80',
            'iconClasses' => 'bg-primary-100 text-primary-700',
            'icon' => 'fa-spinner',
        ],
        [
            'label' => 'Completed',
            'count' => $completedCount,
            'cardClasses' => 'border-l-4 border-accent-400 bg-accent-100/80',
            'iconClasses' => 'bg-accent-200 text-accent-800',
            'icon' => 'fa-check-double',
        ],
    ];

    $latestBooking = $bookings->first();
@endphp

<div class="cleanflow-page-shell min-h-[calc(100vh-81px)] px-4 py-6 sm:px-6 sm:py-8">
    <div class="mx-auto max-w-6xl space-y-6">
        @if (session('success'))
            <div class="cleanflow-alert cleanflow-alert--success flex items-start gap-3">
                <i class="fas fa-circle-check mt-0.5 text-base"></i>
                <div>
                    <p class="text-sm font-semibold">Booking update saved.</p>
                    <p class="mt-1 text-sm text-emerald-800/80">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="cleanflow-alert cleanflow-alert--error flex items-start gap-3">
                <i class="fas fa-circle-xmark mt-0.5 text-base"></i>
                <div>
                    <p class="text-sm font-semibold">Booking change unavailable.</p>
                    <p class="mt-1 text-sm text-red-800/80">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        @if (session('warning'))
            <div class="cleanflow-alert cleanflow-alert--warning flex items-start gap-3">
                <i class="fas fa-triangle-exclamation mt-0.5 text-base"></i>
                <div>
                    <p class="text-sm font-semibold">Preferred cleaner update.</p>
                    <p class="mt-1 text-sm text-amber-800/80">{{ session('warning') }}</p>
                </div>
            </div>
        @endif

        @if (session('info'))
            <div class="cleanflow-alert cleanflow-alert--info flex items-start gap-3">
                <i class="fas fa-circle-info mt-0.5 text-base"></i>
                <div>
                    <p class="text-sm font-semibold">Booking note.</p>
                    <p class="mt-1 text-sm text-blue-800/80">{{ session('info') }}</p>
                </div>
            </div>
        @endif

        <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8 lg:px-10">
            <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl space-y-4">
                    <span class="cleanflow-kicker">
                        <i class="fas fa-clipboard-list text-[0.75rem]"></i>
                        Client bookings
                    </span>
                    <div class="space-y-3">
                        <h1 class="max-w-2xl text-3xl font-black tracking-tight sm:text-4xl">
                            Track every booking from request to completion
                        </h1>
                        <p class="max-w-2xl text-sm leading-7 text-white/80 sm:text-base">
                            Review your cleaning history, follow the latest schedule updates, and open any booking for
                            full payment, staff, and proof-of-service details.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3 text-sm text-white/85">
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-layer-group text-xs"></i>
                            {{ $totalCount }} total booking{{ $totalCount === 1 ? '' : 's' }}
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-wand-sparkles text-xs"></i>
                            {{ $completedCount }} completed service{{ $completedCount === 1 ? '' : 's' }}
                        </span>
                        @if ($latestBooking)
                            <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                                <i class="fas fa-calendar-day text-xs"></i>
                                Latest: {{ \Carbon\Carbon::parse($latestBooking->scheduled_date)->format('M d, Y') }}
                            </span>
                        @endif
                    </div>
                </div>

                <a href="{{ route('bookings.create') }}" class="cleanflow-ghost-button self-start xl:self-auto">
                    <i class="fas fa-plus text-xs"></i>
                    New booking
                </a>
            </div>
        </section>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ($stats as $stat)
                <section class="cleanflow-panel px-5 py-5 {{ $stat['cardClasses'] }}">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $stat['label'] }}</p>
                            <strong class="mt-3 block text-4xl font-black tracking-tight text-slate-900">{{ $stat['count'] }}</strong>
                        </div>
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl {{ $stat['iconClasses'] }}">
                            <i class="fas {{ $stat['icon'] }}"></i>
                        </span>
                    </div>
                </section>
            @endforeach
        </div>

        <section class="cleanflow-panel overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Booking history</h2>
                    <p class="mt-1 text-sm text-slate-500">Every request tied to your account, including schedule, payment, and cleaner updates.</p>
                </div>
                <div class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
                    {{ $bookings->total() }} record{{ $bookings->total() === 1 ? '' : 's' }}
                </div>
            </div>

            @if ($bookings->count())
                <div class="overflow-x-auto">
                    <table class="min-w-[1020px] w-full border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/85">
                                <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">Booking #</th>
                                <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">Service</th>
                                <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">Address</th>
                                <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">Schedule</th>
                                <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">Cleaner</th>
                                <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">Price</th>
                                <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">Status</th>
                                <th class="px-6 py-3 text-center text-[11px] font-bold uppercase tracking-[0.16em] text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bookings as $booking)
                                <tr class="border-b border-slate-100 transition hover:bg-emerald-50/35">
                                    <td class="px-6 py-4 align-top">
                                        <a href="{{ route('bookings.show', $booking->id) }}" class="font-mono text-sm font-bold text-emerald-600 hover:underline">
                                            CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 align-top">
                                        <div class="flex items-start gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                                                <i class="fas fa-broom text-sm"></i>
                                            </div>
                                            <div>
                                                <div class="font-semibold text-slate-900">{{ $booking->service_label }}</div>
                                                <div class="text-xs text-slate-500">
                                                    @if ($booking->isSubscription())
                                                        {{ $booking->subscriptionSummary() }} &middot; Visit {{ $booking->subscription_sequence }}
                                                    @else
                                                        Cleaning service
                                                    @endif
                                                </div>
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
                                        @if ($booking->staff)
                                            <div class="flex items-center gap-2">
                                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-violet-100 text-[11px] font-bold text-violet-700">
                                                    {{ strtoupper(substr($booking->staff->first_name, 0, 1)) }}
                                                </div>
                                                <div>
                                                    <div class="text-sm font-medium text-slate-700">
                                                        {{ $booking->staff->first_name }} {{ $booking->staff->last_name }}
                                                    </div>
                                                    <div class="text-xs text-slate-500">Assigned cleaner</div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-sm italic text-slate-400">Cleaner not assigned yet</span>
                                        @endif

                                        @if ($booking->preferredStaff)
                                            <div class="mt-2 text-xs text-slate-500">
                                                Preferred Cleaner:
                                                <span class="font-semibold text-slate-700">
                                                    {{ $booking->preferredStaff->first_name }} {{ $booking->preferredStaff->last_name }}
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 align-top">
                                        <span class="font-semibold text-slate-900">&#8369;{{ number_format($booking->price, 0) }}</span>
                                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                            <span class="rounded-full px-2.5 py-1 font-semibold {{ $paymentStatusClasses[$booking->payment_status] ?? 'border border-slate-200 bg-slate-50 text-slate-600' }}">
                                                {{ \App\Models\Booking::paymentStatusLabel($booking->payment_status) }}
                                            </span>
                                            <span class="text-slate-500">{{ \App\Models\Booking::paymentMethodLabel($booking->payment_method) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 align-top">
                                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$booking->status] ?? 'border border-slate-200 bg-slate-50 text-slate-600' }}">
                                            {{ ucwords(str_replace('_', ' ', $booking->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 align-top">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('bookings.show', $booking->id) }}" class="inline-flex items-center gap-1 text-sm font-medium text-accent-600 hover:text-accent-700 hover:underline">
                                                View
                                                <i class="fas fa-arrow-right text-[11px]"></i>
                                            </a>
                                            @if ($booking->status === 'pending' && !$booking->staff_id)
                                                <form action="{{ route('bookings.cancel', $booking->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking? This action cannot be undone.')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="flex h-8 w-8 items-center justify-center rounded-full bg-danger-50 text-danger-500 transition hover:bg-danger-100" aria-label="Cancel booking">
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

                @if ($bookings->hasPages())
                    <div class="border-t border-slate-100 px-6 py-4">
                        {{ $bookings->links('pagination::tailwind') }}
                    </div>
                @endif
            @else
                <div class="px-6 py-14 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                        <i class="fas fa-broom text-xl"></i>
                    </div>
                    <h3 class="mt-4 text-lg font-bold text-slate-900">No bookings yet</h3>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
                        When you submit your first request, its schedule, payment status, and cleaner updates will
                        appear here.
                    </p>
                    <a href="{{ route('bookings.create') }}" class="mt-5 inline-flex items-center gap-2 rounded-2xl bg-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-dark">
                        <i class="fas fa-plus text-xs"></i>
                        Book your first service
                    </a>
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
