@extends('layouts.provider')

@section('title', 'Cleaner Dashboard')
@section('page-title', 'Cleaner Dashboard')
@section('page-subtitle', 'Manage assignments, availability, and payout setup')

@section('content')
@php
    $cleanerName = $application?->business_name ?? auth()->user()->display_name;
    $ratingValue = $ratingStats['average'] ?? null;
    $currentProgress = [
        ['label' => 'Assigned', 'icon' => 'fa-clipboard-check', 'active' => (bool) $currentBooking, 'meta' => $currentBooking?->created_at?->format('M d, Y')],
        ['label' => 'Confirmed', 'icon' => 'fa-check', 'active' => $currentBooking && in_array($currentBooking->status, ['confirmed', 'in_progress', 'completed'], true), 'meta' => $currentBooking?->scheduled_date?->format('M d, Y')],
        ['label' => 'Checked In', 'icon' => 'fa-broom', 'active' => (bool) $currentBooking?->started_at, 'meta' => $currentBooking?->started_at?->format('h:i A')],
        ['label' => 'In Progress', 'icon' => 'fa-person-running', 'active' => $currentBooking && in_array($currentBooking->status, ['in_progress', 'completed'], true), 'meta' => $currentBooking?->status === 'in_progress' ? 'Ongoing' : null],
        ['label' => 'Completed', 'icon' => 'fa-flag-checkered', 'active' => $currentBooking?->status === 'completed', 'meta' => $currentBooking?->completed_at?->format('h:i A') ?? 'Pending'],
    ];
@endphp

<section class="min-h-screen bg-slate-50 px-4 py-6 sm:px-6 sm:py-8">
    <div class="mx-auto max-w-7xl space-y-5">
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

        <section class="relative overflow-hidden rounded-2xl border border-blue-200 bg-gradient-to-r from-blue-50 via-white to-blue-50 p-6 shadow-sm">
            <div class="relative z-10 grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px] lg:items-center">
                <div>
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-2xl text-amber-500">
                            <i class="fas fa-hand-sparkles"></i>
                        </div>
                        <div>
                            <p class="text-lg font-black text-slate-900">Welcome back,</p>
                            <h1 class="text-4xl font-black tracking-tight text-slate-950">{{ $cleanerName }}!</h1>
                        </div>
                    </div>
                    <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-600">Here is what is happening with your cleaner account today.</p>
                    <div class="mt-4 inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-black {{ $application?->availabilityBadgeClass() ?? 'bg-slate-100 text-slate-600' }}">
                        <span class="h-2 w-2 rounded-full bg-current"></span>
                        {{ $application?->availabilityLabel() ?? 'Availability not set' }} for new assignments
                    </div>
                </div>
                <div class="relative hidden min-h-[150px] lg:block">
                    <div class="absolute bottom-0 right-6 h-28 w-28 rounded-full bg-blue-100"></div>
                    <div class="absolute bottom-0 right-20 h-24 w-20 rounded-t-full bg-blue-700 shadow-lg"></div>
                    <div class="absolute right-24 top-8 h-12 w-12 rounded-full bg-orange-100 ring-4 ring-white"></div>
                    <div class="absolute right-28 top-4 h-8 w-16 rounded-t-full bg-blue-800"></div>
                    <div class="absolute bottom-2 right-36 h-11 w-28 rounded-xl bg-blue-600/90"></div>
                    <div class="absolute bottom-10 right-44 h-12 w-12 rounded-xl bg-amber-100"></div>
                    <div class="absolute bottom-0 right-4 h-24 w-10 rounded-t-full bg-blue-100"></div>
                    <div class="absolute bottom-0 left-2 h-16 w-40 rounded-t-2xl bg-blue-100/70"></div>
                    <div class="absolute right-56 top-2 grid grid-cols-2 gap-1 opacity-60">
                        <span class="h-9 w-9 border-4 border-blue-100"></span>
                        <span class="h-9 w-9 border-4 border-blue-100"></span>
                        <span class="h-9 w-9 border-4 border-blue-100"></span>
                        <span class="h-9 w-9 border-4 border-blue-100"></span>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('provider.bookings') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-xl text-blue-700"><i class="fas fa-clipboard-list"></i></span>
                    <div>
                        <div class="text-sm font-semibold text-slate-600">Assigned Bookings</div>
                        <div class="mt-1 text-3xl font-black text-slate-950">{{ number_format($bookingStats['assigned']) }}</div>
                        <div class="mt-2 text-xs font-bold text-blue-700">View all bookings <i class="fas fa-arrow-right ml-1"></i></div>
                    </div>
                </div>
            </a>
            <a href="{{ route('provider.bookings', ['status' => 'active']) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-xl text-blue-700"><i class="fas fa-person-running"></i></span>
                    <div>
                        <div class="text-sm font-semibold text-slate-600">Active Bookings</div>
                        <div class="mt-1 text-3xl font-black text-slate-950">{{ number_format($bookingStats['active']) }}</div>
                        <div class="mt-2 text-xs font-bold text-blue-700">View active <i class="fas fa-arrow-right ml-1"></i></div>
                    </div>
                </div>
            </a>
            <a href="{{ route('provider.bookings', ['status' => 'completed']) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-xl text-emerald-700"><i class="fas fa-circle-check"></i></span>
                    <div>
                        <div class="text-sm font-semibold text-slate-600">Completed Jobs</div>
                        <div class="mt-1 text-3xl font-black text-slate-950">{{ number_format($bookingStats['completed']) }}</div>
                        <div class="mt-2 text-xs font-bold text-blue-700">View history <i class="fas fa-arrow-right ml-1"></i></div>
                    </div>
                </div>
            </a>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
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
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
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
                    <div class="mt-6 rounded-2xl bg-slate-50 px-5 py-10 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-slate-400 ring-1 ring-slate-200">
                            <i class="fas fa-calendar-check text-xl"></i>
                        </div>
                        <p class="mt-3 text-sm font-semibold text-slate-600">No active booking is in progress.</p>
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

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.1fr)_minmax(320px,0.8fr)_minmax(360px,0.9fr)]">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
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
                        <div class="px-5 py-10 text-center text-sm font-semibold text-slate-500">No assigned bookings yet.</div>
                    @endforelse
                </div>
            </section>

            @if($application)
                <section id="availability" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
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
                        <select name="availability_status" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-hidden">
                            <option value="available" {{ old('availability_status', $application->availability_status ?: 'available') === 'available' ? 'selected' : '' }}>Available</option>
                            <option value="paused" {{ old('availability_status', $application->availability_status) === 'paused' ? 'selected' : '' }}>Paused</option>
                        </select>
                        <input name="availability_notes" value="{{ old('availability_notes', $application->availability_notes) }}" placeholder="Example: Fully booked this weekend" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-hidden">
                        <input type="number" min="1" max="20" name="max_daily_bookings" value="{{ old('max_daily_bookings', $application->max_daily_bookings) }}" placeholder="Daily booking limit" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-hidden">
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-black text-blue-700 transition hover:bg-blue-100">
                            <i class="fas fa-calendar-check"></i>
                            Update Availability
                        </button>
                    </form>
                </section>

                <section id="payout-setup" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-black text-slate-950">Payout Verification</h2>
                            <p class="mt-2 text-sm text-slate-500">Complete payout setup to receive payments.</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-black ring-1 {{ $application->payoutVerificationBadgeClass() }}">{{ $application->payoutVerificationStatusLabel() }}</span>
                    </div>
                    <div class="mt-5 divide-y divide-slate-100 text-sm">
                        @foreach([
                            'details' => ['label' => $application->payoutMethodLabel().' Information'],
                            'valid_id' => ['label' => 'Valid ID'],
                            'proof' => ['label' => 'Proof of Ownership'],
                        ] as $key => $item)
                            <div class="flex items-center justify-between gap-4 py-3">
                                <span class="font-bold text-slate-700"><i class="fas fa-circle-info mr-2 text-slate-400"></i>{{ $item['label'] }}</span>
                                @if($payoutChecklist[$key])
                                    <span class="text-xs font-black text-emerald-700"><i class="fas fa-check mr-1"></i>Completed</span>
                                @else
                                    <span class="text-xs font-black text-orange-700"><i class="fas fa-clock mr-1"></i>Pending</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <form action="{{ route('provider.payout-setup.update') }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-3">
                        @csrf
                        @method('PATCH')
                        <div class="grid gap-3 sm:grid-cols-3">
                            <select name="payout_method" class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-hidden">
                                @foreach(\App\Models\CleanerApplication::PAYOUT_METHOD_LABELS as $method => $label)
                                    <option value="{{ $method }}" {{ old('payout_method', $application->payout_method) === $method ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <input name="payout_account_name" value="{{ old('payout_account_name', $application->payout_account_name) }}" placeholder="Account name" class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-hidden">
                            <input name="payout_account_number" value="{{ old('payout_account_number', $application->payout_account_number) }}" placeholder="Account number" class="rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-hidden">
                        </div>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <input type="file" name="valid_id_front_document" accept=".jpg,.jpeg,.png,.pdf" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs file:mr-2 file:rounded-lg file:border-0 file:bg-blue-50 file:px-2 file:py-1 file:text-xs file:font-bold file:text-blue-700">
                            <input type="file" name="valid_id_back_document" accept=".jpg,.jpeg,.png,.pdf" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs file:mr-2 file:rounded-lg file:border-0 file:bg-blue-50 file:px-2 file:py-1 file:text-xs file:font-bold file:text-blue-700">
                            <input type="file" name="payout_account_proof_document" accept=".jpg,.jpeg,.png,.pdf" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs file:mr-2 file:rounded-lg file:border-0 file:bg-blue-50 file:px-2 file:py-1 file:text-xs file:font-bold file:text-blue-700">
                        </div>
                        @if($application->isTeam())
                            <input type="file" name="business_permit_document" accept=".jpg,.jpeg,.png,.pdf" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs file:mr-2 file:rounded-lg file:border-0 file:bg-blue-50 file:px-2 file:py-1 file:text-xs file:font-bold file:text-blue-700">
                        @endif
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-black text-white transition hover:bg-blue-700">
                            <i class="fas fa-cloud-arrow-up"></i>
                            Update Documents
                        </button>
                    </form>
                </section>
            @endif
        </div>

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
