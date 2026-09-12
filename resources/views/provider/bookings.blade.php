@extends('layouts.provider')

@section('title', 'Assigned Bookings')
@section('page-title', 'Assigned Bookings')
@section('page-subtitle', 'Review cleaner assignments and responses')

@section('content')
@php
    $statusHeading = $status === 'all' ? 'All assigned bookings' : str_replace('_', ' ', ucfirst($status));
@endphp
<section class="min-h-screen bg-slate-50 px-4 py-6 sm:px-6 sm:py-8">
    <div class="mx-auto max-w-6xl space-y-6">
        <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white shadow-lg shadow-blue-950/10 sm:px-8">
            <div class="cleanflow-hero-content flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <span class="cleanflow-kicker">
                        <i class="fas fa-list-check"></i>
                        Cleaner Bookings
                    </span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Assigned Bookings</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-white/82">
                        Review jobs assigned to <strong>{{ $application->business_name }}</strong>, respond to pending assignments, and keep upcoming work visible.
                    </p>
                </div>
                <a href="{{ route('provider.dashboard') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/15">
                    <i class="fas fa-arrow-left"></i>
                    Dashboard
                </a>
            </div>
        </section>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach([
                'all' => ['label' => 'All', 'icon' => 'fa-layer-group', 'color' => 'text-slate-700', 'bg' => 'bg-white', 'border' => 'border-slate-300'],
                'pending_response' => ['label' => 'Needs response', 'icon' => 'fa-hourglass-half', 'color' => 'text-amber-700', 'bg' => 'bg-amber-50', 'border' => 'border-amber-400'],
                'active' => ['label' => 'Active', 'icon' => 'fa-person-running', 'color' => 'text-blue-700', 'bg' => 'bg-blue-50', 'border' => 'border-blue-400'],
                'completed' => ['label' => 'Completed', 'icon' => 'fa-circle-check', 'color' => 'text-emerald-700', 'bg' => 'bg-emerald-50', 'border' => 'border-emerald-400'],
                'cancelled' => ['label' => 'Cancelled', 'icon' => 'fa-circle-xmark', 'color' => 'text-rose-700', 'bg' => 'bg-rose-50', 'border' => 'border-rose-400'],
            ] as $key => $meta)
                <a href="{{ route('provider.bookings', ['status' => $key]) }}" class="rounded-2xl border-t-4 {{ $meta['border'] }} border-x border-b border-slate-200 {{ $meta['bg'] }} p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $status === $key ? 'ring-2 ring-blue-100' : '' }}">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-[11px] font-black uppercase tracking-wide {{ $meta['color'] }}">{{ $meta['label'] }}</div>
                        <i class="fas {{ $meta['icon'] }} {{ $meta['color'] }}"></i>
                    </div>
                    <div class="mt-3 text-3xl font-black text-slate-950">{{ number_format($counts[$key]) }}</div>
                </a>
            @endforeach
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h2 class="text-lg font-black capitalize text-slate-950">{{ $statusHeading }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $bookings->total() }} booking{{ $bookings->total() === 1 ? '' : 's' }} in this view.</p>
                </div>
                @if($counts['pending_response'] > 0 && $status !== 'pending_response')
                    <a href="{{ route('provider.bookings', ['status' => 'pending_response']) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-black text-amber-800 transition hover:bg-amber-100">
                        <i class="fas fa-hourglass-half"></i>
                        {{ number_format($counts['pending_response']) }} needs your response
                    </a>
                @endif
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($bookings as $booking)
                    @php
                        $hasClientPin = filled($booking->service_latitude) && filled($booking->service_longitude);
                        $clientAddress = $booking->street_address.', '.ucfirst($booking->barangay).', Valencia City, Bukidnon';
                        $isPendingResponse = $booking->canProviderRespondToAssignment();
                    @endphp
                    <article class="grid gap-5 border-l-4 {{ $isPendingResponse ? 'border-amber-400 bg-amber-50/30' : 'border-transparent' }} px-5 py-5 transition hover:bg-blue-50/40 sm:px-6 lg:grid-cols-[minmax(0,1fr)_auto]">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <div class="font-mono text-sm font-bold text-blue-700">CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</div>
                                @if($isPendingResponse)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-black uppercase tracking-wide text-amber-800">
                                        <i class="fas fa-bolt text-[10px]"></i>
                                        Action required
                                    </span>
                                @endif
                            </div>
                            <h2 class="mt-2 text-lg font-black text-slate-950 sm:text-xl">{{ $booking->service_label }}</h2>
                            <div class="mt-2 flex items-start gap-2 text-sm text-slate-500">
                                <i class="fas fa-location-dot mt-1 text-blue-500"></i>
                                <span>{{ $booking->street_address }}, {{ $booking->barangay }}</span>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span>
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $booking->providerAssignmentBadgeClass() }}">{{ \App\Models\Booking::providerAssignmentStatusLabel($booking->effectiveProviderAssignmentStatus()) }}</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ \App\Models\Booking::paymentStatusLabel($booking->payment?->status ?? 'pending') }}</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">{{ number_format($booking->duration_minutes ?? \App\Models\Service::DEFAULT_DURATION_MINUTES) }} min</span>
                            </div>
                            @if($application->isTeam())
                                <div class="mt-3 text-xs font-semibold {{ $booking->teamMembers->isNotEmpty() ? 'text-emerald-700' : 'text-amber-700' }}">
                                    <i class="fas fa-users mr-1"></i>
                                    @if($booking->teamMembers->isNotEmpty())
                                        Assigned cleaner{{ $booking->teamMembers->count() === 1 ? '' : 's' }}: {{ $booking->teamMembers->pluck('full_name')->join(', ') }}
                                    @else
                                        No team cleaner assigned yet
                                    @endif
                                </div>
                            @endif
                            <div class="mt-4">
                                @if($hasClientPin)
                                    <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-black text-blue-700 transition hover:bg-blue-100" data-provider-list-map-toggle="provider-list-map-panel-{{ $booking->id }}" data-provider-list-map-target="provider-list-map-{{ $booking->id }}">
                                        <i class="fas fa-route"></i>
                                        Map / Route
                                    </button>
                                    <span id="provider-list-map-{{ $booking->id }}-status" class="ml-2 text-xs font-semibold text-slate-500">Client pin saved</span>
                                @else
                                    <div class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800">
                                        <i class="fas fa-triangle-exclamation"></i>
                                        No map pin saved
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-col items-stretch gap-3 lg:items-end">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-left lg:min-w-44 lg:text-right">
                                <div class="text-[11px] font-black uppercase tracking-wide text-slate-400">Scheduled service</div>
                                <div class="mt-1 font-black text-slate-900">{{ $booking->scheduled_date->format('M d, Y') }}</div>
                                <div class="mt-1 text-sm font-semibold text-slate-500">{{ \Carbon\Carbon::parse($booking->scheduled_time)->format('h:i A') }}</div>
                            </div>
                            <div class="flex flex-col gap-2 sm:flex-row lg:justify-end">
                                @if($hasClientPin)
                                    <a href="https://www.google.com/maps/dir/?api=1&destination={{ $booking->service_latitude }},{{ $booking->service_longitude }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-bold text-blue-700 transition hover:bg-blue-100">
                                        <i class="fas fa-map-location-dot"></i>
                                        Open Maps
                                    </a>
                                @endif
                                <a href="{{ route('provider.bookings.show', $booking) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-blue-700">
                                    <i class="fas fa-eye"></i>
                                    {{ $isPendingResponse ? 'Review' : 'View' }}
                                </a>
                            </div>
                        </div>
                        @if($hasClientPin)
                            <div id="provider-list-map-panel-{{ $booking->id }}" class="hidden rounded-2xl border border-blue-100 bg-blue-50 p-4 lg:col-span-2">
                                <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div class="text-sm font-black text-slate-950">Route to client location</div>
                                        <div class="mt-1 text-xs text-slate-500">{{ $clientAddress }}</div>
                                    </div>
                                    <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-600 transition hover:bg-slate-50" data-provider-list-map-close="provider-list-map-panel-{{ $booking->id }}">
                                        <i class="fas fa-xmark"></i>
                                        Close
                                    </button>
                                </div>
                                <div
                                    id="provider-list-map-{{ $booking->id }}"
                                    class="provider-list-map"
                                    data-destination-lat="{{ $booking->service_latitude }}"
                                    data-destination-lng="{{ $booking->service_longitude }}"
                                    data-address="{{ $clientAddress }}"
                                    data-client="{{ $booking->user?->display_name ?? 'Client' }}"
                                ></div>
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="px-6 py-16 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                            <i class="fas fa-calendar-check text-2xl"></i>
                        </div>
                        <h2 class="mt-4 text-xl font-black text-slate-950">No assigned bookings</h2>
                        <p class="mt-2 text-sm text-slate-500">CleanFlow admin-assigned bookings will appear here.</p>
                    </div>
                @endforelse
            </div>

            <div class="border-t border-slate-100 px-5 py-4">
                {{ $bookings->links('pagination::tailwind') }}
            </div>
        </div>
    </div>
</section>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />
<style>
.provider-list-map {
    height: min(52vh, 420px);
    min-height: 300px;
    width: 100%;
    overflow: hidden;
    border-radius: 1rem;
    background: #eff6ff;
}
.provider-list-map-marker {
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
.provider-list-map-marker--client { background: #2563eb; }
.provider-list-map-marker--provider { background: #059669; }
</style>
@endpush

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>
const providerListMaps = {};
let providerListCurrentPosition = null;
let providerListLocationPromise = null;

function providerListMapIcon(type) {
    return L.divIcon({
        html: `<div class="provider-list-map-marker provider-list-map-marker--${type}">${type === 'provider' ? 'P' : 'C'}</div>`,
        iconSize: [28, 28],
        iconAnchor: [14, 14],
        className: ''
    });
}

function setProviderListRouteStatus(mapId, message) {
    const status = document.getElementById(mapId + '-status');
    if (status) {
        status.textContent = message;
    }
}

function initProviderListMap(mapEl) {
    if (!mapEl || providerListMaps[mapEl.id] || typeof L === 'undefined') {
        return providerListMaps[mapEl?.id];
    }

    const destLat = Number(mapEl.dataset.destinationLat);
    const destLng = Number(mapEl.dataset.destinationLng);

    if (!Number.isFinite(destLat) || !Number.isFinite(destLng)) {
        setProviderListRouteStatus(mapEl.id, 'Client pin is invalid');
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

    L.marker([destLat, destLng], { icon: providerListMapIcon('client') })
        .addTo(map)
        .bindPopup(`<strong>${mapEl.dataset.client || 'Client'}</strong><br>${mapEl.dataset.address || 'Pinned service address'}`);

    providerListMaps[mapEl.id] = { map, destLat, destLng, providerMarker: null, routeLayer: null };
    requestAnimationFrame(() => map.invalidateSize());

    return providerListMaps[mapEl.id];
}

function getProviderListCurrentPosition() {
    if (providerListCurrentPosition) {
        return Promise.resolve(providerListCurrentPosition);
    }

    if (providerListLocationPromise) {
        return providerListLocationPromise;
    }

    providerListLocationPromise = new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('Geolocation unavailable'));
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                providerListCurrentPosition = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                resolve(providerListCurrentPosition);
            },
            reject,
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
        );
    });

    return providerListLocationPromise;
}

async function drawProviderListRoute(mapId) {
    const state = providerListMaps[mapId] || initProviderListMap(document.getElementById(mapId));

    if (!state) {
        return;
    }

    setProviderListRouteStatus(mapId, 'Getting your current location...');

    try {
        const origin = await getProviderListCurrentPosition();

        if (state.providerMarker) {
            state.providerMarker.setLatLng([origin.lat, origin.lng]);
        } else {
            state.providerMarker = L.marker([origin.lat, origin.lng], { icon: providerListMapIcon('provider') })
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
            setProviderListRouteStatus(mapId, `${distanceKm} km - about ${durationMin} min`);
        } else {
            state.routeLayer = L.polyline([[origin.lat, origin.lng], [state.destLat, state.destLng]], {
                color: '#2563eb',
                weight: 3,
                dashArray: '6, 8',
                opacity: 0.75
            }).addTo(state.map);
            state.map.fitBounds([[origin.lat, origin.lng], [state.destLat, state.destLng]], { padding: [24, 24] });
            setProviderListRouteStatus(mapId, 'Route service unavailable; showing direct line');
        }

        requestAnimationFrame(() => state.map.invalidateSize());
    } catch (error) {
        state.map.setView([state.destLat, state.destLng], 16);
        setProviderListRouteStatus(mapId, 'Allow location access to draw your route');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-provider-list-map-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            const panel = document.getElementById(button.dataset.providerListMapToggle);
            const mapEl = document.getElementById(button.dataset.providerListMapTarget);

            panel?.classList.remove('hidden');
            initProviderListMap(mapEl);
            drawProviderListRoute(button.dataset.providerListMapTarget);

            requestAnimationFrame(function () {
                mapEl?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                providerListMaps[button.dataset.providerListMapTarget]?.map.invalidateSize();
            });
        });
    });

    document.querySelectorAll('[data-provider-list-map-close]').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById(button.dataset.providerListMapClose)?.classList.add('hidden');
        });
    });
});
</script>
@endpush
