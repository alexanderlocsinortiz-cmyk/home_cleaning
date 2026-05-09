@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : (auth()->user()->role === 'staff' ? 'layouts.staff' : 'layouts.client'))
@section('title', 'Booking Details - Home Cleaning Service')
@section('page-title', 'Booking Details')
@section('page-subtitle', 'Live service status and staff tracking')

@php
    $viewer = auth()->user();
    $isAdmin = $viewer->role === 'admin';
    $isClient = $viewer->role === 'client';
    $isStaff = $viewer->role === 'staff';
    $backUrl = $isAdmin ? route('admin.bookings') : ($isStaff ? route('staff.bookings') : route('bookings.index'));
    $backLabel = $isAdmin ? 'Back to Bookings' : ($isStaff ? 'Back to Assigned Bookings' : 'Back to My Bookings');
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />
@endpush

@section('content')
@php
    $statusConfig = [
        'pending' => ['label' => 'Pending', 'badge' => 'bg-amber-100 text-amber-700', 'bar' => 'bg-amber-500', 'icon' => 'fa-hourglass-half'],
        'confirmed' => ['label' => 'Confirmed', 'badge' => 'bg-accent-50 text-accent-700', 'bar' => 'bg-accent-500', 'icon' => 'fa-calendar-check'],
        'in_progress' => ['label' => 'In Progress', 'badge' => 'bg-primary-100 text-primary-700', 'bar' => 'bg-primary-500', 'icon' => 'fa-soap'],
        'completed' => ['label' => 'Completed', 'badge' => 'bg-accent-100 text-accent-800', 'bar' => 'bg-accent-600', 'icon' => 'fa-circle-check'],
        'cancelled' => ['label' => 'Cancelled', 'badge' => 'bg-danger-100 text-danger-700', 'bar' => 'bg-danger-600', 'icon' => 'fa-ban'],
    ];
    $sc = $statusConfig[$booking->status] ?? ['label' => 'Unknown', 'badge' => 'bg-slate-100 text-slate-700', 'bar' => 'bg-slate-500', 'icon' => 'fa-circle-question'];
    $bookingCode = 'CF-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT);
    $scheduledDate = \Carbon\Carbon::parse($booking->scheduled_date);
    $scheduledTime = \Carbon\Carbon::parse($booking->scheduled_time);
    $homeUrl = $isAdmin ? route('admin.dashboard') : ($isStaff ? route('staff.dashboard') : route('client.dashboard'));
    $listLabel = $isAdmin ? 'Bookings' : ($isStaff ? 'Assigned Bookings' : 'My Bookings');
    $propertyTypeLabel = \App\Models\Booking::propertyTypeLabel($booking->property_type);
    $selectedAddOns = \App\Models\Booking::addOnBreakdown($booking->add_ons ?? []);
    $includedFloorArea = \App\Models\Booking::includedFloorArea();
    $floorArea = (int) ($booking->floor_area ?? 0);
    $billableFloorArea = max(0, $floorArea - $includedFloorArea);
    $floorAreaRate = \App\Models\Booking::floorAreaRateForService($booking->service_type);
    $paymentMethodLabel = \App\Models\Booking::paymentMethodLabel($booking->payment_method);
    $paymentStatusLabel = \App\Models\Booking::paymentStatusLabel($booking->payment_status);
    $servicePlanLabel = \App\Models\Booking::servicePlanLabel($booking->service_plan);
    $subscriptionSummary = $booking->subscriptionSummary();
    $paymentStatusClasses = [
        'paid' => 'bg-accent-100 text-accent-800',
        'pending' => 'bg-amber-100 text-amber-700',
    ];
    $staffInitials = $booking->staff
        ? strtoupper(substr($booking->staff->first_name ?? 'S', 0, 1) . substr($booking->staff->last_name ?? 'T', 0, 1))
        : 'NA';
    $beforeProofs = $booking->serviceProofs->where('stage', 'before')->where('media_type', 'image')->values();
    $afterProofs = $booking->serviceProofs->where('stage', 'after')->where('media_type', 'image')->values();
    $completionVideos = $booking->serviceProofs->where('stage', 'after')->where('media_type', 'video')->values();
    $activityLogs = $booking->activityLogs;
    $bookingMessages = $booking->messages;
    $canSendBookingMessage = $booking->staff_id && (
        ($isClient && (int) $booking->user_id === (int) $viewer->id)
        || ($isStaff && (int) $booking->staff_id === (int) $viewer->id)
    );
@endphp

<div class="cleanflow-page-shell min-h-[calc(100vh-81px)] px-6 py-8">
    <div class="mx-auto max-w-7xl">
        @if(session('success'))
        <div class="cleanflow-alert cleanflow-alert--success mb-4 text-sm">
            {{ session('success') }}
        </div>
        @endif

        @if($errors->any())
        <div class="cleanflow-alert cleanflow-alert--error mb-4 text-sm">
            <div class="mb-2 font-semibold text-red-800">Please review the following:</div>
            @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
            @endforeach
        </div>
        @endif

        @if(session('error'))
        <div class="cleanflow-alert cleanflow-alert--error mb-4 text-sm">
            {{ session('error') }}
        </div>
        @endif

        @if(session('warning'))
        <div class="cleanflow-alert cleanflow-alert--warning mb-4 text-sm">
            {{ session('warning') }}
        </div>
        @endif

        @if(session('info'))
        <div class="cleanflow-alert cleanflow-alert--info mb-4 text-sm">
            {{ session('info') }}
        </div>
        @endif

        <div class="cleanflow-hero mb-6 px-6 py-6 text-white">
            <div class="cleanflow-hero-content flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="mb-3 flex flex-wrap items-center gap-2 text-sm text-white/70">
                    <a href="{{ $homeUrl }}" class="transition hover:text-white">Dashboard</a>
                    <span>&gt;</span>
                    <a href="{{ $backUrl }}" class="transition hover:text-white">{{ $listLabel }}</a>
                    <span>&gt;</span>
                    <span class="text-white/85">Booking Details</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-3xl font-bold text-white">Booking Details</h1>
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/12 px-3 py-1 text-sm font-semibold text-white backdrop-blur">
                            <i class="fa-solid {{ $sc['icon'] }}"></i>
                            {{ $sc['label'] }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-white/78">
                        Booking # <span class="font-mono font-semibold text-white">{{ $bookingCode }}</span>
                        <span class="mx-2 text-white/30">&bull;</span>
                        Scheduled for {{ $scheduledDate->format('F d, Y') }} at {{ $scheduledTime->format('h:i A') }}
                    </p>
                </div>
                <a href="{{ $backUrl }}" class="cleanflow-ghost-button self-start lg:self-auto">
                <i class="fa-solid fa-arrow-left"></i>
                {{ $backLabel }}
                </a>
            </div>
        </div>

        <div class="booking-show-summary-grid mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="cleanflow-panel border-l-4 border-primary-300 bg-primary-50/80 p-5">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-primary-700">Current Status</div>
                <div class="mt-3 flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-primary-600 shadow-sm">
                        <i class="fa-solid {{ $sc['icon'] }}"></i>
                    </div>
                    <div>
                        <div class="text-lg font-bold text-slate-900">{{ $sc['label'] }}</div>
                        <div class="text-sm text-slate-500">Latest booking stage</div>
                    </div>
                </div>
            </div>

            <div class="cleanflow-panel border-l-4 border-secondary-300 bg-secondary-50/80 p-5">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-secondary-700">Payment Status</div>
                <div class="mt-3 flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-secondary-600 shadow-sm">
                        <i class="fa-solid fa-credit-card"></i>
                    </div>
                    <div>
                        <div class="text-lg font-bold text-slate-900">{{ $paymentStatusLabel }}</div>
                        <div class="text-sm text-slate-500">{{ $paymentMethodLabel }}</div>
                    </div>
                </div>
            </div>

            <div class="cleanflow-panel border-l-4 border-accent-300 bg-accent-50/80 p-5">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-accent-700">Assigned Cleaner</div>
                <div class="mt-3 flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-accent-600 shadow-sm">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <div>
                        <div class="text-lg font-bold text-slate-900">{{ $booking->staff ? $booking->staff->full_name : 'Pending' }}</div>
                        <div class="text-sm text-slate-500">{{ $booking->staff ? 'Cleaner assigned' : 'Waiting for assignment' }}</div>
                    </div>
                </div>
            </div>

            <div class="cleanflow-panel border-l-4 border-amber-300 bg-amber-50/80 p-5">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">Proof Of Service</div>
                <div class="mt-3 flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-amber-600 shadow-sm">
                        <i class="fa-solid fa-camera-retro"></i>
                    </div>
                    <div>
                        <div class="text-lg font-bold text-slate-900">{{ $beforeProofs->count() + $afterProofs->count() + $completionVideos->count() }}</div>
                        <div class="text-sm text-slate-500">Uploaded file{{ ($beforeProofs->count() + $afterProofs->count() + $completionVideos->count()) === 1 ? '' : 's' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="booking-show-grid grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <div class="space-y-6">
                <div class="detail-card cleanflow-panel overflow-hidden">
                    <div class="{{ $sc['bar'] }} px-6 py-4 text-white">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-white/80">Service Overview</p>
                                <h2 class="mt-1 text-xl font-semibold">Booking Information</h2>
                            </div>
                            <span class="inline-flex items-center gap-2 self-start rounded-full bg-white/15 px-3 py-1 text-xs font-semibold backdrop-blur">
                                <i class="fa-solid {{ $sc['icon'] }}"></i>
                                {{ $sc['label'] }}
                            </span>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                            <div class="flex items-center gap-4">
                                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-2xl text-emerald-600">
                                    <i class="fa-solid fa-broom"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-slate-500">Service Type</p>
                                    <h3 class="text-2xl font-bold text-slate-900">{{ $booking->service_label }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">Reference: <span class="font-mono font-semibold text-emerald-600">{{ $bookingCode }}</span></p>
                                </div>
                            </div>

                            <div class="rounded-2xl bg-emerald-50 px-5 py-4 lg:text-right">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700/70">Total Amount</p>
                                <p class="mt-1 text-3xl font-bold text-emerald-600">&#8369;{{ number_format($booking->price, 2) }}</p>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                                <div class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <i class="fa-regular fa-calendar text-emerald-500"></i>
                                    Scheduled Date
                                </div>
                                <div class="text-base font-semibold text-slate-900">{{ $scheduledDate->format('F d, Y') }}</div>
                                <div class="mt-1 text-sm text-slate-500">{{ $scheduledDate->format('l') }}</div>
                            </div>

                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                                <div class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <i class="fa-regular fa-clock text-emerald-500"></i>
                                    Service Time
                                </div>
                                <div class="text-base font-semibold text-slate-900">{{ $scheduledTime->format('h:i A') }}</div>
                                <div class="mt-1 text-sm text-slate-500">Local schedule</div>
                            </div>

                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                                <div class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <i class="fa-solid fa-ruler-combined text-emerald-500"></i>
                                    Service Basis
                                </div>
                                <div class="text-base font-semibold text-slate-900">{{ $propertyTypeLabel }}</div>
                                <div class="mt-1 text-sm text-slate-500">{{ $booking->rooms }} room{{ $booking->rooms === 1 ? '' : 's' }} • {{ $booking->bathrooms }} bathroom{{ $booking->bathrooms === 1 ? '' : 's' }}</div>
                                <div class="mt-1 text-sm text-slate-500">{{ $floorArea > 0 ? $floorArea . ' sqm total floor area' : 'Floor area not provided' }}</div>
                            </div>

                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                                <div class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <i class="fa-solid fa-rotate text-emerald-500"></i>
                                    Service Plan
                                </div>
                                <div class="text-base font-semibold text-slate-900">{{ $servicePlanLabel }}</div>
                                <div class="mt-1 text-sm text-slate-500">
                                    {{ $subscriptionSummary ?: 'Single scheduled visit only.' }}
                                </div>
                                @if($booking->isSubscription())
                                <div class="mt-1 text-sm text-slate-500">Visit {{ $booking->subscription_sequence }} of {{ $booking->subscription_occurrences }}</div>
                                @endif
                            </div>

                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                                <div class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <i class="fa-solid fa-location-dot text-emerald-500"></i>
                                    Service Address
                                </div>
                                <div class="text-base font-semibold text-slate-900">{{ $booking->street_address }}</div>
                                <div class="mt-1 text-sm text-slate-500">{{ ucfirst($booking->barangay) }}, Valencia City</div>
                            </div>

                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                                <div class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <i class="fa-solid fa-puzzle-piece text-emerald-500"></i>
                                    Add-ons
                                </div>
                                @if(count($selectedAddOns))
                                    <div class="space-y-1">
                                        @foreach($selectedAddOns as $addOn)
                                        <div class="text-sm font-medium text-slate-900">{{ $addOn['label'] }}</div>
                                        @endforeach
                                    </div>
                                    <div class="mt-2 text-sm text-slate-500">{{ count($selectedAddOns) }} add-on{{ count($selectedAddOns) === 1 ? '' : 's' }} included in the quotation.</div>
                                @else
                                    <div class="text-sm text-slate-700">No add-ons were included in this booking.</div>
                                @endif
                            </div>

                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                                <div class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <i class="fa-solid fa-credit-card text-emerald-500"></i>
                                    Payment
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="text-base font-semibold text-slate-900">{{ $paymentMethodLabel }}</div>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $paymentStatusClasses[$booking->payment_status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $paymentStatusLabel }}
                                    </span>
                                </div>
                                <div class="mt-1 text-sm text-slate-500">
                                    @if($booking->payment_reference)
                                    Reference: {{ $booking->payment_reference }}
                                    @else
                                    A payment reference will appear here once one is recorded.
                                    @endif
                                </div>
                                <div class="mt-1 text-sm text-slate-500">
                                    @if($booking->paid_at)
                                    Paid on {{ $booking->paid_at->format('F d, Y h:i A') }}
                                    @elseif($booking->payment_method === 'on_site_cash')
                                    Cash will be recorded after service completion.
                                    @else
                                    Payment is still waiting for confirmation.
                                    @endif
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                                <div class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <i class="fa-regular fa-note-sticky text-emerald-500"></i>
                                    Notes
                                </div>
                                <div class="text-sm text-slate-700">{{ $booking->notes ?: 'No special instructions were added for this booking.' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="detail-card cleanflow-panel p-6">
                    <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Staff Assignment</h2>
                            <p class="text-sm text-slate-500">Assigned cleaner details for this booking.</p>
                        </div>
                        @if($booking->staff)
                        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                            <i class="fa-solid fa-circle-check"></i>
                            Assigned
                        </span>
                        @endif
                    </div>

                    @if($booking->staff)
                    <div class="flex flex-col gap-4 rounded-2xl border border-slate-100 bg-slate-50 p-4 sm:flex-row sm:items-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-primary-600 text-sm font-bold text-white shadow-sm">
                            {{ $staffInitials }}
                        </div>
                        <div class="flex-1">
                            <div class="text-lg font-semibold text-slate-900">{{ $booking->staff->first_name }} {{ $booking->staff->last_name }}</div>
                            <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-500">
                                <span><i class="fa-solid fa-phone mr-1 text-violet-500"></i>{{ $booking->staff->phone ?: 'Phone not available' }}</span>
                                <span><i class="fa-solid fa-location-dot mr-1 text-violet-500"></i>{{ ucfirst($booking->staff->barangay ?? 'N/A') }}</span>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4">
                        <div class="flex gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-yellow-100 text-yellow-600">
                                <i class="fa-solid fa-hourglass-half"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-yellow-800">Cleaner not yet assigned</div>
                                <div class="mt-1 text-sm text-yellow-700">Our admin team will assign a cleaner as soon as your booking is confirmed.</div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($booking->preferredStaff)
                    <div class="mt-4 rounded-xl border border-blue-100 bg-blue-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Preferred Cleaner</div>
                        <div class="mt-2 text-base font-semibold text-slate-900">{{ $booking->preferredStaff->full_name }}</div>
                        <div class="mt-1 text-sm text-slate-600">
                            @if($booking->preferred_staff_status === 'requested')
                                Your request has been recorded and is waiting for final assignment.
                            @elseif($booking->preferred_staff_status === 'unavailable')
                                This cleaner was not available for your selected date and time, so another available cleaner will be assigned.
                            @elseif($booking->preferred_staff_status === 'assigned')
                                Your preferred cleaner was successfully assigned to this booking.
                            @elseif($booking->preferred_staff_status === 'alternate_assigned')
                                A different cleaner was assigned because your preferred cleaner could not take this booking.
                            @else
                                No preferred cleaner update is available yet.
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                @if($isAdmin && $booking->rating)
                <div class="detail-card cleanflow-panel p-6">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold text-slate-900">Client Rating</h2>
                        <p class="text-sm text-slate-500">Feedback submitted for this completed service.</p>
                    </div>
                    <div class="mb-4 flex items-center gap-2">
                        @for($i = 1; $i <= 5; $i++)
                        <span class="text-2xl {{ $i <= $booking->rating->stars ? 'text-amber-400' : 'text-slate-200' }}">&#9733;</span>
                        @endfor
                        <span class="ml-2 text-sm font-semibold text-slate-700">{{ $booking->rating->stars }}/5</span>
                    </div>
                    @if($booking->rating->comment)
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 text-sm italic text-slate-600">"{{ $booking->rating->comment }}"</div>
                    @endif
                    @if($booking->rating->photo)
                    <div class="mt-4">
                        <div class="mb-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Client Photo</div>
                        <img src="{{ asset('storage/' . $booking->rating->photo) }}" alt="Rating photo" class="max-h-64 rounded-2xl border border-slate-200 object-cover">
                    </div>
                    @endif
                    <div class="mt-4 text-xs text-slate-500">
                        Reviewed by {{ $booking->user->first_name }} {{ $booking->user->last_name }} on {{ $booking->rating->created_at->format('M d, Y') }}
                    </div>
                </div>
                @endif

                <div class="detail-card cleanflow-panel p-6">
                    <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Proof of Service</h2>
                            <p class="text-sm text-slate-500">Before-and-after documentation uploaded during the service.</p>
                        </div>
                        <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                            <i class="fa-solid fa-camera-retro text-emerald-500"></i>
                            {{ $beforeProofs->count() + $afterProofs->count() + $completionVideos->count() }} file{{ ($beforeProofs->count() + $afterProofs->count() + $completionVideos->count()) === 1 ? '' : 's' }}
                        </span>
                    </div>

                    <div class="grid gap-5 lg:grid-cols-2">
                        <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                            <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-900">
                                <i class="fa-solid fa-door-open text-blue-500"></i>
                                Before Service Photos
                            </div>
                            @if($beforeProofs->count())
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                @foreach($beforeProofs as $proof)
                                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                    <img src="{{ asset('storage/' . $proof->file_path) }}" alt="Before service proof" class="h-44 w-full object-cover">
                                    <div class="space-y-1 px-3 py-2 text-xs text-slate-500">
                                        <div>Uploaded {{ $proof->created_at->format('M d, Y h:i A') }}</div>
                                        <div>By {{ $proof->uploader?->full_name ?? 'Assigned staff' }}</div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @else
                            <div class="rounded-xl border border-dashed border-slate-200 bg-white px-4 py-6 text-sm text-slate-500">
                                Before-service photos will appear here once the cleaner starts the job.
                            </div>
                            @endif
                        </div>

                        <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                            <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-900">
                                <i class="fa-solid fa-sparkles text-emerald-500"></i>
                                After Service Photos
                            </div>
                            @if($afterProofs->count())
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                @foreach($afterProofs as $proof)
                                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                    <img src="{{ asset('storage/' . $proof->file_path) }}" alt="After service proof" class="h-44 w-full object-cover">
                                    <div class="space-y-1 px-3 py-2 text-xs text-slate-500">
                                        <div>Uploaded {{ $proof->created_at->format('M d, Y h:i A') }}</div>
                                        <div>By {{ $proof->uploader?->full_name ?? 'Assigned staff' }}</div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @else
                            <div class="rounded-xl border border-dashed border-slate-200 bg-white px-4 py-6 text-sm text-slate-500">
                                After-service photos will appear here once the service is completed.
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-5 rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-900">
                            <i class="fa-solid fa-video text-fuchsia-500"></i>
                            Completion Video
                        </div>
                        @if($completionVideos->count())
                        <div class="space-y-4">
                            @foreach($completionVideos as $proof)
                            <div class="rounded-2xl border border-slate-200 bg-white p-3">
                                <video controls preload="metadata" class="w-full rounded-2xl border border-slate-200 bg-slate-950">
                                    <source src="{{ asset('storage/' . $proof->file_path) }}">
                                    Your browser does not support HTML video playback.
                                </video>
                                <div class="mt-2 text-xs text-slate-500">
                                    Uploaded {{ $proof->created_at->format('M d, Y h:i A') }} by {{ $proof->uploader?->full_name ?? 'Assigned staff' }}
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="rounded-xl border border-dashed border-slate-200 bg-white px-4 py-6 text-sm text-slate-500">
                            A completion video has not been uploaded for this booking.
                        </div>
                        @endif
                    </div>
                </div>

                @if($isClient && in_array($booking->status, ['confirmed', 'in_progress'], true) && $booking->staff)
                <div class="detail-card cleanflow-panel p-6">
                    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Staff Location</h2>
                            <p class="text-sm text-slate-500">Track the cleaner while the booking is active.</p>
                        </div>
                        <div id="location-status" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500">Waiting for location...</div>
                    </div>
                    @if($booking->status === 'confirmed')
                    <div id="no-location-msg" class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center text-slate-500">
                        <div class="mb-2 text-[32px] text-slate-400"><i class="fa-solid fa-map-location-dot"></i></div>
                        <div class="text-sm">Live location will appear here once your assigned staff member starts sharing it.</div>
                    </div>

                    <div id="map-container" class="hidden space-y-4">
                        <div id="arrival-status" class="hidden rounded-xl border border-green-200 bg-green-50 p-4">
                            <div class="flex items-center gap-2.5">
                                <span class="text-[22px] text-emerald-600"><i class="fa-solid fa-route"></i></span>
                                <div>
                                    <div id="arrival-text" class="text-sm font-bold text-emerald-700">Staff is on the way</div>
                                    <div id="arrival-sub" class="mt-0.5 text-xs text-slate-500"></div>
                                </div>
                            </div>
                        </div>
                        <div id="google-map-frame" class="rounded-2xl"></div>
                        <div id="client-route-info" class="hidden rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-center text-sm text-emerald-700"></div>
                    </div>
                    @elseif($booking->status === 'in_progress')
                    <div id="no-location-msg" class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center text-slate-500">
                        <div class="mb-2 text-[32px] text-slate-400"><i class="fa-solid fa-map-location-dot"></i></div>
                        <div class="text-sm">Live location will appear here once your assigned staff member starts sharing it.</div>
                    </div>

                    <div id="map-container" class="hidden space-y-4">
                        <div id="arrival-status" class="hidden rounded-xl border border-green-200 bg-green-50 p-4">
                            <div class="flex items-center gap-2.5">
                                <span class="text-[22px] text-emerald-600"><i class="fa-solid fa-route"></i></span>
                                <div>
                                    <div id="arrival-text" class="text-sm font-bold text-emerald-700">Staff is on the way</div>
                                    <div id="arrival-sub" class="mt-0.5 text-xs text-slate-500"></div>
                                </div>
                            </div>
                        </div>
                        <div id="google-map-frame" class="rounded-2xl"></div>
                        <div id="client-route-info" class="hidden rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-center text-sm text-emerald-700"></div>
                    </div>
                    @endif

                    <div class="mt-4 text-center text-xs text-slate-500">
                        Live location is shared only during active cleaning service.
                    </div>
                </div>
                @endif
                @if($isAdmin)
                <div class="detail-card cleanflow-panel p-6">
                    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Live Tracking</h2>
                            <p class="text-sm text-slate-500">Monitor active cleaner location for this booking.</p>
                        </div>
                        <div id="admin-location-status" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">Checking...</div>
                    </div>

                    <div id="admin-no-location" class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <i class="fa-solid fa-map-location-dot text-xl"></i>
                        </div>
                        <div class="mt-4 text-sm font-medium text-slate-700">Live location has not been shared yet for this booking.</div>
                        <div class="mt-1 text-xs text-slate-500">The map will update automatically once staff tracking is active.</div>
                    </div>

                    <div id="admin-map-container" class="hidden space-y-4">
                        <div id="admin-google-map-frame" class="rounded-2xl"></div>
                        <div id="admin-location-info" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-500"></div>
                    </div>
                </div>
                @endif

                @if($isClient && $booking->status === 'completed' && $booking->staff_id && !$booking->rating)
                <div class="detail-card cleanflow-panel p-6">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold text-slate-900">Rate This Service</h2>
                        <p class="text-sm text-slate-500">Share your experience and help us improve future bookings.</p>
                    </div>
                    <form action="{{ route('bookings.rate', $booking->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div id="star-rating" class="flex gap-2">
                            @for($i = 1; $i <= 5; $i++)
                            <button type="button" onclick="setRating({{ $i }})" class="star-btn rating-star text-4xl leading-none text-slate-200" data-value="{{ $i }}">&#9733;</button>
                            @endfor
                        </div>
                        <input type="hidden" name="stars" id="stars-input" value="">
                        @error('stars')
                        <p class="text-sm text-red-500">{{ $message }}</p>
                        @enderror
                        <textarea name="comment" rows="4" placeholder="Write your review (optional)..." class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-slate-700 outline-hidden transition focus:border-emerald-300 focus:ring-2 focus:ring-emerald-200"></textarea>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Add a Photo (optional)</label>
                            <input type="file" name="photo" accept="image/*" id="photo-input" class="hidden" onchange="previewPhoto(this)">
                            <div onclick="document.getElementById('photo-input').click()" class="cursor-pointer rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 px-6 py-8 text-center transition hover:border-emerald-300 hover:bg-emerald-50/40">
                                <div id="photo-placeholder">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm"><i class="fa-solid fa-camera text-lg"></i></div>
                                    <div class="mt-3 text-sm font-medium text-slate-700">Click to upload a review photo</div>
                                    <div class="mt-1 text-xs text-slate-500">JPG, PNG, or WEBP up to 5MB</div>
                                </div>
                                <img id="photo-preview" src="" class="mx-auto hidden max-h-56 rounded-2xl border border-slate-200 object-cover">
                            </div>
                            @error('photo')
                            <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            <i class="fa-solid fa-star"></i> Submit Rating
                        </button>
                    </form>
                </div>
                @elseif($isClient && $booking->rating)
                <div class="detail-card cleanflow-panel p-6">
                    <div class="mb-4">
                        <h2 class="text-lg font-semibold text-slate-900">Your Rating</h2>
                        <p class="text-sm text-slate-500">Thank you for sharing your feedback on this service.</p>
                    </div>
                    <div class="mb-4 flex items-center gap-2">
                        @for($i = 1; $i <= 5; $i++)
                        <span class="text-3xl {{ $i <= $booking->rating->stars ? 'text-amber-400' : 'text-slate-200' }}">&#9733;</span>
                        @endfor
                    </div>
                    @if($booking->rating->comment)
                    <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 text-sm italic text-slate-600">"{{ $booking->rating->comment }}"</div>
                    @endif
                    @if($booking->rating->photo)
                    <div class="mt-4">
                        <div class="mb-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Your Photo</div>
                        <img src="{{ asset('storage/' . $booking->rating->photo) }}" alt="Rating photo" class="max-h-64 rounded-2xl border border-slate-200 object-cover">
                    </div>
                    @endif
                </div>
                @endif

                @if($isClient && $booking->status === 'pending' && !$booking->staff_id)
                <div class="flex justify-end">
                    <form action="{{ route('bookings.cancel', $booking->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking? This action cannot be undone.')">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-red-300 px-6 py-2 text-sm font-medium text-red-500 transition hover:bg-red-50">
                            <i class="fa-solid fa-xmark"></i>
                            Cancel Booking
                        </button>
                    </form>
                </div>
                @endif
            </div>

            <div class="space-y-6 xl:sticky xl:top-28">
                <div class="detail-card cleanflow-panel p-5">
                    <div class="mb-5">
                        <h2 class="text-lg font-semibold text-slate-900">Status Timeline</h2>
                        <p class="text-sm text-slate-500">Track the current stage of this booking.</p>
                    </div>
                    @php
                        $steps = [
                            ['label' => 'Booking Submitted', 'desc' => 'Your request has been received and is waiting for review.', 'icon' => 'fa-file-lines'],
                            ['label' => 'Booking Confirmed', 'desc' => 'The booking has been approved and scheduled.', 'icon' => 'fa-calendar-check'],
                            ['label' => 'Service In Progress', 'desc' => 'Your assigned cleaner is currently handling the service.', 'icon' => 'fa-soap'],
                            ['label' => 'Service Completed', 'desc' => 'Cleaning has been completed successfully.', 'icon' => 'fa-circle-check'],
                        ];
                        $statusOrder = ['pending' => 0, 'confirmed' => 1, 'in_progress' => 2, 'completed' => 3, 'cancelled' => -1];
                        $currentOrder = $statusOrder[$booking->status] ?? 0;
                    @endphp
                    @if($booking->status === 'cancelled')
                    <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-6 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-500">
                            <i class="fa-solid fa-ban"></i>
                        </div>
                        <div class="mt-3 font-semibold text-red-700">Booking Cancelled</div>
                        <div class="mt-1 text-sm text-red-600">This booking was cancelled before the service was completed.</div>
                    </div>
                    @else
                    <div class="space-y-4">
                        @foreach($steps as $index => $step)
                        @php
                            $isDone = $currentOrder > $index;
                            $isCurrent = $currentOrder === $index;
                        @endphp
                        <div class="timeline-step flex gap-4">
                            <div class="flex flex-col items-center">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full border-2 text-xs {{ $isCurrent ? 'animate-pulse border-emerald-500 bg-emerald-500 text-white' : ($isDone ? 'border-emerald-500 bg-white text-emerald-500' : 'border-slate-300 bg-white text-slate-300') }}">
                                    @if($isCurrent || $isDone)
                                    <i class="fa-solid fa-check"></i>
                                    @else
                                    <span class="h-2.5 w-2.5 rounded-full bg-current opacity-80"></span>
                                    @endif
                                </div>
                                <div class="timeline-line mt-2 h-full w-px {{ $isDone ? 'bg-emerald-300' : 'bg-slate-200' }}"></div>
                            </div>
                            <div class="pb-5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-semibold {{ $isCurrent ? 'text-slate-900' : ($isDone ? 'text-slate-800' : 'text-slate-400') }}">
                                        <i class="fa-solid {{ $step['icon'] }} mr-2 {{ $isCurrent || $isDone ? 'text-emerald-500' : 'text-slate-300' }}"></i>
                                        {{ $step['label'] }}
                                    </span>
                                    @if($isCurrent)
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Current</span>
                                    @endif
                                </div>
                                <p class="mt-2 text-sm {{ $isCurrent || $isDone ? 'text-slate-500' : 'text-slate-400' }}">{{ $step['desc'] }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                <div id="booking-messages" class="detail-card cleanflow-panel p-5">
                    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Booking Messages</h2>
                            <p class="text-sm text-slate-500">Conversation between the client and the assigned cleaner for this booking.</p>
                        </div>
                        <span class="self-start rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                            {{ $bookingMessages->count() }} message{{ $bookingMessages->count() === 1 ? '' : 's' }}
                        </span>
                    </div>

                    @if(! $booking->staff_id)
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-8 text-sm text-slate-500">
                        Messaging will become available after a cleaner is assigned to this booking.
                    </div>
                    @else
                    <div class="max-h-[360px] space-y-4 overflow-y-auto rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        @forelse($bookingMessages as $message)
                        @php
                            $sentByViewer = (int) $message->sender_id === (int) $viewer->id;
                            $senderRole = ucfirst($message->sender?->role ?? 'User');
                        @endphp
                        <div class="flex {{ $sentByViewer ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[82%] rounded-2xl px-4 py-3 text-sm shadow-sm {{ $sentByViewer ? 'bg-emerald-600 text-white' : 'border border-slate-200 bg-white text-slate-700' }}">
                                <div class="mb-1 text-[11px] font-semibold uppercase tracking-wide {{ $sentByViewer ? 'text-white/75' : 'text-slate-400' }}">
                                    {{ $message->sender?->display_name ?? 'Unknown user' }} &bull; {{ $senderRole }}
                                </div>
                                <div class="leading-6">{{ $message->message }}</div>
                                <div class="mt-2 text-[11px] {{ $sentByViewer ? 'text-white/65' : 'text-slate-400' }}">
                                    {{ $message->created_at->format('M d, Y h:i A') }}
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="px-4 py-8 text-center text-sm text-slate-500">
                            No messages yet. Use this area for booking-related coordination only.
                        </div>
                        @endforelse
                    </div>

                    @if($canSendBookingMessage)
                    <form action="{{ route('bookings.messages.store', $booking) }}" method="POST" class="mt-4">
                        @csrf
                        <label for="booking-message" class="mb-2 block text-sm font-semibold text-slate-700">Send Message</label>
                        <textarea
                            id="booking-message"
                            name="message"
                            rows="3"
                            maxlength="1000"
                            required
                            class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-hidden transition focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100"
                            placeholder="Write a message about this booking...">{{ old('message') }}</textarea>
                        <div class="mt-3 flex justify-end">
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                <i class="fa-solid fa-paper-plane"></i>
                                Send Message
                            </button>
                        </div>
                    </form>
                    @elseif($isAdmin)
                    <div class="mt-4 rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs text-slate-500">
                        Admin users can review the conversation but cannot send messages in the client-staff thread.
                    </div>
                    @else
                    <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-700">
                        Only the booking client and assigned cleaner can send messages in this thread.
                    </div>
                    @endif
                    @endif
                </div>

                <div class="detail-card cleanflow-panel p-5">
                    <div class="mb-5">
                        <h2 class="text-lg font-semibold text-slate-900">Staff Action History</h2>
                        <p class="text-sm text-slate-500">Proof uploads and staff status updates are recorded here.</p>
                    </div>
                    @if($activityLogs->count())
                    <div class="space-y-4">
                        @foreach($activityLogs as $activity)
                        <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="text-sm font-semibold text-slate-900">{{ $activity->description }}</div>
                                <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                    {{ str_replace('_', ' ', $activity->action) }}
                                </span>
                            </div>
                            <div class="mt-2 text-xs text-slate-500">
                                {{ $activity->actor_name ?? 'System' }}{{ $activity->actor_role ? ' • ' . ucfirst($activity->actor_role) : '' }}
                            </div>
                            <div class="mt-1 text-xs text-slate-400">
                                {{ $activity->created_at->format('F d, Y h:i A') }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-8 text-sm text-slate-500">
                        Staff activity history will appear here after status updates and proof uploads are recorded.
                    </div>
                    @endif
                </div>

                <div class="detail-card cleanflow-panel p-5">
                    <div class="mb-5">
                        <h2 class="text-lg font-semibold text-slate-900">Price Breakdown</h2>
                        <p class="text-sm text-slate-500">Clear basis of computation for this booking quotation.</p>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-start justify-between gap-3 text-sm">
                            <div>
                                <span class="text-slate-500">Base service price</span>
                                <div class="text-xs text-slate-400">{{ $booking->service_label }}</div>
                            </div>
                            <span class="font-medium text-slate-800">&#8369;{{ number_format($booking->base_price ?? 0, 2) }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-3 text-sm">
                            <div>
                                <span class="text-slate-500">Property type adjustment</span>
                                <div class="text-xs text-slate-400">{{ $propertyTypeLabel }}</div>
                            </div>
                            <span class="font-medium text-slate-800">{{ ($booking->property_fee ?? 0) > 0 ? '+' : '' }}&#8369;{{ number_format($booking->property_fee ?? 0, 2) }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-3 text-sm">
                            <div>
                                <span class="text-slate-500">Rooms adjustment</span>
                                <div class="text-xs text-slate-400">{{ max(0, $booking->rooms - 1) }} extra room{{ max(0, $booking->rooms - 1) === 1 ? '' : 's' }} x &#8369;50</div>
                            </div>
                            <span class="font-medium text-slate-800">{{ ($booking->rooms_fee ?? 0) > 0 ? '+' : '' }}&#8369;{{ number_format($booking->rooms_fee ?? 0, 2) }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-3 text-sm">
                            <div>
                                <span class="text-slate-500">Bathrooms adjustment</span>
                                <div class="text-xs text-slate-400">{{ max(0, $booking->bathrooms - 1) }} extra bathroom{{ max(0, $booking->bathrooms - 1) === 1 ? '' : 's' }} x &#8369;100</div>
                            </div>
                            <span class="font-medium text-slate-800">{{ ($booking->bathrooms_fee ?? 0) > 0 ? '+' : '' }}&#8369;{{ number_format($booking->bathrooms_fee ?? 0, 2) }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-3 text-sm">
                            <div>
                                <span class="text-slate-500">Floor area adjustment</span>
                                <div class="text-xs text-slate-400">
                                    {{ $billableFloorArea }} billable sqm x &#8369;{{ number_format($floorAreaRate, 2) }}/sqm after {{ $includedFloorArea }} sqm included
                                </div>
                            </div>
                            <span class="font-medium text-slate-800">{{ ($booking->floor_area_fee ?? 0) > 0 ? '+' : '' }}&#8369;{{ number_format($booking->floor_area_fee ?? 0, 2) }}</span>
                        </div>
                        <div class="space-y-2 border-t border-slate-100 pt-4">
                            <div class="flex items-start justify-between gap-3 text-sm">
                                <div>
                                    <span class="text-slate-500">Add-ons</span>
                                    <div class="text-xs text-slate-400">
                                        @if(count($selectedAddOns))
                                            {{ count($selectedAddOns) }} selected extra task{{ count($selectedAddOns) === 1 ? '' : 's' }}
                                        @else
                                            No add-ons added
                                        @endif
                                    </div>
                                </div>
                                <span class="font-medium text-slate-800">{{ ($booking->add_ons_fee ?? 0) > 0 ? '+' : '' }}&#8369;{{ number_format($booking->add_ons_fee ?? 0, 2) }}</span>
                            </div>
                            @foreach($selectedAddOns as $addOn)
                            <div class="flex items-center justify-between gap-3 pl-4 text-xs text-slate-500">
                                <span>{{ $addOn['label'] }}</span>
                                <span>&#8369;{{ number_format($addOn['price'], 2) }}</span>
                            </div>
                            @endforeach
                        </div>
                        <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                            <span class="text-lg font-bold text-slate-900">Total Amount</span>
                            <span class="text-lg font-bold text-emerald-600">&#8369;{{ number_format($booking->price, 2) }}</span>
                        </div>
                        <div class="rounded-xl border border-yellow-100 bg-yellow-50 p-3 text-xs text-yellow-700">
                            @if($booking->payment_method === 'on_site_cash')
                            Cash payment will be collected and marked as paid once the service is completed. This total is based on the service type, property type, rooms, bathrooms, floor area, and selected add-ons.
                            @else
                            This booking was recorded with {{ strtolower($paymentMethodLabel) }} and stores a digital payment reference for admin and client tracking. This total is based on the service type, property type, rooms, bathrooms, floor area, and selected add-ons.
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@php
    $barangayCenters = config('cleanflow.barangay_centers', []);
    $defaultMapCenter = config('cleanflow.map.center', ['lat' => 7.9047, 'lng' => 125.0940]);
@endphp

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>
const bookingId = @json($booking->id);
const bookingStatus = @json($booking->status);
const userRole = @json($viewer->role);
const staffName = @json($booking->staff?->first_name ?? 'Staff');
const serviceAddress = @json($booking->street_address . ', ' . ucfirst($booking->barangay));
const barangayCenters = @json($barangayCenters);
const bookingBarangay = @json($booking->barangay);
const defaultMapCenter = @json($defaultMapCenter);
const destinationCenter = barangayCenters[bookingBarangay] || defaultMapCenter;
const destLat = destinationCenter.lat;
const destLng = destinationCenter.lng;

let clientMap = null;
let clientStaffMarker = null;
let clientDestMarker = null;
let clientLine = null;

let adminMap = null;
let adminStaffMarker = null;
let adminDestMarker = null;
let adminLine = null;

function makeTileLayer() {
    return L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    });
}

function makeStaffIcon() {
    return L.divIcon({
        html: '<div class="cleanflow-map-marker cleanflow-map-marker--staff">S</div>',
        iconSize: [26, 26],
        iconAnchor: [13, 13],
        className: ''
    });
}

function makeDestIcon() {
    return L.divIcon({
        html: '<div class="cleanflow-map-marker cleanflow-map-marker--destination">C</div>',
        iconSize: [26, 26],
        iconAnchor: [13, 13],
        className: ''
    });
}

function updateClientStatus(text, color, background) {
    const statusEl = document.getElementById('location-status');
    if (statusEl) {
        statusEl.textContent = text;
        statusEl.style.color = color;
        statusEl.style.background = background;
    }
}

async function showClientMap(lat, lng, updatedAt) {
    const noMsg = document.getElementById('no-location-msg');
    const mapContainer = document.getElementById('map-container');
    const arrivalStatus = document.getElementById('arrival-status');

    if (noMsg) noMsg.style.display = 'none';
    if (mapContainer) mapContainer.style.display = 'block';
    if (arrivalStatus) arrivalStatus.style.display = 'flex';

    if (!clientMap) {
        clientMap = L.map('google-map-frame', {
            scrollWheelZoom: true,
            zoomControl: true,
            dragging: true
        });
        makeTileLayer().addTo(clientMap);

        clientDestMarker = L.marker([destLat, destLng], { icon: makeDestIcon() })
            .addTo(clientMap)
            .bindPopup('Your Address: ' + serviceAddress);
    }

    if (clientStaffMarker) {
        clientStaffMarker.setLatLng([lat, lng]);
    } else {
        clientStaffMarker = L.marker([lat, lng], { icon: makeStaffIcon() })
            .addTo(clientMap)
            .bindPopup(staffName + ' is on the way')
            .openPopup();
    }

    try {
        const routeRes = await fetch(`https://router.project-osrm.org/route/v1/driving/${lng},${lat};${destLng},${destLat}?overview=full&geometries=geojson`, {
            mode: 'cors',
            headers: { 'Accept': 'application/json' }
        });
        const routeData = await routeRes.json();

        if (routeData.code === 'Ok' && routeData.routes.length > 0) {
            const coords = routeData.routes[0].geometry.coordinates.map(c => [c[1], c[0]]);
            const distance = (routeData.routes[0].distance / 1000).toFixed(1);
            const duration = Math.round(routeData.routes[0].duration / 60);

            if (clientLine) clientMap.removeLayer(clientLine);
            clientLine = L.polyline(coords, {
                color: '#1D9E75',
                weight: 5,
                opacity: 0.8
            }).addTo(clientMap);

            const info = document.getElementById('client-route-info');
            if (info) {
                info.style.display = 'block';
                info.innerHTML = `<strong>${distance} km</strong> away &nbsp;|&nbsp; <strong>${duration} min</strong> estimated arrival`;
            }

            const arrivalText = document.getElementById('arrival-text');
            const arrivalSub = document.getElementById('arrival-sub');

            if (arrivalText) {
                if (distance < 0.3) {
                    arrivalText.innerHTML = '<strong>Staff has arrived!</strong>';
                    arrivalText.style.color = '#09637e';
                    if (arrivalSub) arrivalSub.textContent = 'Your cleaner is at your location.';
                } else if (duration <= 5) {
                    arrivalText.innerHTML = '<strong>Staff is arriving soon!</strong>';
                    arrivalText.style.color = '#088395';
                    if (arrivalSub) arrivalSub.textContent = `About ${duration} min away - ${distance} km`;
                } else {
                    arrivalText.innerHTML = '<strong>Staff is on the way</strong>';
                    arrivalText.style.color = '#088395';
                    if (arrivalSub) arrivalSub.textContent = `About ${duration} min away - ${distance} km`;
                }
            }

        } else {
            if (clientLine) clientMap.removeLayer(clientLine);
            clientLine = L.polyline([[lat, lng], [destLat, destLng]], {
                color: '#088395', weight: 3, dashArray: '6, 8', opacity: 0.7
            }).addTo(clientMap);
        }
    } catch (e) {
        if (clientLine) clientMap.removeLayer(clientLine);
        clientLine = L.polyline([[lat, lng], [destLat, destLng]], {
            color: '#088395', weight: 3, dashArray: '6, 8', opacity: 0.7
        }).addTo(clientMap);
    }

    const bounds = L.latLngBounds([[lat, lng], [destLat, destLng]]);
    clientMap.fitBounds(bounds, { padding: [40, 40] });
    requestAnimationFrame(() => clientMap.invalidateSize());
    updateClientStatus('Live - Updated ' + (updatedAt || 'just now'), '#088395', '#e7f4f6');
}

async function showAdminMap(lat, lng, updatedAt) {
    const noMsg = document.getElementById('admin-no-location');
    const mapContainer = document.getElementById('admin-map-container');
    const info = document.getElementById('admin-location-info');
    const statusEl = document.getElementById('admin-location-status');

    if (noMsg) noMsg.style.display = 'none';
    if (mapContainer) mapContainer.style.display = 'block';
    if (info) info.textContent = `\u{1F4CD} Last updated: ${updatedAt || 'just now'} \u2014 Coordinates: ${parseFloat(lat).toFixed(5)}, ${parseFloat(lng).toFixed(5)}`;
    if (statusEl) {
        statusEl.textContent = '\u{1F7E2} Live';
        statusEl.style.color = '#088395';
        statusEl.style.background = '#e7f4f6';
    }

    if (!adminMap) {
        adminMap = L.map('admin-google-map-frame', { scrollWheelZoom: true });
        makeTileLayer().addTo(adminMap);

        adminDestMarker = L.marker([destLat, destLng], { icon: makeDestIcon() })
            .addTo(adminMap)
            .bindPopup('Client destination: ' + serviceAddress);
    }

    if (adminStaffMarker) {
        adminStaffMarker.setLatLng([lat, lng]);
    } else {
        adminStaffMarker = L.marker([lat, lng], { icon: makeStaffIcon() })
            .addTo(adminMap)
            .bindPopup(staffName + ' is here')
            .openPopup();
    }

    try {
        const routeRes = await fetch(`https://router.project-osrm.org/route/v1/driving/${lng},${lat};${destLng},${destLat}?overview=full&geometries=geojson`, {
            mode: 'cors',
            headers: { 'Accept': 'application/json' }
        });
        const routeData = await routeRes.json();

        if (routeData.code === 'Ok' && routeData.routes.length > 0) {
            const coords = routeData.routes[0].geometry.coordinates.map(c => [c[1], c[0]]);
            const distance = (routeData.routes[0].distance / 1000).toFixed(1);
            const duration = Math.round(routeData.routes[0].duration / 60);

            if (adminLine) adminMap.removeLayer(adminLine);
            adminLine = L.layerGroup([
                L.polyline(coords, {
                    color: '#a16207',
                    weight: 8,
                    opacity: 0.32
                }),
                L.polyline(coords, {
                    color: '#facc15',
                    weight: 5,
                    opacity: 0.95
                }),
            ]).addTo(adminMap);

            if (info) {
                info.innerHTML = `Last updated: ${updatedAt || 'just now'} - Coordinates: ${parseFloat(lat).toFixed(5)}, ${parseFloat(lng).toFixed(5)}`;
            }
        } else {
            if (adminLine) adminMap.removeLayer(adminLine);
            adminLine = L.layerGroup([
                L.polyline([[lat, lng], [destLat, destLng]], {
                    color: '#a16207', weight: 6, opacity: 0.28
                }),
                L.polyline([[lat, lng], [destLat, destLng]], {
                    color: '#facc15', weight: 3, dashArray: '6, 8', opacity: 0.85
                }),
            ]).addTo(adminMap);
        }
    } catch (e) {
        if (adminLine) adminMap.removeLayer(adminLine);
        adminLine = L.layerGroup([
            L.polyline([[lat, lng], [destLat, destLng]], {
                color: '#a16207', weight: 6, opacity: 0.28
            }),
            L.polyline([[lat, lng], [destLat, destLng]], {
                color: '#facc15', weight: 3, dashArray: '6, 8', opacity: 0.85
            }),
        ]).addTo(adminMap);
    }

    const bounds = L.latLngBounds([[lat, lng], [destLat, destLng]]);
    adminMap.fitBounds(bounds, { padding: [40, 40] });
    requestAnimationFrame(() => adminMap.invalidateSize());
}

async function pollLocation() {
    try {
        const res = await fetch(`/bookings/${bookingId}/location/current`);
        if (!res.ok) return;
        const data = await res.json();

        if (!data.tracking) {
            updateClientStatus('Waiting for location...', '#94a3b8', '#f8fafc');
            return;
        }

        const lat = parseFloat(data.latitude);
        const lng = parseFloat(data.longitude);

        if (document.getElementById('google-map-frame')) {
            await showClientMap(lat, lng, data.updated_at);
        }
        if (document.getElementById('admin-google-map-frame')) {
            await showAdminMap(lat, lng, data.updated_at);
        }

    } catch (error) {
        console.error('Poll error:', error);
    }
}

function setRating(value) {
    const starsInput = document.getElementById('stars-input');
    if (!starsInput) return;
    starsInput.value = value;
    document.querySelectorAll('.star-btn').forEach((btn) => {
        btn.style.color = parseInt(btn.dataset.value, 10) <= value ? '#088395' : '#e2e8f0';
    });
}

function previewPhoto(input) {
    const preview = document.getElementById('photo-preview');
    const placeholder = document.getElementById('photo-placeholder');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

if (['confirmed', 'in_progress'].includes(bookingStatus)) {
    pollLocation();
    window.setInterval(pollLocation, 10000);
}
</script>
@endpush
