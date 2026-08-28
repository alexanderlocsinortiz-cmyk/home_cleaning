@extends('layouts.provider')

@section('title', 'Assigned Booking')
@section('page-title', 'Assigned Booking')
@section('page-subtitle', 'Review one cleaner assignment')

@section('content')
@php
    $beforeProofs = $booking->serviceProofs->where('stage', 'before')->where('media_type', 'image')->values();
    $afterProofs = $booking->serviceProofs->where('stage', 'after')->where('media_type', 'image')->values();
    $completionVideos = $booking->serviceProofs->where('stage', 'after')->where('media_type', 'video')->values();
    $assignmentAccepted = $booking->effectiveProviderAssignmentStatus() === 'accepted';
    $canStart = $assignmentAccepted && $booking->status === 'confirmed';
    $canComplete = $assignmentAccepted && $booking->status === 'in_progress';
    $proofMaxVideoMb = (int) floor(config('cleanflow.proof_uploads.max_video_kb', 10240) / 1024);
    $hasClientPin = filled($booking->service_latitude) && filled($booking->service_longitude);
    $clientAddress = $booking->street_address.', '.ucfirst($booking->barangay).', Valencia City, Bukidnon';
    $statusLabel = ucfirst(str_replace('_', ' ', $booking->status));
    $nextActionLabel = match (true) {
        $booking->canProviderRespondToAssignment() => 'Respond to assignment',
        $canStart => 'Upload before photos and start service',
        $canComplete => 'Upload completion proof',
        $booking->status === 'completed' => 'Service completed',
        $booking->status === 'cancelled' => 'Booking cancelled',
        default => 'Waiting for admin confirmation',
    };
    $progressSteps = [
        ['label' => 'Assigned', 'active' => true, 'icon' => 'fa-clipboard-check', 'meta' => $booking->created_at?->format('M d, h:i A')],
        ['label' => 'Accepted', 'active' => $assignmentAccepted, 'icon' => 'fa-handshake', 'meta' => $booking->provider_assignment_responded_at?->format('M d, h:i A') ?? 'Pending'],
        ['label' => 'Started', 'active' => in_array($booking->status, ['in_progress', 'completed'], true), 'icon' => 'fa-broom', 'meta' => $booking->started_at?->format('M d, h:i A') ?? 'Pending'],
        ['label' => 'Completed', 'active' => $booking->status === 'completed', 'icon' => 'fa-circle-check', 'meta' => $booking->completed_at?->format('M d, h:i A') ?? 'Pending'],
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

        <section class="overflow-hidden rounded-2xl border border-blue-200 bg-gradient-to-r from-blue-50 via-white to-slate-50 shadow-sm">
            <div class="grid gap-6 p-6 lg:grid-cols-[minmax(0,1fr)_340px] lg:items-center">
                <div>
                    <a href="{{ route('provider.bookings') }}" class="inline-flex items-center gap-2 text-sm font-black text-blue-700">
                        <i class="fas fa-arrow-left"></i>
                        Assigned bookings
                    </a>
                    <div class="mt-5 font-mono text-sm font-black text-blue-700">CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</div>
                    <h1 class="mt-2 text-4xl font-black tracking-tight text-slate-950">{{ $booking->service_label }}</h1>
                    <p class="mt-2 text-sm text-slate-500">Assigned to <strong>{{ $application->business_name }}</strong></p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-black text-blue-700 ring-1 ring-blue-100">
                            <i class="fas fa-briefcase"></i>
                            {{ $statusLabel }}
                        </span>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-black {{ $booking->providerAssignmentBadgeClass() }}">
                            {{ \App\Models\Booking::providerAssignmentStatusLabel($booking->effectiveProviderAssignmentStatus()) }}
                        </span>
                    </div>
                </div>
                <div class="rounded-2xl border border-white bg-white/80 p-5 shadow-sm">
                    <div class="text-xs font-black uppercase tracking-wide text-slate-400">Next action</div>
                    <div class="mt-2 text-lg font-black text-slate-950">{{ $nextActionLabel }}</div>
                    <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-xl bg-blue-50 px-3 py-2">
                            <div class="text-lg font-black text-blue-700">{{ $beforeProofs->count() }}</div>
                            <div class="text-[11px] font-bold text-slate-500">Before</div>
                        </div>
                        <div class="rounded-xl bg-emerald-50 px-3 py-2">
                            <div class="text-lg font-black text-emerald-700">{{ $afterProofs->count() }}</div>
                            <div class="text-[11px] font-bold text-slate-500">After</div>
                        </div>
                        <div class="rounded-xl bg-purple-50 px-3 py-2">
                            <div class="text-lg font-black text-purple-700">{{ $completionVideos->count() }}</div>
                            <div class="text-[11px] font-bold text-slate-500">Videos</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1.45fr)_minmax(340px,0.8fr)]">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-xl font-black text-slate-950">Service Workflow</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Move this booking forward with proof. Customers can review uploaded proof after updates.</p>
                    </div>
                    <div class="rounded-full px-3 py-1 text-xs font-black {{ $assignmentAccepted ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ $assignmentAccepted ? 'Accepted assignment' : 'Needs response' }}
                    </div>
                </div>

                <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
                    <div class="grid divide-y divide-slate-100 bg-slate-50 sm:grid-cols-4 sm:divide-x sm:divide-y-0">
                        @foreach($progressSteps as $step)
                            <div class="p-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-xl text-sm {{ $step['active'] ? 'bg-blue-600 text-white' : 'bg-white text-slate-400 ring-1 ring-slate-200' }}">
                                        <i class="fas {{ $step['icon'] }}"></i>
                                    </span>
                                    <div>
                                        <div class="text-sm font-black text-slate-950">{{ $step['label'] }}</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $step['meta'] }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if($booking->canProviderRespondToAssignment())
                    <form action="{{ route('provider.bookings.response', $booking) }}" method="POST" class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5">
                        @csrf
                        @method('PATCH')
                        <div class="flex items-start gap-4">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white text-amber-600 ring-1 ring-amber-100">
                                <i class="fas fa-hourglass-half"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-base font-black text-slate-950">Confirm if you can handle this job</h3>
                                <label for="provider_assignment_notes" class="mt-4 block text-xs font-bold uppercase tracking-wide text-amber-800">Optional response note</label>
                                <textarea id="provider_assignment_notes" name="provider_assignment_notes" rows="3" class="mt-2 w-full rounded-xl border border-amber-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:outline-hidden" placeholder="Example: We can handle this schedule with a 3-person team.">{{ old('provider_assignment_notes') }}</textarea>
                                <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                                    <button type="submit" name="response" value="accepted" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-black text-white transition hover:bg-emerald-700">
                                        <i class="fas fa-check"></i>
                                        Accept assignment
                                    </button>
                                    <button type="submit" name="response" value="declined" class="inline-flex items-center justify-center gap-2 rounded-xl border border-red-200 bg-white px-5 py-3 text-sm font-black text-red-700 transition hover:bg-red-50">
                                        <i class="fas fa-xmark"></i>
                                        Decline assignment
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                @endif

                @if($canStart)
                    <form action="{{ route('provider.bookings.status', $booking) }}" method="POST" enctype="multipart/form-data" class="mt-6 rounded-2xl border border-blue-200 bg-blue-50 p-5">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="in_progress">
                        <div class="grid gap-5 lg:grid-cols-[1fr_auto] lg:items-end">
                            <div>
                                <div class="flex items-center gap-3">
                                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-blue-700 ring-1 ring-blue-100">
                                        <i class="fas fa-camera"></i>
                                    </span>
                                    <div>
                                        <label class="text-base font-black text-slate-950" for="before_photos">Before-service photos</label>
                                        <p class="mt-1 text-xs leading-5 text-slate-600">Required before starting. Upload 1 to 4 clear photos.</p>
                                    </div>
                                </div>
                                <input id="before_photos" type="file" name="before_photos[]" accept="image/*" multiple class="mt-4 w-full rounded-xl border border-blue-200 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-blue-700">
                            </div>
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-black text-white transition hover:bg-blue-700">
                                <i class="fas fa-play"></i>
                                Start Service
                            </button>
                        </div>
                    </form>
                @endif

                @if($canComplete)
                    <form action="{{ route('provider.bookings.status', $booking) }}" method="POST" enctype="multipart/form-data" class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="completed">
                        <div class="grid gap-4 lg:grid-cols-2">
                            <div class="rounded-xl bg-white p-4 ring-1 ring-emerald-100">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                                        <i class="fas fa-images"></i>
                                    </span>
                                    <div>
                                        <label class="text-sm font-black text-slate-950" for="after_photos">After-service photos</label>
                                        <p class="mt-1 text-xs leading-5 text-slate-600">Required to complete. Upload 1 to 4 photos.</p>
                                    </div>
                                </div>
                                <input id="after_photos" type="file" name="after_photos[]" accept="image/*" multiple class="mt-4 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-emerald-700">
                            </div>
                            <div class="rounded-xl bg-white p-4 ring-1 ring-emerald-100">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                                        <i class="fas fa-video"></i>
                                    </span>
                                    <div>
                                        <label class="text-sm font-black text-slate-950" for="completion_video">Completion video</label>
                                        <p class="mt-1 text-xs leading-5 text-slate-600">Optional video proof. Max {{ $proofMaxVideoMb }} MB.</p>
                                    </div>
                                </div>
                                <input id="completion_video" type="file" name="completion_video" accept="video/mp4,video/quicktime,video/webm,video/x-msvideo" class="mt-4 w-full rounded-xl border border-emerald-200 bg-white px-3 py-2 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-emerald-700">
                            </div>
                        </div>
                        <button type="submit" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-black text-white transition hover:bg-emerald-700 sm:w-auto">
                            <i class="fas fa-circle-check"></i>
                            Complete Service
                        </button>
                    </form>
                @endif

                @if(! $booking->canProviderRespondToAssignment() && ! $canStart && ! $canComplete)
                    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-slate-500 ring-1 ring-slate-200">
                                <i class="fas fa-circle-info"></i>
                            </span>
                            <div>
                                <h3 class="text-sm font-black text-slate-950">{{ $nextActionLabel }}</h3>
                                <p class="mt-1 text-sm leading-6 text-slate-500">No cleaner action is available at this status. CleanFlow admin still manages confirmation, payment, payout approval, and reassignment.</p>
                            </div>
                        </div>
                    </div>
                @endif
            </section>

            <aside class="space-y-5">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black text-slate-950">Job Details</h2>
                    <div class="mt-4 space-y-4 text-sm">
                        <div class="flex justify-between gap-4"><span class="text-slate-500">Date</span><span class="font-bold text-slate-900">{{ $booking->scheduled_date->format('F d, Y') }}</span></div>
                        <div class="flex justify-between gap-4"><span class="text-slate-500">Time</span><span class="font-bold text-slate-900">{{ \Carbon\Carbon::parse($booking->scheduled_time)->format('h:i A') }}</span></div>
                        <div class="flex justify-between gap-4"><span class="text-slate-500">Duration</span><span class="font-bold text-slate-900">{{ number_format($booking->duration_minutes ?? \App\Models\Service::DEFAULT_DURATION_MINUTES) }} minutes</span></div>
                        <div class="border-t border-slate-100 pt-4">
                            <div class="text-xs font-black uppercase text-slate-400">Service Location</div>
                            <div class="mt-2 font-bold text-slate-900">{{ $booking->street_address }}</div>
                            <div class="mt-1 text-slate-500">{{ $booking->barangay }}</div>
                            @if($hasClientPin)
                                <div class="mt-4 overflow-hidden rounded-2xl border border-blue-100 bg-blue-50">
                                    <div
                                        id="provider-booking-map-{{ $booking->id }}"
                                        class="provider-booking-map"
                                        data-destination-lat="{{ $booking->service_latitude }}"
                                        data-destination-lng="{{ $booking->service_longitude }}"
                                        data-address="{{ $clientAddress }}"
                                        data-client="{{ $booking->user?->display_name ?? 'Client' }}"
                                    ></div>
                                    <div class="flex flex-col gap-2 border-t border-blue-100 bg-white p-3">
                                        <div id="provider-booking-map-{{ $booking->id }}-status" class="text-xs font-semibold text-slate-500">Client pin saved</div>
                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-3 py-2 text-xs font-black text-white transition hover:bg-blue-700" data-provider-route-map="provider-booking-map-{{ $booking->id }}">
                                                <i class="fas fa-route"></i>
                                                Show route
                                            </button>
                                            <a href="https://www.google.com/maps/dir/?api=1&destination={{ $booking->service_latitude }},{{ $booking->service_longitude }}" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-black text-blue-700 transition hover:bg-blue-100">
                                                <i class="fas fa-map-location-dot"></i>
                                                Open Maps
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-3 text-xs font-semibold leading-5 text-amber-800">
                                    No client map pin was saved. Use the written address and ask admin to confirm the pinned location.
                                </div>
                            @endif
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black text-slate-950">{{ $booking->payment_method === 'on_site_cash' ? 'Cash Commission Snapshot' : 'Payout Snapshot' }}</h2>
                    <div class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4"><span class="text-slate-500">Gross</span><span class="font-bold text-slate-900">&#8369;{{ number_format((float) $booking->provider_gross_amount, 2) }}</span></div>
                        <div class="flex justify-between gap-4"><span class="text-slate-500">Commission</span><span class="font-bold text-blue-700">&#8369;{{ number_format((float) $booking->platform_commission_amount, 2) }}</span></div>
                        <div class="flex justify-between gap-4"><span class="text-slate-500">Cleaner payout</span><span class="font-bold text-emerald-700">&#8369;{{ number_format((float) $booking->provider_payout_amount, 2) }}</span></div>
                        @if($booking->payment_method === 'on_site_cash')
                            <div class="flex justify-between gap-4"><span class="text-slate-500">Cash collected</span><span class="font-bold text-slate-900">&#8369;{{ number_format((float) $booking->cash_collected_amount, 2) }}</span></div>
                            <div class="flex justify-between gap-4 border-t border-slate-100 pt-3"><span class="text-slate-500">Remit to CleanFlow</span><span class="font-bold text-orange-700">&#8369;{{ number_format((float) $booking->provider_commission_due, 2) }}</span></div>
                            <div class="flex justify-between gap-4"><span class="text-slate-500">Status</span><span class="font-bold text-slate-900">{{ \App\Models\Booking::providerCommissionStatusLabel($booking->provider_commission_status) }}</span></div>
                        @else
                            <div class="flex justify-between gap-4 border-t border-slate-100 pt-3"><span class="text-slate-500">Status</span><span class="font-bold text-slate-900">{{ \App\Models\Booking::providerPayoutStatusLabel($booking->provider_payout_status) }}</span></div>
                        @endif
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-lg font-black text-slate-950">Customer Notes</h2>
                    <p class="mt-3 text-sm leading-7 text-slate-600">{{ $booking->notes ?: 'No customer notes were provided for this booking.' }}</p>
                </section>
            </aside>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-xl font-black text-slate-950">Proof of Service</h2>
                    <p class="mt-2 text-sm text-slate-500">Uploaded files are visible to CleanFlow admin and the customer booking details.</p>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-xl bg-blue-50 px-4 py-2"><div class="text-lg font-black text-blue-700">{{ $beforeProofs->count() }}</div><div class="text-[11px] font-bold text-slate-500">Before</div></div>
                    <div class="rounded-xl bg-emerald-50 px-4 py-2"><div class="text-lg font-black text-emerald-700">{{ $afterProofs->count() }}</div><div class="text-[11px] font-bold text-slate-500">After</div></div>
                    <div class="rounded-xl bg-purple-50 px-4 py-2"><div class="text-lg font-black text-purple-700">{{ $completionVideos->count() }}</div><div class="text-[11px] font-bold text-slate-500">Videos</div></div>
                </div>
            </div>
            @if($beforeProofs->count() || $afterProofs->count() || $completionVideos->count())
                <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($beforeProofs->merge($afterProofs)->take(8) as $proof)
                        <a href="{{ \Illuminate\Support\Facades\Storage::disk(config('filesystems.public_uploads_disk'))->url($proof->file_path) }}" target="_blank" class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk(config('filesystems.public_uploads_disk'))->url($proof->file_path) }}" alt="Service proof" class="h-40 w-full object-cover transition group-hover:scale-105">
                            <div class="flex items-center justify-between px-3 py-2 text-xs font-bold text-slate-600">
                                <span>{{ ucfirst($proof->stage) }} photo</span>
                                <i class="fas fa-up-right-from-square text-slate-400"></i>
                            </div>
                        </a>
                    @endforeach
                    @foreach($completionVideos->take(2) as $proof)
                        <a href="{{ \Illuminate\Support\Facades\Storage::disk(config('filesystems.public_uploads_disk'))->url($proof->file_path) }}" target="_blank" class="flex min-h-40 flex-col items-center justify-center rounded-2xl border border-blue-200 bg-blue-50 p-4 text-center text-blue-700 transition hover:-translate-y-0.5 hover:bg-blue-100">
                            <i class="fas fa-video text-2xl"></i>
                            <span class="mt-2 text-xs font-bold">{{ $proof->original_name ?: 'Completion video' }}</span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-slate-400 ring-1 ring-slate-200">
                        <i class="fas fa-images"></i>
                    </div>
                    <p class="mt-3 text-sm font-semibold text-slate-600">No proof uploaded yet.</p>
                </div>
            @endif
        </section>
    </div>
</section>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />
<style>
.provider-booking-map {
    height: 240px;
    width: 100%;
    background: #eff6ff;
}
.provider-map-marker {
    display: flex;
    height: 28px;
    width: 28px;
    align-items: center;
    justify-content: center;
    border: 2px solid #fff;
    border-radius: 9999px;
    color: #fff;
    font-size: 0.72rem;
    font-weight: 900;
    box-shadow: 0 10px 20px rgba(15, 23, 42, 0.2);
}
.provider-map-marker--client { background: #2563eb; }
.provider-map-marker--provider { background: #059669; }
</style>
@endpush

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>
const providerBookingMaps = {};
let providerCurrentPosition = null;
let providerLocationPromise = null;

function providerMapIcon(type) {
    return L.divIcon({
        html: `<div class="provider-map-marker provider-map-marker--${type}">${type === 'provider' ? 'P' : 'C'}</div>`,
        iconSize: [28, 28],
        iconAnchor: [14, 14],
        className: ''
    });
}

function setProviderRouteStatus(mapId, message) {
    const status = document.getElementById(mapId + '-status');
    if (status) {
        status.textContent = message;
    }
}

function initProviderBookingMap(mapEl) {
    if (!mapEl || providerBookingMaps[mapEl.id] || typeof L === 'undefined') {
        return providerBookingMaps[mapEl?.id];
    }

    const destLat = Number(mapEl.dataset.destinationLat);
    const destLng = Number(mapEl.dataset.destinationLng);

    if (!Number.isFinite(destLat) || !Number.isFinite(destLng)) {
        setProviderRouteStatus(mapEl.id, 'Client pin is invalid');
        return null;
    }

    const map = L.map(mapEl.id, {
        center: [destLat, destLng],
        zoom: 16,
        scrollWheelZoom: true,
        zoomControl: true
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    L.marker([destLat, destLng], { icon: providerMapIcon('client') })
        .addTo(map)
        .bindPopup(`<strong>${mapEl.dataset.client || 'Client'}</strong><br>${mapEl.dataset.address || 'Pinned service address'}`);

    providerBookingMaps[mapEl.id] = { map, destLat, destLng, providerMarker: null, routeLayer: null };
    requestAnimationFrame(() => map.invalidateSize());

    return providerBookingMaps[mapEl.id];
}

function getProviderCurrentPosition() {
    if (providerCurrentPosition) {
        return Promise.resolve(providerCurrentPosition);
    }

    if (providerLocationPromise) {
        return providerLocationPromise;
    }

    providerLocationPromise = new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('Geolocation unavailable'));
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                providerCurrentPosition = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                resolve(providerCurrentPosition);
            },
            reject,
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
        );
    });

    return providerLocationPromise;
}

async function drawProviderRoute(mapId) {
    const state = providerBookingMaps[mapId] || initProviderBookingMap(document.getElementById(mapId));

    if (!state) {
        return;
    }

    setProviderRouteStatus(mapId, 'Getting your current location...');

    try {
        const origin = await getProviderCurrentPosition();

        if (state.providerMarker) {
            state.providerMarker.setLatLng([origin.lat, origin.lng]);
        } else {
            state.providerMarker = L.marker([origin.lat, origin.lng], { icon: providerMapIcon('provider') })
                .addTo(state.map)
                .bindPopup('Your current location');
        }

        const response = await fetch(`https://router.project-osrm.org/route/v1/driving/${origin.lng},${origin.lat};${state.destLng},${state.destLat}?overview=full&geometries=geojson`, {
            mode: 'cors',
            headers: { 'Accept': 'application/json' }
        });
        const routeData = await response.json();

        if (state.routeLayer) {
            state.map.removeLayer(state.routeLayer);
        }

        if (routeData.code === 'Ok' && routeData.routes && routeData.routes.length > 0) {
            const route = routeData.routes[0];
            const coords = route.geometry.coordinates.map((coord) => [coord[1], coord[0]]);
            const distanceKm = (route.distance / 1000).toFixed(1);
            const durationMin = Math.max(1, Math.round((route.duration || 0) / 60));

            state.routeLayer = L.polyline(coords, { color: '#2563eb', weight: 5, opacity: 0.82 }).addTo(state.map);
            state.map.fitBounds(L.latLngBounds(coords), { padding: [24, 24] });
            setProviderRouteStatus(mapId, `${distanceKm} km - about ${durationMin} min`);
        } else {
            state.routeLayer = L.polyline([[origin.lat, origin.lng], [state.destLat, state.destLng]], {
                color: '#2563eb',
                weight: 3,
                dashArray: '6, 8',
                opacity: 0.75
            }).addTo(state.map);
            state.map.fitBounds([[origin.lat, origin.lng], [state.destLat, state.destLng]], { padding: [24, 24] });
            setProviderRouteStatus(mapId, 'Route service unavailable; showing direct line');
        }

        requestAnimationFrame(() => state.map.invalidateSize());
    } catch (error) {
        state.map.setView([state.destLat, state.destLng], 16);
        setProviderRouteStatus(mapId, 'Allow location access to draw your route');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.provider-booking-map').forEach(function (mapEl) {
        initProviderBookingMap(mapEl);
    });

    document.querySelectorAll('[data-provider-route-map]').forEach(function (button) {
        button.addEventListener('click', function () {
            drawProviderRoute(button.dataset.providerRouteMap);
        });
    });
});
</script>
@endpush
