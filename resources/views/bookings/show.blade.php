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
        'confirmed' => ['label' => 'Confirmed', 'badge' => 'bg-blue-50 text-blue-700', 'bar' => 'bg-blue-500', 'icon' => 'fa-calendar-check'],
        'in_progress' => ['label' => 'In Progress', 'badge' => 'bg-teal-100 text-teal-700', 'bar' => 'bg-teal-500', 'icon' => 'fa-soap'],
        'completed' => ['label' => 'Completed', 'badge' => 'bg-emerald-100 text-emerald-700', 'bar' => 'bg-emerald-600', 'icon' => 'fa-circle-check'],
        'cancelled' => ['label' => 'Cancelled', 'badge' => 'bg-red-100 text-red-700', 'bar' => 'bg-red-600', 'icon' => 'fa-ban'],
    ];
    $sc = $statusConfig[$booking->status] ?? ['label' => 'Unknown', 'badge' => 'bg-slate-100 text-slate-700', 'bar' => 'bg-slate-500', 'icon' => 'fa-circle-question'];
    $bookingCode = 'CF-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT);
    $scheduledDate = \Carbon\Carbon::parse($booking->scheduled_date);
    $scheduledTime = \Carbon\Carbon::parse($booking->scheduled_time);
    $homeUrl = $isAdmin ? route('admin.dashboard') : ($isStaff ? route('staff.dashboard') : route('client.dashboard'));
    $listLabel = $isAdmin ? 'Bookings' : ($isStaff ? 'Assigned Bookings' : 'My Bookings');
    $propertyTypeLabel = \App\Models\Booking::propertyTypeLabel($booking->property_type);
    $selectedAddOns = \App\Models\Booking::addOnBreakdown($booking->add_ons ?? [], $booking->add_on_quantities ?? []);
    $includedFloorArea = \App\Models\Booking::includedFloorArea();
    $floorArea = (int) ($booking->floor_area ?? 0);
    $cleanerCapacity = \App\Models\Service::cleanerCapacityForSlug($booking->service_type);
    $requiredCleaners = (int) ($booking->required_cleaners ?: \App\Models\Booking::requiredCleanerCountForService($booking->service_type, $floorArea));
    $isPerSquareMeterService = \App\Models\Service::usesPerSquareMeterPricing($booking->service_type);
    $isFlatRateRangeService = \App\Models\Service::usesFlatRateRangePricing($booking->service_type);
    $billableFloorArea = \App\Models\Booking::billableFloorAreaForService($booking->service_type, $floorArea);
    $floorAreaRate = \App\Models\Booking::floorAreaRateForService($booking->service_type);
    $paymentMethod = $booking->payment?->method ?? 'on_site_cash';
    $paymentStatus = $booking->payment?->status ?? 'pending';
    $paymentReference = $booking->payment?->reference;
    $paymentPaidAt = $booking->payment?->paid_at;
    $cashProofStatus = $booking->payment?->cash_proof_status;
    $cashProofStatusLabel = match ($cashProofStatus) {
        'pending' => 'Awaiting admin review',
        'approved' => 'Approved',
        'rejected' => 'Needs correction',
        default => null,
    };
    $paymentMethodLabel = \App\Models\Booking::paymentMethodLabel($paymentMethod);
    $paymentStatusLabel = \App\Models\Booking::paymentStatusLabel($paymentStatus);
    $servicePlanLabel = \App\Models\Booking::servicePlanLabel($booking->service_plan);
    $subscriptionSummary = $booking->subscriptionSummary();
    $paymentStatusClasses = [
        'paid' => 'bg-emerald-100 text-emerald-700',
        'pending' => 'bg-amber-100 text-amber-700',
    ];
    $staffInitials = $booking->staff
        ? strtoupper(substr($booking->staff->first_name ?? 'S', 0, 1) . substr($booking->staff->last_name ?? 'T', 0, 1))
        : 'NA';
    $beforeProofs = $booking->serviceProofs->where('stage', 'before')->where('media_type', 'image')->values();
    $afterProofs = $booking->serviceProofs->where('stage', 'after')->where('media_type', 'image')->values();
    $completionVideos = $booking->serviceProofs->where('stage', 'after')->where('media_type', 'video')->values();
    $staffAssignments = $booking->staffAssignments ?? collect();
    $myStaffAssignments = $staffAssignments->filter(fn ($assignment) => (int) $assignment->staff_id === (int) $viewer->id)->values();
    $activityLogs = $booking->activityLogs;
    $directionsDestination = ($booking->service_latitude && $booking->service_longitude)
        ? $booking->service_latitude . ',' . $booking->service_longitude
        : $booking->street_address . ', ' . ucfirst($booking->barangay) . ', Valencia City, Bukidnon';
    $directionsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($directionsDestination);
    $canOpenLiveVideo = $booking->canAccessLiveVideo($viewer) && (! $isClient || $booking->dailyRoomIsActive());
    $liveVideoLabel = $isClient ? 'Watch Live Video' : 'Open Live Video';
    $canUseBookingMessages = ($isClient || $isStaff) && $booking->staff_id;
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
                <div class="flex flex-wrap gap-3 self-start lg:self-auto">
                    @if($canOpenLiveVideo)
                    <a href="{{ route('bookings.live-video', $booking) }}" class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-3 text-sm font-bold text-blue-700 shadow-lg transition hover:bg-blue-50">
                        <i class="fa-solid fa-video"></i>
                        {{ $liveVideoLabel }}
                    </a>
                    @endif
                    <a href="{{ $backUrl }}" class="cleanflow-ghost-button">
                    <i class="fa-solid fa-arrow-left"></i>
                    {{ $backLabel }}
                    </a>
                </div>
            </div>
        </div>

        <div class="booking-show-summary-grid mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="cleanflow-panel border-l-4 border-blue-300 bg-blue-50/80 p-5">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Current Status</div>
                <div class="mt-3 flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-blue-600 shadow-sm">
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

            <div class="cleanflow-panel border-l-4 border-blue-300 bg-blue-50/80 p-5">
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Assigned Cleaner</div>
                <div class="mt-3 flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-blue-600 shadow-sm">
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

        @if($staffAssignments->count() && ($isAdmin || $isStaff))
        <div class="cleanflow-panel mb-6 border-l-4 border-violet-300 bg-violet-50/70 p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-bold uppercase tracking-[0.18em] text-violet-700">Specialist task plan</div>
                    <h2 class="mt-2 text-lg font-black text-slate-950">{{ $staffAssignments->count() }} cleaner{{ $staffAssignments->count() === 1 ? '' : 's' }} assigned</h2>
                </div>
                <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-bold text-violet-700">{{ $isStaff ? 'Your assigned tasks' : 'Admin view' }}</span>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @foreach($staffAssignments as $assignment)
                @if($isAdmin || $myStaffAssignments->contains('id', $assignment->id))
                <div class="rounded-xl border border-violet-100 bg-white p-3">
                    <div class="font-bold text-slate-900">{{ $assignment->staff?->display_name ?? 'Assigned cleaner' }}</div>
                    <div class="mt-1 text-sm font-semibold text-violet-700">{{ $assignment->taskGroupLabel() }}</div>
                    @if($assignment->task_notes)
                    <div class="mt-2 text-sm leading-5 text-slate-600">{{ $assignment->task_notes }}</div>
                    @endif
                </div>
                @endif
                @endforeach
            </div>
        </div>
        @endif

        @if($booking->dispute_status)
            <div class="cleanflow-panel mb-6 border-l-4 {{ $booking->hasOpenDispute() ? 'border-red-400 bg-red-50/80' : 'border-slate-300 bg-slate-50/80' }} p-5">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-[0.18em] {{ $booking->hasOpenDispute() ? 'text-red-700' : 'text-slate-500' }}">Dispute Status</div>
                        <h2 class="mt-2 text-lg font-black text-slate-950">{{ \App\Models\Booking::disputeStatusLabel($booking->dispute_status) }}</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $booking->disputeReasonLabel() }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-700">{{ $booking->dispute_description }}</p>
                    </div>
                    @if($booking->disputeResolutionLabel())
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700">
                            <div class="text-xs font-bold uppercase text-slate-400">Resolution</div>
                            <div class="mt-1 font-bold text-slate-900">{{ $booking->disputeResolutionLabel() }}</div>
                            @if($booking->dispute_admin_notes)
                                <div class="mt-2 text-xs leading-5 text-slate-500">{{ $booking->dispute_admin_notes }}</div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @elseif($isClient && $booking->canClientOpenDispute($viewer))
            <div class="cleanflow-panel mb-6 border-l-4 border-red-300 bg-red-50/70 p-5">
                <h2 class="text-lg font-black text-slate-950">Report a service issue</h2>
                <p class="mt-1 text-sm leading-6 text-slate-600">Opening a dispute will hold provider payout while admin reviews your complaint.</p>
                <form action="{{ route('bookings.dispute', $booking->id) }}" method="POST" class="mt-4 grid gap-4 lg:grid-cols-[240px_minmax(0,1fr)_auto] lg:items-end">
                    @csrf
                    <div>
                        <label for="dispute_reason" class="text-xs font-bold uppercase text-slate-500">Reason</label>
                        <select id="dispute_reason" name="dispute_reason" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-hidden">
                            @foreach(\App\Models\Booking::disputeReasons() as $reason => $label)
                                <option value="{{ $reason }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="dispute_description" class="text-xs font-bold uppercase text-slate-500">Details</label>
                        <textarea id="dispute_description" name="dispute_description" rows="2" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-hidden" placeholder="Describe what happened and what resolution you expect."></textarea>
                    </div>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-red-700">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Submit dispute
                    </button>
                </form>
            </div>
        @endif

        <div class="booking-show-grid grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
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
                                <div class="mt-1 text-sm font-semibold text-blue-700">{{ $requiredCleaners }} cleaner{{ $requiredCleaners === 1 ? '' : 's' }} recommended ({{ $cleanerCapacity }} sqm per cleaner)</div>
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
                                @if($isAdmin || $isStaff)
                                    @if(filled($booking->service_latitude) && filled($booking->service_longitude))
                                        <div class="mt-4 overflow-hidden rounded-2xl border border-blue-200 bg-white">
                                            <div
                                                id="client-location-map"
                                                class="client-location-map"
                                                data-lat="{{ $booking->service_latitude }}"
                                                data-lng="{{ $booking->service_longitude }}"
                                                aria-label="Client service location map"
                                            ></div>
                                            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-blue-100 px-3 py-2">
                                                <span class="text-[11px] font-semibold text-slate-500">Exact client pin saved</span>
                                                <a href="{{ $directionsUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-[11px] font-bold text-blue-700 hover:text-blue-900">
                                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                                    Open in Google Maps
                                                </a>
                                            </div>
                                        </div>
                                    @else
                                        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs leading-5 text-amber-800">
                                            No exact client pin was saved. Use the written address and ask the client to confirm the location.
                                        </div>
                                    @endif
                                @endif
                                @if($isStaff)
                                <a href="{{ $directionsUrl }}" target="_blank" rel="noopener" class="mt-4 inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 transition hover:bg-blue-100">
                                    <i class="fa-solid fa-route"></i>
                                    Get Directions
                                </a>
                                @endif
                            </div>

                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                                <div class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <i class="fa-solid fa-puzzle-piece text-emerald-500"></i>
                                    Add-ons
                                </div>
                                @if(count($selectedAddOns))
                                    <div class="space-y-1">
                                        @foreach($selectedAddOns as $addOn)
                                        <div class="text-sm font-medium text-slate-900">{{ $addOn['label'] }} @if(($addOn['quantity'] ?? 1) > 1)<span class="text-xs font-semibold text-violet-700">× {{ $addOn['quantity'] }}</span>@endif</div>
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
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $paymentStatusClasses[$paymentStatus] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $paymentStatusLabel }}
                                    </span>
                                </div>
                                <div class="mt-1 text-sm text-slate-500">
                                    @if($paymentReference)
                                    Reference: {{ $paymentReference }}
                                    @else
                                    A payment reference will appear here once one is recorded.
                                    @endif
                                </div>
                                <div class="mt-1 text-sm text-slate-500">
                                    @if($paymentPaidAt)
                                    Paid on {{ $paymentPaidAt->format('F d, Y h:i A') }}
                                    @elseif($paymentMethod === 'on_site_cash')
                                    Cash will be recorded after service completion.
                                    @else
                                    Payment is still waiting for confirmation.
                                    @endif
                                </div>
                                @if($paymentStatus === 'paid' && $paymentReference)
                                    @if($paymentMethod === 'on_site_cash' && $booking->payment?->receipt_number)
                                        <div class="mt-2 text-sm text-emerald-700">
                                            Cash receipt: <span class="font-mono font-semibold">{{ $booking->payment->receipt_number }}</span>
                                        </div>
                                    @endif
                                    <a href="{{ route('bookings.receipt', $booking->id) }}" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-white px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50">
                                        <i class="fas fa-receipt"></i>
                                        View / print receipt
                                    </a>
                                @endif
                                @if($isClient && $paymentMethod === 'on_site_cash' && $paymentStatus !== 'paid')
                                    <div class="mt-4 rounded-xl border border-blue-100 bg-blue-50/70 p-3">
                                        <div class="flex items-center gap-2 text-sm font-bold text-blue-900">
                                            <i class="fas fa-receipt"></i>
                                            Cash payment proof
                                        </div>
                                        <p class="mt-1 text-xs leading-5 text-blue-800">Upload the receipt provided by the cleaner. Admin will review it and update your payment status.</p>
                                        @if($cashProofStatusLabel)
                                            <div class="mt-2 text-xs font-bold {{ $cashProofStatus === 'rejected' ? 'text-red-700' : 'text-blue-800' }}">
                                                Status: {{ $cashProofStatusLabel }}
                                            </div>
                                        @endif
                                        @if($cashProofStatus === 'rejected' && $booking->payment?->cash_proof_rejection_reason)
                                            <div class="mt-2 rounded-lg border border-red-100 bg-red-50 p-2 text-xs leading-5 text-red-700">
                                                {{ $booking->payment->cash_proof_rejection_reason }}
                                            </div>
                                        @endif
                                        @if($cashProofStatus === 'pending')
                                            <div class="mt-2 text-xs text-slate-600">Your uploaded receipt is securely stored and waiting for admin review.</div>
                                        @elseif($booking->status !== 'completed')
                                            <div class="mt-2 text-xs text-slate-600">The upload button will appear after the cleaner marks the service as completed.</div>
                                        @else
                                            <form action="{{ route('bookings.cash-payment-proof.upload', $booking->id) }}" method="POST" enctype="multipart/form-data" class="mt-3">
                                                @csrf
                                                <label class="mb-1 block text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500" for="cash_payment_proof">Receipt image or PDF</label>
                                                <input id="cash_payment_proof" type="file" name="cash_payment_proof" accept=".jpg,.jpeg,.png,.webp,.pdf" required class="w-full rounded-lg border border-dashed border-blue-200 bg-white px-2.5 py-2 text-xs text-slate-600 file:mr-2 file:rounded-md file:border-0 file:bg-blue-700 file:px-2 file:py-1 file:text-[11px] file:font-bold file:text-white">
                                                @error('cash_payment_proof')
                                                    <div class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</div>
                                                @enderror
                                                <button type="submit" class="mt-2 inline-flex items-center gap-2 rounded-lg bg-blue-700 px-3 py-2 text-xs font-bold text-white transition hover:bg-blue-800">
                                                    <i class="fas fa-upload"></i>
                                                    Upload receipt
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @elseif($isAdmin && $paymentMethod === 'on_site_cash' && $booking->payment?->cash_proof_path)
                                    @php
                                        $cashProofStatus = $booking->payment->cash_proof_status ?: 'submitted';
                                    @endphp
                                    <div class="mt-4 rounded-xl border border-blue-100 bg-blue-50/70 p-3">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="text-sm font-bold text-blue-900"><i class="fas fa-receipt mr-1"></i> Client cash payment proof</div>
                                            <span class="rounded-full px-2 py-1 text-[10px] font-bold {{ $cashProofStatus === 'pending' ? 'bg-amber-100 text-amber-700' : ($cashProofStatus === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700') }}">{{ ucfirst($cashProofStatus) }}</span>
                                        </div>
                                        <div class="mt-1 text-xs text-slate-600">{{ $booking->payment->cash_proof_original_name ?: 'Uploaded receipt' }}</div>
                                        <a href="{{ route('bookings.cash-payment-proof.download', $booking->id) }}" class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-blue-700 underline hover:text-blue-900">
                                            <i class="fas fa-download"></i>
                                            Download private proof
                                        </a>
                                        @if($cashProofStatus === 'pending')
                                            <form action="{{ route('admin.bookings.cash-payment-proof.review', $booking->id) }}" method="POST" class="mt-3 space-y-2">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="decision" value="approve">
                                                <div class="grid gap-2 sm:grid-cols-2">
                                                    <input type="number" name="payment_collected_amount" min="0.01" step="0.01" value="{{ old('payment_collected_amount', $booking->price) }}" placeholder="Cash amount" required class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs focus:border-blue-500 focus:outline-hidden">
                                                    <input type="datetime-local" name="payment_collected_at" value="{{ old('payment_collected_at', now()->format('Y-m-d\TH:i')) }}" required class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs focus:border-blue-500 focus:outline-hidden">
                                                </div>
                                                <input type="text" name="payment_receipt_notes" placeholder="Optional admin note" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs focus:border-blue-500 focus:outline-hidden">
                                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-emerald-700">
                                                    <i class="fas fa-circle-check"></i>
                                                    Approve and mark paid
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.bookings.cash-payment-proof.review', $booking->id) }}" method="POST" class="mt-2 space-y-2">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="decision" value="reject">
                                                <input type="text" name="cash_proof_rejection_reason" required minlength="5" maxlength="1000" placeholder="Reason if rejecting" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs focus:border-red-500 focus:outline-hidden">
                                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-bold text-red-700 transition hover:bg-red-50">
                                                    <i class="fas fa-rotate-left"></i>
                                                    Reject and request replacement
                                                </button>
                                            </form>
                                        @elseif($cashProofStatus === 'rejected' && $booking->payment->cash_proof_rejection_reason)
                                            <div class="mt-2 rounded-lg border border-red-100 bg-red-50 p-2 text-xs leading-5 text-red-700">{{ $booking->payment->cash_proof_rejection_reason }}</div>
                                        @endif
                                    </div>
                                @elseif($isClient && $paymentMethod === 'on_site_cash' && $paymentStatus === 'paid' && $cashProofStatus)
                                    <div class="mt-3 text-xs text-emerald-700">Cash receipt proof: {{ $cashProofStatusLabel }}</div>
                                @endif
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

                <div id="proof-of-service" class="detail-card cleanflow-panel p-6">
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
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white shadow-sm">
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
                                <div class="mt-1 text-sm text-yellow-700">Your booking time is confirmed. Our admin team will assign a cleaner before the service starts.</div>
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
                        <img src="{{ route('bookings.rating-photo', $booking) }}" alt="Rating photo" class="max-h-64 rounded-2xl border border-slate-200 object-cover">
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
                                    <img src="{{ route('bookings.service-proof', [$booking, $proof]) }}" alt="Before service proof" class="h-44 w-full object-cover">
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
                                    <img src="{{ route('bookings.service-proof', [$booking, $proof]) }}" alt="After service proof" class="h-44 w-full object-cover">
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
                                    <source src="{{ route('bookings.service-proof', [$booking, $proof]) }}">
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
                        <div id="arrival-status" class="hidden rounded-xl border border-emerald-200 bg-emerald-50 p-4">
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
                        <div id="arrival-status" class="hidden rounded-xl border border-emerald-200 bg-emerald-50 p-4">
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

                @if($isClient || $isStaff)
                <div id="booking-messages" class="detail-card cleanflow-panel p-6">
                    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Booking Messages</h2>
                            <p class="text-sm text-slate-500">Coordinate directly about this booking.</p>
                        </div>
                        <span class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                            <i class="fa-solid fa-message"></i>
                            {{ $booking->messages->count() }} message{{ $booking->messages->count() === 1 ? '' : 's' }}
                        </span>
                    </div>

                    @if($canUseBookingMessages)
                        <div class="max-h-96 space-y-3 overflow-y-auto rounded-2xl border border-slate-100 bg-slate-50 p-4">
                            @forelse($booking->messages as $bookingMessage)
                                @php
                                    $messageIsMine = (int) $bookingMessage->sender_id === (int) $viewer->id;
                                @endphp
                                <div class="flex {{ $messageIsMine ? 'justify-end' : 'justify-start' }}">
                                    <div class="max-w-[min(32rem,85%)] rounded-2xl px-4 py-3 {{ $messageIsMine ? 'bg-blue-600 text-white' : 'border border-slate-200 bg-white text-slate-700' }}">
                                        <div class="mb-1 flex items-center gap-2 text-xs font-bold {{ $messageIsMine ? 'text-blue-50' : 'text-slate-500' }}">
                                            <span>{{ $messageIsMine ? 'You' : ($bookingMessage->sender?->display_name ?? 'User') }}</span>
                                            <span class="{{ $messageIsMine ? 'text-blue-100' : 'text-slate-300' }}">&bull;</span>
                                            <span>{{ $bookingMessage->created_at->format('M d, h:i A') }}</span>
                                        </div>
                                        <div class="whitespace-pre-line text-sm leading-6">{{ $bookingMessage->message }}</div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-5 py-8 text-center text-sm text-slate-500">
                                    No messages yet. Send the first update for this booking.
                                </div>
                            @endforelse
                        </div>

                        <form action="{{ route('bookings.messages.store', $booking) }}" method="POST" class="mt-4 space-y-3">
                            @csrf
                            <label for="booking-message-input" class="sr-only">Message</label>
                            <textarea
                                id="booking-message-input"
                                name="message"
                                rows="3"
                                maxlength="1000"
                                placeholder="Write a message about this booking..."
                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 outline-hidden transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100"
                            >{{ old('message') }}</textarea>
                            @error('message')
                                <p class="text-sm text-red-500">{{ $message }}</p>
                            @enderror
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-700">
                                <i class="fa-solid fa-paper-plane"></i>
                                Send message
                            </button>
                        </form>
                    @else
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-8 text-center text-sm text-slate-500">
                            Messaging becomes available after a cleaner is assigned to this booking.
                        </div>
                    @endif
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
                        <img src="{{ route('bookings.rating-photo', $booking) }}" alt="Rating photo" class="max-h-64 rounded-2xl border border-slate-200 object-cover">
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
                        <p class="text-sm text-slate-500">Saved pricing snapshot for this booking, including each applied charge.</p>
                    </div>

                    <div class="space-y-4">
                        @unless($isPerSquareMeterService)
                        <div class="flex items-start justify-between gap-3 text-sm">
                            <div>
                                <span class="text-slate-500">{{ $isFlatRateRangeService ? 'Flat service price' : 'Base service price' }}</span>
                                <div class="text-xs text-slate-400">
                                    @if($isFlatRateRangeService)
                                        Standard 2-3 bedroom home flat-rate package
                                    @else
                                        {{ $booking->service_label }}
                                    @endif
                                </div>
                            </div>
                            <span class="font-medium text-slate-800">&#8369;{{ number_format($booking->base_price ?? 0, 2) }}</span>
                        </div>
                        @endunless
                        <div class="flex items-start justify-between gap-3 text-sm">
                            <div>
                                <span class="text-slate-500">Property charge</span>
                                <div class="text-xs text-slate-400">{{ $propertyTypeLabel }}</div>
                            </div>
                            <span class="font-medium text-slate-800">{{ ($booking->property_fee ?? 0) > 0 ? '+' : '' }}&#8369;{{ number_format($booking->property_fee ?? 0, 2) }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-3 text-sm">
                            <div>
                            <span class="text-slate-500">Floor area charge</span>
                                <div class="text-xs text-slate-400">
                                    @if($isFlatRateRangeService)
                                        Included in the flat-rate package
                                    @elseif($isPerSquareMeterService)
                                        {{ $billableFloorArea }} sqm x &#8369;{{ number_format($floorAreaRate, 2) }}/sqm
                                    @else
                                        {{ $billableFloorArea }} billable sqm x &#8369;{{ number_format($floorAreaRate, 2) }}/sqm after {{ $includedFloorArea }} sqm included
                                    @endif
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
                                <span>{{ $addOn['label'] }} @if(($addOn['quantity'] ?? 1) > 1)<span class="text-violet-700">× {{ $addOn['quantity'] }} {{ str_replace('per ', '', $addOn['pricing_unit'] ?? '') }}</span>@endif</span>
                                <span>&#8369;{{ number_format($addOn['price'], 2) }}</span>
                            </div>
                            @endforeach
                        </div>
                        <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                            <span class="text-lg font-bold text-slate-900">Booking total</span>
                            <span class="text-lg font-bold text-emerald-600">&#8369;{{ number_format($booking->price, 2) }}</span>
                        </div>
                        <div class="rounded-xl border border-yellow-100 bg-yellow-50 p-3 text-xs text-yellow-700">
                            @if($paymentMethod === 'on_site_cash')
                            Cash payment remains pending until the service is completed, receipt proof is reviewed, and admin confirms the amount. This saved total is based on the service type, property type, floor area, and selected add-ons.
                            @else
                            This booking was recorded with {{ strtolower($paymentMethodLabel) }} and stores a digital payment reference for admin and client tracking. This saved total is based on the service type, property type, floor area, and selected add-ons.
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
const serviceLatitude = @json($booking->service_latitude);
const serviceLongitude = @json($booking->service_longitude);
const destinationCenter = serviceLatitude && serviceLongitude
    ? { lat: Number(serviceLatitude), lng: Number(serviceLongitude) }
    : (barangayCenters[bookingBarangay] || defaultMapCenter);
const destLat = destinationCenter.lat;
const destLng = destinationCenter.lng;
const travelMinutesPerKm = 5;

function estimateTravelMinutes(distanceKm) {
    const numericDistance = Number.parseFloat(distanceKm);

    if (!Number.isFinite(numericDistance) || numericDistance <= 0) {
        return 1;
    }

    return Math.max(1, Math.round(numericDistance * travelMinutesPerKm));
}

let clientMap = null;
let clientStaffMarker = null;
let clientDestMarker = null;
let clientLine = null;

let adminMap = null;
let adminStaffMarker = null;
let adminDestMarker = null;
let adminLine = null;
let clientLocationMap = null;

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

function initClientLocationMap() {
    const mapEl = document.getElementById('client-location-map');

    if (!mapEl || typeof L === 'undefined' || clientLocationMap) {
        return;
    }

    const lat = Number(mapEl.dataset.lat);
    const lng = Number(mapEl.dataset.lng);

    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
        return;
    }

    clientLocationMap = L.map(mapEl.id, {
        scrollWheelZoom: true,
        zoomControl: true,
        dragging: true
    });
    makeTileLayer().addTo(clientLocationMap);
    L.marker([lat, lng], { icon: makeDestIcon() })
        .addTo(clientLocationMap)
        .bindPopup('Client service location: ' + serviceAddress)
        .openPopup();
    clientLocationMap.setView([lat, lng], 17);
    requestAnimationFrame(() => clientLocationMap.invalidateSize());
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
            const duration = estimateTravelMinutes(distance);

            if (clientLine) clientMap.removeLayer(clientLine);
            clientLine = L.polyline(coords, {
                color: '#2563EB',
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
                    arrivalText.style.color = '#1E40AF';
                    if (arrivalSub) arrivalSub.textContent = 'Your cleaner is at your location.';
                } else if (duration <= 5) {
                    arrivalText.innerHTML = '<strong>Staff is arriving soon!</strong>';
                    arrivalText.style.color = '#2563EB';
                    if (arrivalSub) arrivalSub.textContent = `About ${duration} min away - ${distance} km`;
                } else {
                    arrivalText.innerHTML = '<strong>Staff is on the way</strong>';
                    arrivalText.style.color = '#2563EB';
                    if (arrivalSub) arrivalSub.textContent = `About ${duration} min away - ${distance} km`;
                }
            }

        } else {
            if (clientLine) clientMap.removeLayer(clientLine);
            clientLine = L.polyline([[lat, lng], [destLat, destLng]], {
                color: '#2563EB', weight: 3, dashArray: '6, 8', opacity: 0.7
            }).addTo(clientMap);
        }
    } catch (e) {
        if (clientLine) clientMap.removeLayer(clientLine);
        clientLine = L.polyline([[lat, lng], [destLat, destLng]], {
            color: '#2563EB', weight: 3, dashArray: '6, 8', opacity: 0.7
        }).addTo(clientMap);
    }

    const bounds = L.latLngBounds([[lat, lng], [destLat, destLng]]);
    clientMap.fitBounds(bounds, { padding: [40, 40] });
    requestAnimationFrame(() => clientMap.invalidateSize());
    updateClientStatus('Live - Updated ' + (updatedAt || 'just now'), '#2563EB', '#EFF6FF');
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
        statusEl.style.color = '#2563EB';
        statusEl.style.background = '#EFF6FF';
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
            const duration = estimateTravelMinutes(distance);

            if (adminLine) adminMap.removeLayer(adminLine);
            adminLine = L.layerGroup([
                L.polyline(coords, {
                    color: '#1E40AF',
                    weight: 8,
                    opacity: 0.32
                }),
                L.polyline(coords, {
                    color: '#60A5FA',
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
                    color: '#1E40AF', weight: 6, opacity: 0.28
                }),
                L.polyline([[lat, lng], [destLat, destLng]], {
                    color: '#60A5FA', weight: 3, dashArray: '6, 8', opacity: 0.85
                }),
            ]).addTo(adminMap);
        }
    } catch (e) {
        if (adminLine) adminMap.removeLayer(adminLine);
        adminLine = L.layerGroup([
            L.polyline([[lat, lng], [destLat, destLng]], {
                color: '#1E40AF', weight: 6, opacity: 0.28
            }),
            L.polyline([[lat, lng], [destLat, destLng]], {
                color: '#60A5FA', weight: 3, dashArray: '6, 8', opacity: 0.85
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
            updateClientStatus('Waiting for location...', '#60A5FA', '#EFF6FF');
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
        btn.style.color = parseInt(btn.dataset.value, 10) <= value ? '#3B82F6' : '#DBEAFE';
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

initClientLocationMap();

if (['confirmed', 'in_progress'].includes(bookingStatus)) {
    pollLocation();
    window.setInterval(pollLocation, 10000);
}
</script>
@endpush
