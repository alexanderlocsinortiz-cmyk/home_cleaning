@extends('layouts.client')
@section('title', 'Service Areas')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />
@endpush

@section('content')
@php
    $legendItems = [
        ['label' => 'Service Center', 'dot' => 'bg-red-500'],
        ['label' => 'Residential', 'dot' => 'bg-emerald-600'],
        ['label' => 'Commercial', 'dot' => 'bg-orange-500'],
        ['label' => 'Office', 'dot' => 'bg-green-500'],
    ];

    $statsCards = [
        ['icon' => 'fa-map-marker-alt', 'label' => 'Barangays', 'value' => $stats['barangays']],
        ['icon' => 'fa-users', 'label' => 'Customers', 'value' => $stats['customers'], 'id' => 'statCustomers'],
        ['icon' => 'fa-user-tie', 'label' => 'Staff', 'value' => $stats['staff']],
        ['icon' => 'fa-star', 'label' => 'Satisfaction', 'value' => $stats['satisfaction'] . '%'],
    ];
@endphp

<div class="cleanflow-page-shell min-h-[calc(100vh-81px)] px-4 py-6 sm:px-6 sm:py-8">
    <div class="mx-auto max-w-7xl space-y-6">
        <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8 lg:px-10">
            <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl space-y-4">
                    <span class="cleanflow-kicker">
                        <i class="fas fa-map-location-dot text-[0.75rem]"></i>
                        Client coverage map
                    </span>
                    <div class="space-y-3">
                        <h1 class="max-w-2xl text-3xl font-black tracking-tight sm:text-4xl">
                            Explore where CleanFlow can reach you
                        </h1>
                        <p class="max-w-2xl text-sm leading-7 text-white/80 sm:text-base">
                            Browse all supported barangays in Valencia City, filter by service type, and zoom straight
                            into your area before you book.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3 text-sm text-white/85">
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-layer-group text-xs"></i>
                            {{ $stats['barangays'] }} mapped barangays
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-magnifying-glass-location text-xs"></i>
                            Search and filter ready
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-location-crosshairs text-xs"></i>
                            Shared live map data
                        </span>
                    </div>
                </div>

                <a href="{{ route('bookings.create') }}" class="cleanflow-ghost-button self-start xl:self-auto">
                    <i class="fas fa-sparkles text-xs"></i>
                    Book a service
                </a>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[320px_minmax(0,1fr)]">
            <aside class="cleanflow-panel order-2 p-5 lg:order-1">
                <div class="space-y-6">
                    <div class="space-y-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Coverage controls</h2>
                            <p class="mt-1 text-sm text-slate-500">Search a barangay name or narrow the map by service type.</p>
                        </div>

                        <div class="relative">
                            <i class="fas fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                            <input
                                type="text"
                                id="barangaySearch"
                                placeholder="Search barangay..."
                                class="client-profile-input pl-10"
                            >
                        </div>
                    </div>

                    <div class="rounded-[1.35rem] border border-slate-100 bg-slate-50/90 p-4">
                        <div class="mb-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Service type</div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="filter-btn client-service-filter active" data-filter="all">All</button>
                            <button type="button" class="filter-btn client-service-filter" data-filter="residential">Residential</button>
                            <button type="button" class="filter-btn client-service-filter" data-filter="commercial">Commercial</button>
                            <button type="button" class="filter-btn client-service-filter" data-filter="office">Office</button>
                        </div>
                        <p class="mt-3 text-xs leading-5 text-slate-500">
                            The residential view also keeps the main service center visible for easier reference.
                        </p>
                    </div>

                    <div class="rounded-[1.35rem] border border-slate-100 bg-white p-4">
                        <div class="mb-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Legend</div>
                        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-1">
                            @foreach ($legendItems as $item)
                                <div class="flex items-center gap-3 rounded-2xl bg-slate-50 px-3 py-2.5 text-sm text-slate-600">
                                    <span class="h-3 w-3 rounded-full {{ $item['dot'] }}"></span>
                                    <span class="font-medium">{{ $item['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="min-h-0 rounded-[1.35rem] border border-slate-100 bg-white">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Barangays</div>
                            <p class="mt-1 text-sm text-slate-500">Click any location to center the map and open details.</p>
                        </div>
                        <ul id="barangayList" class="max-h-[420px] divide-y divide-slate-100 overflow-y-auto">
                            @foreach ($barangays as $b)
                                <li
                                    class="flex cursor-pointer items-center justify-between gap-3 px-4 py-3 transition hover:bg-emerald-50/60"
                                    data-type="{{ $b['type'] }}"
                                    data-lat="{{ $b['lat'] }}"
                                    data-lng="{{ $b['lng'] }}"
                                    data-name="{{ $b['name'] }}"
                                >
                                    <div class="flex items-center gap-3">
                                        <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $b['type'] == 'service_center' ? 'bg-red-500' : ($b['type'] == 'residential' ? 'bg-emerald-600' : ($b['type'] == 'commercial' ? 'bg-orange-500' : 'bg-green-500')) }}"></span>
                                        <div>
                                            <div class="text-sm font-semibold text-slate-700">{{ $b['name'] }}</div>
                                            <div class="text-xs text-slate-400">{{ implode(', ', $b['services']) }}</div>
                                        </div>
                                    </div>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">
                                        {{ ucfirst(str_replace('_', ' ', $b['type'])) }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </aside>

            <div class="order-1 space-y-5 lg:order-2">
                <section class="cleanflow-panel overflow-hidden p-4 sm:p-5">
                    <div class="mb-4 flex flex-col gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Interactive coverage map</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                Zoom in, inspect available service types, and confirm if your neighborhood is covered.
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            Shared coordinate data active
                        </span>
                    </div>

                    <div id="map" class="client-service-area-map z-10 w-full overflow-hidden rounded-3xl border border-slate-100 min-h-[360px] md:min-h-[520px] xl:min-h-[620px]"></div>
                </section>

                <div class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-4">
                    @foreach ($statsCards as $card)
                        <section class="cleanflow-panel p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $card['label'] }}</p>
                                    <strong
                                        @if (!empty($card['id'])) id="{{ $card['id'] }}" @endif
                                        class="mt-3 block text-3xl font-black tracking-tight text-slate-900"
                                    >
                                        {{ $card['value'] }}
                                    </strong>
                                </div>
                                <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                                    <i class="fas {{ $card['icon'] }}"></i>
                                </span>
                            </div>
                            <p class="mt-4 text-sm leading-6 text-slate-500">
                                {{ $card['label'] === 'Satisfaction' ? 'Based on recent completed-service feedback in covered areas.' : 'Live project data connected to the shared Valencia City service map.' }}
                            </p>
                        </section>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>
    window.cleanflowMapConfig = @json(config('cleanflow.map'));
    window.barangayData = @json($barangays);
</script>
<script src="{{ asset('js/map.js') }}"></script>
@endpush
