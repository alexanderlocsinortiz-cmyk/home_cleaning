@extends(auth()->user()->role === 'admin' ? 'layouts.admin' : 'layouts.client')
@section('title', 'Booking Details - Home Cleaning Service')
@section('page-title', 'Booking Details')
@section('page-subtitle', 'Live service status and staff tracking')

@php
    $viewer = auth()->user();
    $isAdmin = $viewer->role === 'admin';
    $isClient = $viewer->role === 'client';
    $backUrl = $isAdmin ? route('admin.bookings') : route('bookings.index');
    $backLabel = $isAdmin ? 'Back to Bookings' : 'Back to My Bookings';
@endphp

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .detail-card { transition: box-shadow 0.2s ease, transform 0.2s ease; }
    .detail-card:hover { box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08) !important; }
    .timeline-step:last-child .timeline-line { display: none; }
    .rating-star { transition: color 0.15s ease, transform 0.15s ease; }
    .rating-star:hover { transform: scale(1.06); }
    #google-map-frame,
    #admin-google-map-frame {
        min-height: 340px;
        overflow: hidden;
        border: 1px solid #d1d5db !important;
        background: #e5e7eb;
    }
    #google-map-frame .leaflet-control-zoom,
    #admin-google-map-frame .leaflet-control-zoom {
        border: 0 !important;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.12);
    }
    #google-map-frame .leaflet-control-zoom a,
    #admin-google-map-frame .leaflet-control-zoom a {
        width: 36px;
        height: 36px;
        line-height: 34px;
        font-size: 22px;
        color: #0f172a;
    }
    #google-map-frame .leaflet-popup-content-wrapper,
    #admin-google-map-frame .leaflet-popup-content-wrapper {
        border-radius: 14px;
    }
    #no-location-msg > div:first-child,
    #arrival-status > div > span,
    #photo-placeholder > div:first-child {
        display: none;
    }
    label[style*="font-size: 13px; font-weight: 600; color: #374151; display: block; margin-bottom: 6px;"] {
        display: block;
        margin-bottom: 0.5rem !important;
        font-size: 0 !important;
        color: transparent !important;
    }
    label[style*="font-size: 13px; font-weight: 600; color: #374151; display: block; margin-bottom: 6px;"]::after {
        content: 'Add a Photo (optional)';
        font-size: 0.875rem;
        font-weight: 500;
        color: #334155;
    }
    @media (max-width: 1024px) {
        .booking-show-grid { grid-template-columns: 1fr !important; }
    }
    @media (max-width: 767px) {
        #google-map-frame,
        #admin-google-map-frame {
            min-height: 280px;
        }
    }
</style>
@endpush

@section('content')
@php
    $statusConfig = [
        'pending' => ['label' => 'Pending', 'badge' => 'bg-yellow-100 text-yellow-700', 'bar' => 'from-yellow-500 to-amber-400', 'icon' => 'fa-hourglass-half'],
        'confirmed' => ['label' => 'Confirmed', 'badge' => 'bg-blue-100 text-blue-700', 'bar' => 'from-blue-600 to-sky-400', 'icon' => 'fa-calendar-check'],
        'in_progress' => ['label' => 'In Progress', 'badge' => 'bg-orange-100 text-orange-700', 'bar' => 'from-orange-500 to-amber-400', 'icon' => 'fa-soap'],
        'completed' => ['label' => 'Completed', 'badge' => 'bg-green-100 text-green-700', 'bar' => 'from-green-600 to-emerald-400', 'icon' => 'fa-circle-check'],
        'cancelled' => ['label' => 'Cancelled', 'badge' => 'bg-red-100 text-red-700', 'bar' => 'from-red-500 to-rose-400', 'icon' => 'fa-ban'],
    ];
    $sc = $statusConfig[$booking->status] ?? ['label' => 'Unknown', 'badge' => 'bg-slate-100 text-slate-700', 'bar' => 'from-slate-500 to-slate-400', 'icon' => 'fa-circle-question'];
    $bookingCode = 'CF-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT);
    $scheduledDate = \Carbon\Carbon::parse($booking->scheduled_date);
    $scheduledTime = \Carbon\Carbon::parse($booking->scheduled_time);
    $homeUrl = $isAdmin ? route('admin.dashboard') : route('client.dashboard');
    $listLabel = $isAdmin ? 'Bookings' : 'My Bookings';
    $staffInitials = $booking->staff
        ? strtoupper(substr($booking->staff->first_name ?? 'S', 0, 1) . substr($booking->staff->last_name ?? 'T', 0, 1))
        : 'NA';
@endphp

<div class="min-h-[calc(100vh-81px)] bg-gray-50 px-6 py-8">
    <div class="mx-auto max-w-7xl">
        @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-700 shadow-sm">
            {{ session('success') }}
        </div>
        @endif

        @if($errors->any())
        <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700 shadow-sm">
            <div class="mb-2 font-semibold text-red-800">Please review the following:</div>
            @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
            @endforeach
        </div>
        @endif

        @if(session('error'))
        <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700 shadow-sm">
            {{ session('error') }}
        </div>
        @endif

        @if(session('warning'))
        <div class="mb-4 rounded-2xl border border-yellow-200 bg-yellow-50 px-5 py-4 text-sm text-yellow-700 shadow-sm">
            {{ session('warning') }}
        </div>
        @endif

        @if(session('info'))
        <div class="mb-4 rounded-2xl border border-blue-200 bg-blue-50 px-5 py-4 text-sm text-blue-700 shadow-sm">
            {{ session('info') }}
        </div>
        @endif

        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="mb-3 flex flex-wrap items-center gap-2 text-sm text-slate-400">
                    <a href="{{ $homeUrl }}" class="transition hover:text-emerald-600">Dashboard</a>
                    <span>&gt;</span>
                    <a href="{{ $backUrl }}" class="transition hover:text-emerald-600">{{ $listLabel }}</a>
                    <span>&gt;</span>
                    <span class="text-slate-500">Booking Details</span>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-3xl font-bold text-slate-900">Booking Details</h1>
                    <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-semibold {{ $sc['badge'] }}">
                        <i class="fa-solid {{ $sc['icon'] }}"></i>
                        {{ $sc['label'] }}
                    </span>
                </div>
                <p class="mt-2 text-sm text-slate-500">
                    Booking # <span class="font-mono font-semibold text-emerald-600">{{ $bookingCode }}</span>
                    <span class="mx-2 text-slate-300">&bull;</span>
                    Scheduled for {{ $scheduledDate->format('F d, Y') }} at {{ $scheduledTime->format('h:i A') }}
                </p>
            </div>
            <a href="{{ $backUrl }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm transition hover:border-emerald-200 hover:text-emerald-600">
                <i class="fa-solid fa-arrow-left"></i>
                {{ $backLabel }}
            </a>
        </div>

        <div class="booking-show-grid grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <div class="space-y-6">
                <div class="detail-card overflow-hidden rounded-2xl bg-white shadow-sm">
                    <div class="bg-gradient-to-r {{ $sc['bar'] }} px-6 py-4 text-white">
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

                        <div class="mt-6 grid gap-4 md:grid-cols-2">
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
                                    <i class="fa-solid fa-location-dot text-emerald-500"></i>
                                    Service Address
                                </div>
                                <div class="text-base font-semibold text-slate-900">{{ $booking->street_address }}</div>
                                <div class="mt-1 text-sm text-slate-500">{{ ucfirst($booking->barangay) }}, Valencia City</div>
                            </div>

                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                                <div class="mb-2 flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <i class="fa-regular fa-note-sticky text-emerald-500"></i>
                                    Notes
                                </div>
                                <div class="text-sm text-slate-700">{{ $booking->notes ?: 'No special instructions provided for this booking.' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="detail-card rounded-2xl bg-white p-6 shadow-sm">
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
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-fuchsia-500 text-sm font-bold text-white shadow-sm">
                            {{ $staffInitials }}
                        </div>
                        <div class="flex-1">
                            <div class="text-lg font-semibold text-slate-900">{{ $booking->staff->first_name }} {{ $booking->staff->last_name }}</div>
                            <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-500">
                                <span><i class="fa-solid fa-phone mr-1 text-violet-500"></i>{{ $booking->staff->phone ?: 'No phone provided' }}</span>
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
                                <div class="font-semibold text-yellow-800">Staff not yet assigned</div>
                                <div class="mt-1 text-sm text-yellow-700">Our admin team will assign a cleaner as soon as your booking is confirmed.</div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                @if($isAdmin && $booking->rating)
                <div class="detail-card rounded-2xl bg-white p-6 shadow-sm">
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

                @if($isClient && in_array($booking->status, ['confirmed', 'in_progress'], true) && $booking->staff)
                <div class="detail-card rounded-2xl bg-white p-6 shadow-sm">
                    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Staff Location</h2>
                            <p class="text-sm text-slate-500">Track the cleaner while the booking is active.</p>
                        </div>
                        <div id="location-status" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500">Waiting for location...</div>
                    </div>
                    @if($booking->status === 'confirmed')
                    <div id="no-location-msg" class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center text-slate-500">
                        <div style="font-size: 32px; margin-bottom: 8px;">ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã‚Â</div>
                        <div style="font-size: 13px;">Live location will appear here once your assigned staff member starts sharing it.</div>
                    </div>

                    <div id="map-container" class="hidden space-y-4">
                        <div id="arrival-status" class="hidden rounded-xl border border-green-200 bg-green-50 p-4">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span style="font-size: 22px;">ÃƒÂ°Ã…Â¸Ã…Â¡Ã¢â‚¬â€</span>
                                <div>
                                    <div id="arrival-text" style="font-size: 14px; font-weight: 700; color: #1D9E75;">Staff is on the way</div>
                                    <div id="arrival-sub" style="font-size: 12px; color: #64748b; margin-top: 2px;"></div>
                                </div>
                            </div>
                        </div>
                        <div id="google-map-frame" class="rounded-2xl"></div>
                        <div id="client-route-info" class="hidden rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-center text-sm text-emerald-700"></div>
                    </div>
                    @elseif($booking->status === 'in_progress')
                    <div id="no-location-msg" class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center text-slate-500">
                        <div style="font-size: 32px; margin-bottom: 8px;">ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã‚Â</div>
                        <div style="font-size: 13px;">Live location will appear here once your assigned staff member starts sharing it.</div>
                    </div>

                    <div id="map-container" class="hidden space-y-4">
                        <div id="arrival-status" class="hidden rounded-xl border border-green-200 bg-green-50 p-4">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span style="font-size: 22px;">ÃƒÂ°Ã…Â¸Ã…Â¡Ã¢â‚¬â€</span>
                                <div>
                                    <div id="arrival-text" style="font-size: 14px; font-weight: 700; color: #1D9E75;">Staff is on the way</div>
                                    <div id="arrival-sub" style="font-size: 12px; color: #64748b; margin-top: 2px;"></div>
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
                <div class="detail-card rounded-2xl bg-white p-6 shadow-sm">
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
                <div class="detail-card rounded-2xl bg-white p-6 shadow-sm">
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
                        <textarea name="comment" rows="4" placeholder="Write your review (optional)..." class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-emerald-300 focus:ring-2 focus:ring-emerald-200"></textarea>

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
                <div class="detail-card rounded-2xl bg-white p-6 shadow-sm">
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
                    <form action="{{ route('bookings.cancel', $booking->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?')">
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

            <div class="space-y-6">
                <div class="detail-card rounded-2xl bg-white p-5 shadow-sm">
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

                <div class="detail-card rounded-2xl bg-white p-5 shadow-sm">
                    <div class="mb-5">
                        <h2 class="text-lg font-semibold text-slate-900">Payment Summary</h2>
                        <p class="text-sm text-slate-500">Charges and payment timing for this booking.</p>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="text-slate-500">Service</span>
                            <span class="font-medium text-slate-800">{{ $booking->service_label }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="text-slate-500">Booking Number</span>
                            <span class="font-mono font-semibold text-emerald-600">{{ $bookingCode }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="text-slate-500">Schedule</span>
                            <span class="font-medium text-slate-800">{{ $scheduledDate->format('M d, Y') }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                            <span class="text-lg font-bold text-slate-900">Total Amount</span>
                            <span class="text-lg font-bold text-emerald-600">&#8369;{{ number_format($booking->price, 2) }}</span>
                        </div>
                        <div class="rounded-xl border border-yellow-100 bg-yellow-50 p-3 text-xs text-yellow-700">
                            Payment is collected on-site after the service has been completed.
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
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
        html: '<div style="background:#22c55e;width:26px;height:26px;border-radius:50%;border:3px solid white;box-shadow:0 3px 10px rgba(34,197,94,0.45);display:flex;align-items:center;justify-content:center;color:#052e16;font-size:12px;font-weight:800;">S</div>',
        iconSize: [26, 26],
        iconAnchor: [13, 13],
        className: ''
    });
}

function makeDestIcon() {
    return L.divIcon({
        html: '<div style="background:#1D9E75;width:26px;height:26px;border-radius:50%;border:3px solid white;box-shadow:0 3px 10px rgba(59,130,246,0.45);display:flex;align-items:center;justify-content:center;color:white;font-size:12px;font-weight:800;">C</div>',
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
                    arrivalText.style.color = '#16a34a';
                    if (arrivalSub) arrivalSub.textContent = 'Your cleaner is at your location.';
                } else if (duration <= 5) {
                    arrivalText.innerHTML = '<strong>Staff is arriving soon!</strong>';
                    arrivalText.style.color = '#d97706';
                    if (arrivalSub) arrivalSub.textContent = `About ${duration} min away - ${distance} km`;
                } else {
                    arrivalText.innerHTML = '<strong>Staff is on the way</strong>';
                    arrivalText.style.color = '#1D9E75';
                    if (arrivalSub) arrivalSub.textContent = `About ${duration} min away - ${distance} km`;
                }
            }

        } else {
            if (clientLine) clientMap.removeLayer(clientLine);
            clientLine = L.polyline([[lat, lng], [destLat, destLng]], {
                color: '#1D9E75', weight: 3, dashArray: '6, 8', opacity: 0.7
            }).addTo(clientMap);
        }
    } catch (e) {
        if (clientLine) clientMap.removeLayer(clientLine);
        clientLine = L.polyline([[lat, lng], [destLat, destLng]], {
            color: '#1D9E75', weight: 3, dashArray: '6, 8', opacity: 0.7
        }).addTo(clientMap);
    }

    const bounds = L.latLngBounds([[lat, lng], [destLat, destLng]]);
    clientMap.fitBounds(bounds, { padding: [40, 40] });
    requestAnimationFrame(() => clientMap.invalidateSize());
    updateClientStatus('Live - Updated ' + (updatedAt || 'just now'), '#16a34a', '#f0fdf4');
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
        statusEl.style.color = '#16a34a';
        statusEl.style.background = '#f0fdf4';
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
        btn.style.color = parseInt(btn.dataset.value, 10) <= value ? '#f59e0b' : '#e2e8f0';
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

