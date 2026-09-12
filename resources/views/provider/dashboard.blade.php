@extends('layouts.provider')

@section('title', 'Cleaner Dashboard')
@section('page-title', 'Cleaner Dashboard')
@section('page-subtitle', 'Manage assignments, availability, and payout setup')

@push('styles')
@endpush

@section('content')
@php
    $dashboardTimezone = config('cleanflow.attendance_timezone', 'Asia/Manila');
    $formatDashboardDateTime = static fn ($value, string $format = 'M d, Y') => $value?->copy()->timezone($dashboardTimezone)->format($format);
    $cleanerName = $application?->business_name ?? auth()->user()->display_name;
    $ratingValue = $ratingStats['average'] ?? null;
    $payoutChecklistItems = [
        'details' => ['label' => $application?->payoutMethodLabel().' information', 'complete' => $payoutChecklist['details'] ?? false],
        'valid_id' => ['label' => 'Valid ID (front and back)', 'complete' => $payoutChecklist['valid_id'] ?? false],
        'proof' => ['label' => 'Proof of ownership', 'complete' => $payoutChecklist['proof'] ?? false],
    ];
    if ($application?->isTeam()) {
        $payoutChecklistItems['business_permit'] = [
            'label' => 'Business permit',
            'complete' => $application->hasUploadedPayoutDocument(\App\Models\CleanerApplicationDocument::TYPE_BUSINESS_PERMIT),
        ];
    }
    $payoutCompletedCount = collect($payoutChecklistItems)->where('complete', true)->count();
    $payoutDocumentCount = count($payoutChecklistItems);
    $payoutSetupComplete = $payoutDocumentCount > 0 && $payoutCompletedCount === $payoutDocumentCount;
    $dashboardDate = now($dashboardTimezone)->format('l, M j');
    $nextAssignmentStatus = $currentBooking ? ucfirst(str_replace('_', ' ', $currentBooking->status)) : null;
    $currentProgress = [
        ['label' => 'Assigned', 'icon' => 'fa-clipboard-check', 'active' => (bool) $currentBooking, 'meta' => $formatDashboardDateTime($currentBooking?->created_at)],
        ['label' => 'Confirmed', 'icon' => 'fa-check', 'active' => $currentBooking && in_array($currentBooking->status, ['confirmed', 'in_progress', 'completed'], true), 'meta' => $currentBooking?->scheduled_date?->format('M d, Y')],
        ['label' => 'Checked In', 'icon' => 'fa-broom', 'active' => (bool) $currentBooking?->started_at, 'meta' => $formatDashboardDateTime($currentBooking?->started_at, 'h:i A')],
        ['label' => 'In Progress', 'icon' => 'fa-person-running', 'active' => $currentBooking && in_array($currentBooking->status, ['in_progress', 'completed'], true), 'meta' => $currentBooking?->status === 'in_progress' ? 'Ongoing' : null],
        ['label' => 'Completed', 'icon' => 'fa-flag-checkered', 'active' => $currentBooking?->status === 'completed', 'meta' => $formatDashboardDateTime($currentBooking?->completed_at, 'h:i A') ?? 'Pending'],
    ];
@endphp

<section class="provider-dashboard min-h-screen bg-slate-50 px-4 py-6 sm:px-6 sm:py-8">
    <div class="mx-auto max-w-[1440px] space-y-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="relative overflow-hidden rounded-[1.75rem] bg-gradient-to-br from-blue-950 via-blue-900 to-blue-700 p-6 text-white shadow-xl shadow-blue-950/15 sm:p-8">
            <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-blue-400/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-28 left-1/3 h-64 w-64 rounded-full bg-cyan-300/10 blur-3xl"></div>
            <div class="relative z-10 grid gap-8 lg:grid-cols-[minmax(0,1fr)_390px] lg:items-center">
                <div>
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex min-w-0 items-center gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/12 text-2xl text-amber-300 ring-1 ring-white/15">
                            <i class="fas fa-hand-sparkles"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold uppercase tracking-[0.16em] text-blue-100">Cleaner workspace</p>
                            <h1 class="mt-1 break-words text-3xl font-black tracking-tight text-white sm:text-4xl">Welcome back, {{ $cleanerName }}!</h1>
                        </div>
                        </div>
                        <div class="inline-flex shrink-0 items-center gap-2 rounded-full bg-white/10 px-3 py-2 text-xs font-bold text-blue-100 ring-1 ring-white/15">
                            <i class="fas fa-calendar-day text-blue-200"></i>
                            {{ $dashboardDate }}
                        </div>
                    </div>
                    <p class="mt-5 max-w-2xl text-sm leading-7 text-blue-100">Stay on top of assignments, keep your availability current, and finish payout verification when it is due.</p>
                    <div class="mt-5 flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-black text-white ring-1 ring-white/15">
                            <span class="h-2 w-2 rounded-full {{ $application?->availability_status === 'paused' ? 'bg-amber-300' : 'bg-emerald-300' }}"></span>
                            {{ $application?->availabilityLabel() ?? 'Availability not set' }} for new assignments
                        </span>
                        @if($application && ! $payoutSetupComplete)
                            <a href="#payout-setup" class="inline-flex items-center gap-2 rounded-full bg-amber-300 px-3 py-1.5 text-xs font-black text-blue-950 transition hover:bg-amber-200">
                                <i class="fas fa-shield-halved"></i> Finish payout setup
                            </a>
                        @endif
                    </div>
                </div>
                <div class="rounded-2xl border border-white/15 bg-white/10 p-5 backdrop-blur-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-xs font-black uppercase tracking-[0.14em] text-blue-100">Next assignment</div>
                        <i class="fas fa-calendar-day text-blue-200"></i>
                    </div>
                    @if($currentBooking)
                        <div class="mt-4 text-2xl font-black text-white">{{ $currentBooking->scheduled_date->format('M d, Y') }}</div>
                        <div class="mt-3 inline-flex rounded-full bg-white/10 px-2.5 py-1 text-xs font-bold text-blue-100 ring-1 ring-white/10">{{ $nextAssignmentStatus }}</div>
                        <div class="mt-1 text-sm font-semibold text-blue-100">{{ \Carbon\Carbon::parse($currentBooking->scheduled_time)->format('h:i A') }} <span class="px-1 text-blue-300">/</span> {{ $currentBooking->service_label }}</div>
                        <a href="{{ route('provider.bookings.show', $currentBooking) }}" class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-black text-blue-800 transition hover:bg-blue-50">
                            Open assignment <i class="fas fa-arrow-right"></i>
                        </a>
                    @else
                        <div class="mt-4 text-lg font-black text-white">No active assignment</div>
                        <p class="mt-1 text-sm leading-6 text-blue-100">Your next booking will appear here once it is assigned.</p>
                        <a href="{{ route('provider.bookings') }}" class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-white/12 px-4 py-2.5 text-sm font-black text-white ring-1 ring-white/20 transition hover:bg-white/20">
                            View assigned bookings <i class="fas fa-arrow-right"></i>
                        </a>
                    @endif
                </div>
            </div>
        </section>

        @if($application && ! $payoutSetupComplete)
            <section class="flex flex-col gap-4 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between" role="status">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700"><i class="fas fa-shield-halved"></i></span>
                    <div>
                        <h2 class="text-sm font-black text-amber-950">Payout setup needs attention</h2>
                        <p class="mt-1 text-xs leading-5 text-amber-800">Complete {{ $payoutDocumentCount - $payoutCompletedCount }} remaining item{{ ($payoutDocumentCount - $payoutCompletedCount) === 1 ? '' : 's' }} to receive payouts.</p>
                    </div>
                </div>
                <a href="#payout-setup" class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-400 px-4 py-2.5 text-xs font-black text-amber-950 transition hover:bg-amber-300">Complete setup <i class="fas fa-arrow-down"></i></a>
            </section>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('provider.bookings') }}" class="group rounded-2xl border-t-4 border-blue-500 border-x border-b border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-xl text-blue-700"><i class="fas fa-clipboard-list"></i></span>
                    <div>
                        <div class="text-sm font-semibold text-slate-600">Assigned Bookings</div>
                        <div class="mt-1 text-3xl font-black text-slate-950">{{ number_format($bookingStats['assigned']) }}</div>
                        <div class="mt-2 text-xs font-bold text-blue-700">View all bookings <i class="fas fa-arrow-right ml-1 transition-transform group-hover:translate-x-1"></i></div>
                    </div>
                </div>
            </a>
            <a href="{{ route('provider.bookings', ['status' => 'active']) }}" class="group rounded-2xl border-t-4 border-cyan-500 border-x border-b border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-xl text-blue-700"><i class="fas fa-person-running"></i></span>
                    <div>
                        <div class="text-sm font-semibold text-slate-600">Active Bookings</div>
                        <div class="mt-1 text-3xl font-black text-slate-950">{{ number_format($bookingStats['active']) }}</div>
                        <div class="mt-2 text-xs font-bold text-blue-700">View active <i class="fas fa-arrow-right ml-1 transition-transform group-hover:translate-x-1"></i></div>
                    </div>
                </div>
            </a>
            <a href="{{ route('provider.bookings', ['status' => 'completed']) }}" class="group rounded-2xl border-t-4 border-emerald-500 border-x border-b border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-xl text-emerald-700"><i class="fas fa-circle-check"></i></span>
                    <div>
                        <div class="text-sm font-semibold text-slate-600">Completed Jobs</div>
                        <div class="mt-1 text-3xl font-black text-slate-950">{{ number_format($bookingStats['completed']) }}</div>
                        <div class="mt-2 text-xs font-bold text-blue-700">View history <i class="fas fa-arrow-right ml-1 transition-transform group-hover:translate-x-1"></i></div>
                    </div>
                </div>
            </a>
            <div class="rounded-2xl border-t-4 border-purple-500 border-x border-b border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-purple-50 text-xl text-purple-700"><i class="fas fa-star"></i></span>
                    <div>
                        <div class="text-sm font-semibold text-slate-600">Average Rating</div>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="text-3xl font-black text-slate-950">{{ $ratingValue !== null ? number_format($ratingValue, 1) : 'N/A' }}</span>
                            @if($ratingValue !== null)
                                <span class="text-sm text-amber-400">
                                    @for($i = 1; $i <= 5; $i++)<i class="fas fa-star"></i>@endfor
                                </span>
                            @endif
                        </div>
                        <div class="mt-2 text-xs text-slate-500">Based on {{ number_format($ratingStats['total']) }} review{{ $ratingStats['total'] === 1 ? '' : 's' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(420px,0.95fr)]">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm xl:self-start">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg font-black text-slate-950">Current Booking Progress</h2>
                    @if($currentBooking)
                        <a href="{{ route('provider.bookings.show', $currentBooking) }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-50 px-3 py-2 text-xs font-black text-blue-700 transition hover:bg-blue-100">
                            View Details <i class="fas fa-arrow-right"></i>
                        </a>
                    @endif
                </div>
                @if($currentBooking)
                    <div class="mt-8 grid gap-4 sm:grid-cols-5">
                        @foreach($currentProgress as $index => $step)
                            <div class="relative text-center">
                                @if($index < 4)
                                    <div class="absolute left-1/2 top-5 hidden h-0.5 w-full {{ $currentProgress[$index + 1]['active'] ? 'bg-emerald-500' : 'bg-slate-200' }} sm:block"></div>
                                @endif
                                <div class="relative z-10 mx-auto flex h-11 w-11 items-center justify-center rounded-full text-sm font-black {{ $step['active'] ? 'bg-emerald-500 text-white' : 'bg-white text-slate-400 ring-2 ring-slate-200' }}">
                                    <i class="fas {{ $step['icon'] }}"></i>
                                </div>
                                <div class="mt-3 text-xs font-black text-slate-900">{{ $step['label'] }}</div>
                                <div class="mt-2 text-xs text-slate-500">{{ $step['meta'] ?? 'Pending' }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="mt-6 rounded-2xl bg-slate-50 px-5 py-8 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-slate-400 ring-1 ring-slate-200">
                            <i class="fas fa-calendar-check text-xl"></i>
                        </div>
                        <p class="mt-3 text-sm font-semibold text-slate-600">No active booking is in progress.</p>
                        <a href="{{ route('provider.bookings') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-xs font-black text-blue-700 ring-1 ring-slate-200 transition hover:bg-blue-50">Review assignments <i class="fas fa-arrow-right"></i></a>
                    </div>
                @endif
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg font-black text-slate-950">Earnings Summary</h2>
                    <a href="{{ route('provider.payouts') }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-50 px-3 py-2 text-xs font-black text-blue-700 transition hover:bg-blue-100">Payout History</a>
                </div>
                <div class="mt-5 grid gap-3 md:grid-cols-3">
                    <div class="rounded-xl bg-emerald-50 p-4">
                        <div class="text-xs font-bold text-slate-600">Gross Earnings</div>
                        <div class="mt-3 text-2xl font-black text-emerald-700">&#8369;{{ number_format($payoutStats['gross'], 2) }}</div>
                    </div>
                    <div class="rounded-xl bg-orange-50 p-4">
                        <div class="text-xs font-bold text-slate-600">Platform Fee</div>
                        <div class="mt-3 text-2xl font-black text-orange-700">&#8369;{{ number_format($payoutStats['commission'], 2) }}</div>
                    </div>
                    <div class="rounded-xl bg-blue-50 p-4">
                        <div class="text-xs font-bold text-slate-600">Net Payout</div>
                        <div class="mt-3 text-2xl font-black text-blue-700">&#8369;{{ number_format($payoutStats['payout'], 2) }}</div>
                    </div>
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-4">
                    @foreach([
                        'pending' => ['label' => 'Pending', 'class' => 'text-orange-700 bg-orange-50'],
                        'ready' => ['label' => 'Ready', 'class' => 'text-blue-700 bg-blue-50'],
                        'paid' => ['label' => 'Paid', 'class' => 'text-emerald-700 bg-emerald-50'],
                        'held' => ['label' => 'Held', 'class' => 'text-slate-700 bg-slate-50'],
                    ] as $key => $meta)
                        <div class="rounded-xl {{ $meta['class'] }} px-4 py-3">
                            <div class="text-xs font-black">{{ $meta['label'] }}</div>
                            <div class="mt-1 text-lg font-black">&#8369;{{ number_format($payoutStats[$key], 2) }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-xl bg-slate-50 px-4 py-3">
                        <div class="text-xs font-black text-slate-600">Cash Collected</div>
                        <div class="mt-1 text-lg font-black text-slate-950">&#8369;{{ number_format($payoutStats['cash_collected'], 2) }}</div>
                    </div>
                    <div class="rounded-xl bg-orange-50 px-4 py-3">
                        <div class="text-xs font-black text-orange-700">Commission Due</div>
                        <div class="mt-1 text-lg font-black text-orange-700">&#8369;{{ number_format($payoutStats['commission_due'], 2) }}</div>
                    </div>
                    <div class="rounded-xl bg-emerald-50 px-4 py-3">
                        <div class="text-xs font-black text-emerald-700">Commission Paid</div>
                        <div class="mt-1 text-lg font-black text-emerald-700">&#8369;{{ number_format($payoutStats['commission_paid'], 2) }}</div>
                    </div>
                </div>
            </section>
        </div>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.12fr)_minmax(360px,0.78fr)]">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:self-start">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="text-lg font-black text-slate-950">Recent Assigned Bookings</h2>
                    <a href="{{ route('provider.bookings') }}" class="rounded-xl bg-blue-50 px-3 py-2 text-xs font-black text-blue-700">View All <i class="fas fa-arrow-right ml-1"></i></a>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($recentBookings as $booking)
                        <a href="{{ route('provider.bookings.show', $booking) }}" class="block p-5 transition hover:bg-blue-50/50">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="font-mono text-sm font-black text-blue-700">CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</div>
                                    <div class="mt-2 text-base font-black text-slate-950">{{ $booking->service_label }}</div>
                                    <div class="mt-2 flex items-start gap-2 text-xs leading-5 text-slate-500">
                                        <i class="fas fa-location-dot mt-1 text-slate-400"></i>
                                        <span>{{ $booking->street_address }}, {{ $booking->barangay }}</span>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-blue-700">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span>
                                        <span class="rounded-full px-3 py-1 text-xs font-black {{ $booking->providerAssignmentBadgeClass() }}">{{ \App\Models\Booking::providerAssignmentStatusLabel($booking->effectiveProviderAssignmentStatus()) }}</span>
                                    </div>
                                </div>
                                <div class="shrink-0 text-right text-xs text-slate-500">
                                    <div class="font-black text-slate-900">{{ $booking->scheduled_date->format('M d, Y') }}</div>
                                    <div class="mt-1">{{ \Carbon\Carbon::parse($booking->scheduled_time)->format('h:i A') }}</div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="px-5 py-8 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600"><i class="fas fa-calendar-plus"></i></div>
                            <p class="mt-3 text-sm font-bold text-slate-700">No assigned bookings yet</p>
                            <p class="mx-auto mt-1 max-w-xs text-xs leading-5 text-slate-500">New assignments will appear here when CleanFlow sends them to you.</p>
                            <a href="{{ route('provider.bookings') }}" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-50 px-3 py-2 text-xs font-black text-blue-700 transition hover:bg-blue-100">Open bookings <i class="fas fa-arrow-right"></i></a>
                        </div>
                    @endforelse
                </div>
            </section>

            @if($application)
                <section id="availability" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm scroll-mt-24">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-black text-slate-950">Availability Status</h2>
                            <p class="mt-2 text-sm text-slate-500">You are {{ strtolower($application->availabilityLabel()) }} for new assignments.</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-black {{ $application->availabilityBadgeClass() }}">{{ $application->availabilityLabel() }}</span>
                    </div>
                    <dl class="mt-5 space-y-3 text-sm">
                        <div class="flex justify-between gap-4 border-b border-slate-100 pb-3">
                            <dt class="font-bold text-slate-600">Daily Limit</dt>
                            <dd class="text-right text-slate-500">{{ $application->max_daily_bookings ?: 'No limit set' }}</dd>
                        </div>
                        <div>
                            <dt class="font-bold text-slate-600">Note</dt>
                            <dd class="mt-1 text-slate-500">{{ $application->availability_notes ?: 'No note added' }}</dd>
                        </div>
                    </dl>
                    <form action="{{ route('provider.availability.update') }}" method="POST" class="mt-5 space-y-3">
                        @csrf
                        @method('PATCH')
                        <div>
                            <label for="availability_status" class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-500">Assignment status</label>
                            <select id="availability_status" name="availability_status" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-semibold focus:border-blue-500 focus:outline-hidden">
                                <option value="available" {{ old('availability_status', $application->availability_status ?: 'available') === 'available' ? 'selected' : '' }}>Available</option>
                                <option value="paused" {{ old('availability_status', $application->availability_status) === 'paused' ? 'selected' : '' }}>Paused</option>
                            </select>
                        </div>
                        <div>
                            <label for="availability_notes" class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-500">Note for admin</label>
                            <input id="availability_notes" name="availability_notes" value="{{ old('availability_notes', $application->availability_notes) }}" placeholder="Example: Fully booked this weekend" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-hidden">
                        </div>
                        <div>
                            <label for="max_daily_bookings" class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-500">Daily booking limit</label>
                            <input id="max_daily_bookings" type="number" min="1" max="20" step="1" name="max_daily_bookings" value="{{ old('max_daily_bookings', $application->max_daily_bookings) }}" placeholder="No limit" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-hidden">
                        </div>
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-black text-blue-700 transition hover:bg-blue-100">
                            <i class="fas fa-calendar-check"></i>
                            Update Availability
                        </button>
                    </form>
                </section>
            @endif
        </div>

        @if($application)
            <section id="payout-setup" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm scroll-mt-24">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-700"><i class="fas fa-wallet"></i></span>
                                <div>
                                    <h2 class="text-lg font-black text-slate-950">Payout Verification</h2>
                                    <p class="mt-1 text-sm text-slate-500">Complete your setup so payouts can be released without delays.</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-bold text-slate-500">{{ $payoutCompletedCount }}/{{ $payoutDocumentCount }} complete</span>
                            <span class="rounded-full px-3 py-1 text-xs font-black ring-1 {{ $application->payoutVerificationBadgeClass() }}">{{ $application->payoutVerificationStatusLabel() }}</span>
                        </div>
                    </div>
                    <div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-label="Payout setup progress" aria-valuenow="{{ $payoutCompletedCount }}" aria-valuemin="0" aria-valuemax="{{ $payoutDocumentCount }}">
                        <div class="h-full rounded-full bg-blue-600 transition-all" style="width: {{ $payoutDocumentCount > 0 ? round(($payoutCompletedCount / $payoutDocumentCount) * 100) : 0 }}%"></div>
                    </div>
                    <div class="mt-5 grid gap-2 text-sm sm:grid-cols-3">
                        @foreach($payoutChecklistItems as $key => $item)
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                                <span class="min-w-0 truncate font-bold text-slate-700"><i class="fas fa-circle-info mr-2 text-slate-400"></i>{{ $item['label'] }}</span>
                                @if($item['complete'])
                                    <span class="shrink-0 text-xs font-black text-emerald-700"><i class="fas fa-check mr-1"></i>Ready</span>
                                @else
                                    <span class="shrink-0 text-xs font-black text-orange-700"><i class="fas fa-clock mr-1"></i>Needed</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <form action="{{ route('provider.payout-setup.update') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-5">
                        @csrf
                        @method('PATCH')
                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            <div>
                                <label for="payout_method" class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-500">Payout method</label>
                                <select id="payout_method" name="payout_method" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-semibold focus:border-blue-500 focus:outline-hidden">
                                    @foreach(\App\Models\CleanerApplication::PAYOUT_METHOD_LABELS as $method => $label)
                                        <option value="{{ $method }}" {{ old('payout_method', $application->payout_method) === $method ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="payout_account_name" class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-500">Account name</label>
                                <input id="payout_account_name" name="payout_account_name" value="{{ old('payout_account_name', $application->payout_account_name) }}" placeholder="Name on account" required maxlength="150" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-hidden">
                            </div>
                            <div>
                                <label for="payout_account_number" class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-500">Account number</label>
                                <input id="payout_account_number" name="payout_account_number" value="{{ old('payout_account_number', $application->payout_account_number) }}" placeholder="Account or mobile number" required maxlength="100" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-hidden">
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label for="valid_id_front_document" class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-500">Valid ID / front</label>
                                <input id="valid_id_front_document" type="file" name="valid_id_front_document" accept=".jpg,.jpeg,.png,.pdf" @if(! $application->hasUploadedPayoutDocument(\App\Models\CleanerApplicationDocument::TYPE_VALID_ID_FRONT)) required @endif class="w-full min-w-0 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs file:mr-2 file:rounded-lg file:border-0 file:bg-blue-50 file:px-2 file:py-1 file:text-xs file:font-bold file:text-blue-700">
                            </div>
                            <div>
                                <label for="valid_id_back_document" class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-500">Valid ID / back</label>
                                <input id="valid_id_back_document" type="file" name="valid_id_back_document" accept=".jpg,.jpeg,.png,.pdf" @if(! $application->hasUploadedPayoutDocument(\App\Models\CleanerApplicationDocument::TYPE_VALID_ID_BACK)) required @endif class="w-full min-w-0 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs file:mr-2 file:rounded-lg file:border-0 file:bg-blue-50 file:px-2 file:py-1 file:text-xs file:font-bold file:text-blue-700">
                            </div>
                            <div>
                                <label for="payout_account_proof_document" class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-500">Account proof</label>
                                <input id="payout_account_proof_document" type="file" name="payout_account_proof_document" accept=".jpg,.jpeg,.png,.pdf" @if(! $application->hasUploadedPayoutDocument(\App\Models\CleanerApplicationDocument::TYPE_PAYOUT_ACCOUNT_PROOF)) required @endif class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs file:mr-2 file:rounded-lg file:border-0 file:bg-blue-50 file:px-2 file:py-1 file:text-xs file:font-bold file:text-blue-700">
                            </div>
                            @if($application->isTeam())
                                <div>
                                    <label for="business_permit_document" class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-500">Business permit</label>
                                    <input id="business_permit_document" type="file" name="business_permit_document" accept=".jpg,.jpeg,.png,.pdf" @if(! $application->hasUploadedPayoutDocument(\App\Models\CleanerApplicationDocument::TYPE_BUSINESS_PERMIT)) required @endif class="w-full min-w-0 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs file:mr-2 file:rounded-lg file:border-0 file:bg-blue-50 file:px-2 file:py-1 file:text-xs file:font-bold file:text-blue-700">
                                </div>
                            @endif
                        </div>
                        <p class="text-xs leading-5 text-slate-500"><i class="fas fa-circle-info mr-1 text-slate-400"></i>Accepted formats: JPG, PNG, or PDF. Maximum file size: 5 MB per document.</p>
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-black text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <i class="fas fa-cloud-arrow-up"></i>
                            Update Documents
                        </button>
                    </form>
                </section>
            @endif

        @if($application)
            <section data-provider-location-shell class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-black text-slate-950">Provider Base Location</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Keep your city/municipality and operating base pin accurate so CleanFlow admin can assign work safely. Customers cannot see this exact pin.</p>
                    </div>
                    <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1.5 text-xs font-black text-blue-700 ring-1 ring-blue-100"><i class="fas fa-lock"></i> Admin-only location</span>
                </div>
                <form action="{{ route('provider.location.update') }}" method="POST" class="mt-5 grid gap-5 lg:grid-cols-[280px_minmax(0,1fr)]" data-provider-location-form>
                    @csrf
                    @method('PATCH')
                    <div>
                        <label for="provider_location_area" class="mb-1.5 block text-xs font-black uppercase tracking-wide text-slate-500">City / municipality</label>
                        <select id="provider_location_area" name="location_area" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm font-semibold focus:border-blue-500 focus:outline-hidden">
                            <option value="">Select area</option>
                            @foreach(config('cleanflow.bukidnon_coverage_areas', []) as $areaValue => $areaLabel)
                                <option value="{{ $areaValue }}" {{ old('location_area', $application->location_area) === $areaValue ? 'selected' : '' }}>{{ $areaLabel }}</option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs leading-5 text-slate-500">Select an area first, then click the map at your base location.</p>
                        <button type="button" data-provider-location-current class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-black text-blue-700 transition hover:bg-blue-100"><i class="fas fa-location-crosshairs"></i> Use current location</button>
                        <p data-provider-location-status class="mt-3 text-xs font-bold text-slate-500">{{ $application->location_latitude && $application->location_longitude ? 'Saved provider location.' : 'No exact pin saved yet.' }}</p>
                        @error('location_area')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        @error('location_latitude')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        @error('location_longitude')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <div id="provider-dashboard-location-map" data-provider-location-map data-area-input="provider_location_area" data-latitude-input="provider_location_latitude" data-longitude-input="provider_location_longitude" data-latitude="{{ $application->location_latitude }}" data-longitude="{{ $application->location_longitude }}" class="h-80 overflow-hidden rounded-xl border border-slate-200 bg-slate-100"></div>
                        <input type="hidden" id="provider_location_latitude" name="location_latitude" value="{{ old('location_latitude', $application->location_latitude) }}">
                        <input type="hidden" id="provider_location_longitude" name="location_longitude" value="{{ old('location_longitude', $application->location_longitude) }}">
                    </div>
                    <div class="lg:col-span-2">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-black text-white transition hover:bg-blue-700"><i class="fas fa-save"></i> Save provider location</button>
                    </div>
                </form>
            </section>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-black text-slate-950">Performance Overview</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="flex items-center gap-4">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700"><i class="fas fa-clipboard-check"></i></span>
                    <div>
                        <div class="text-xl font-black text-slate-950">{{ number_format($performanceStats['completed_jobs']) }}</div>
                        <div class="text-sm font-semibold text-slate-600">Jobs Completed</div>
                        <div class="text-xs text-slate-500">All time</div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-700"><i class="fas fa-circle-check"></i></span>
                    <div>
                        <div class="text-xl font-black text-slate-950">{{ $performanceStats['acceptance_rate'] !== null ? $performanceStats['acceptance_rate'].'%' : 'N/A' }}</div>
                        <div class="text-sm font-semibold text-slate-600">Acceptance Rate</div>
                        <div class="text-xs text-slate-500">{{ $performanceStats['acceptance_rate'] !== null ? 'Based on responses' : 'No responses yet' }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-50 text-orange-700"><i class="fas fa-star"></i></span>
                    <div>
                        <div class="text-xl font-black text-slate-950">{{ $performanceStats['average_rating'] !== null ? number_format($performanceStats['average_rating'], 1) : 'N/A' }}</div>
                        <div class="text-sm font-semibold text-slate-600">Average Rating</div>
                        <div class="text-xs text-slate-500">Based on {{ number_format($performanceStats['total_ratings']) }} review{{ $performanceStats['total_ratings'] === 1 ? '' : 's' }}</div>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-50 text-purple-700"><i class="fas fa-clock"></i></span>
                    <div>
                        <div class="text-xl font-black text-slate-950">{{ $performanceStats['average_response_minutes'] !== null ? $performanceStats['average_response_minutes'].' min' : 'N/A' }}</div>
                        <div class="text-sm font-semibold text-slate-600">Avg. Response Time</div>
                        <div class="text-xs text-slate-500">{{ $performanceStats['average_response_minutes'] !== null ? 'Assignment response' : 'No responses yet' }}</div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</section>
@endsection

@push('scripts')
<script>
    window.cleanflowGoogleMapsEnabled = @json(!empty(config('services.google.maps_api_key')));
    window.cleanflowProviderMapConfig = @json(config('cleanflow.provider_map'));
    window.cleanflowProviderLocationCenters = @json(config('cleanflow.bukidnon_location_centers', []));
</script>
<script src="{{ asset('js/provider-location-map.js') }}"></script>
@if(config('services.google.maps_api_key'))
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ urlencode(config('services.google.maps_api_key')) }}&callback=initCleanflowProviderMap"></script>
@endif
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-provider-location-form]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            const latitude = form.querySelector('[name="location_latitude"]')?.value;
            const longitude = form.querySelector('[name="location_longitude"]')?.value;
            const status = form.querySelector('[data-provider-location-status]');

            if (latitude && longitude) {
                return;
            }

            event.preventDefault();
            if (status) {
                status.textContent = 'Click the map or use your current location before saving.';
                status.classList.add('text-red-600');
                status.classList.remove('text-emerald-700');
            }
        });
    });
});
</script>
@endpush
