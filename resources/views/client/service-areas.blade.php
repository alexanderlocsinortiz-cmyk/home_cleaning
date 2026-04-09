@extends('layouts.client')
@section('title', 'Service Areas')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .service-area-shell {
        min-height: calc(100vh - 81px);
    }

    .service-area-map {
        min-height: 340px;
    }

    .filter-btn {
        border-radius: 9999px;
        padding: 0.375rem 1rem;
        font-size: 0.75rem;
        font-weight: 600;
        line-height: 1;
        white-space: nowrap;
        transition: background-color 0.2s ease, color 0.2s ease;
    }

    .filter-btn.active {
        background: #16a34a !important;
        color: #ffffff !important;
    }

    .filter-btn:not(.active) {
        background: #f3f4f6;
        color: #4b5563;
    }

    .filter-btn:not(.active):hover {
        background: #e5e7eb;
    }

    @media (min-width: 1024px) {
        .service-area-layout {
            grid-template-columns: 400px minmax(0, 1fr);
            min-height: 580px;
        }

        .service-area-map {
            height: 100%;
            min-height: 100%;
        }
    }
</style>
@endpush

@section('content')
<section class="service-area-shell bg-gray-50 px-4 py-4 md:px-6 md:py-6">
    <div class="mx-auto max-w-7xl">
        <div class="mb-6 rounded-2xl bg-gradient-to-r from-green-600 to-teal-500 px-8 py-5 text-white shadow-sm">
            <div class="flex items-start gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/15 text-lg text-white backdrop-blur-sm">
                    <i class="fas fa-map-marked-alt"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Service Area Map</h1>
                    <p class="mt-1 text-sm text-white/80">Interactive map of all 31 barangays served in Valencia City, Bukidnon</p>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-sm">
            <div class="service-area-layout grid grid-cols-1">
                <aside class="order-2 h-full border-r border-gray-100 bg-white p-4 lg:order-1">
                    <div class="space-y-5">
                        <div class="relative">
                            <i class="fas fa-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400"></i>
                            <input
                                type="text"
                                id="barangaySearch"
                                placeholder="Search barangay..."
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 pl-10 text-sm focus:outline-none focus:ring-2 focus:ring-green-400"
                            >
                        </div>

                        <div>
                            <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">Service Type</div>
                            <div class="flex flex-wrap gap-2">
                                <button class="filter-btn active" data-filter="all">All</button>
                                <button class="filter-btn" data-filter="residential">Residential</button>
                                <button class="filter-btn" data-filter="commercial">Commercial</button>
                                <button class="filter-btn" data-filter="office">Office</button>
                            </div>
                        </div>

                        <div>
                            <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">Legend</div>
                            <div class="space-y-1">
                                <div class="flex items-center gap-2 py-1 text-sm text-gray-600">
                                    <span class="h-3 w-3 rounded-full bg-red-500"></span>
                                    <span>Service Center</span>
                                </div>
                                <div class="flex items-center gap-2 py-1 text-sm text-gray-600">
                                    <span class="h-3 w-3 rounded-full bg-emerald-600"></span>
                                    <span>Residential</span>
                                </div>
                                <div class="flex items-center gap-2 py-1 text-sm text-gray-600">
                                    <span class="h-3 w-3 rounded-full bg-orange-500"></span>
                                    <span>Commercial</span>
                                </div>
                                <div class="flex items-center gap-2 py-1 text-sm text-gray-600">
                                    <span class="h-3 w-3 rounded-full bg-green-500"></span>
                                    <span>Office</span>
                                </div>
                            </div>
                        </div>

                        <div class="min-h-0 flex-1 overflow-y-auto">
                            <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">Barangays</div>
                            <ul id="barangayList" class="space-y-1">
                                @foreach($barangays as $b)
                                <li
                                    class="flex cursor-pointer items-center justify-between rounded-lg px-2 py-1.5 transition hover:bg-green-50"
                                    data-type="{{ $b['type'] }}"
                                    data-lat="{{ $b['lat'] }}"
                                    data-lng="{{ $b['lng'] }}"
                                    data-name="{{ $b['name'] }}"
                                >
                                    <div class="flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 flex-shrink-0 rounded-full {{ $b['type'] == 'service_center' ? 'bg-red-500' : ($b['type'] == 'residential' ? 'bg-emerald-600' : ($b['type'] == 'commercial' ? 'bg-orange-500' : 'bg-green-500')) }}"></span>
                                        <span class="text-sm font-medium text-gray-700">{{ $b['name'] }}</span>
                                    </div>
                                    <span class="text-xs text-gray-400">{{ ucfirst(str_replace('_', ' ', $b['type'])) }}</span>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </aside>

                <div class="order-1 bg-white lg:order-2">
                    <div id="map" class="service-area-map z-10 w-full"></div>
                </div>
            </div>

            <div class="grid grid-cols-2 divide-x divide-gray-100 border-t border-gray-100 bg-green-50/50 px-8 py-5 md:grid-cols-4">
                <div class="flex flex-col items-center px-4 text-center">
                    <i class="fas fa-map-marker-alt mb-2 text-lg text-green-500"></i>
                    <strong class="text-2xl font-bold text-green-600">{{ $stats['barangays'] }}</strong>
                    <span class="mt-1 text-xs uppercase tracking-wide text-gray-500">Barangays</span>
                </div>
                <div class="flex flex-col items-center px-4 text-center">
                    <i class="fas fa-users mb-2 text-lg text-green-500"></i>
                    <strong class="text-2xl font-bold text-green-600" id="statCustomers">{{ $stats['customers'] }}</strong>
                    <span class="mt-1 text-xs uppercase tracking-wide text-gray-500">Customers</span>
                </div>
                <div class="flex flex-col items-center px-4 text-center">
                    <i class="fas fa-user-tie mb-2 text-lg text-green-500"></i>
                    <strong class="text-2xl font-bold text-green-600">{{ $stats['staff'] }}</strong>
                    <span class="mt-1 text-xs uppercase tracking-wide text-gray-500">Staff</span>
                </div>
                <div class="flex flex-col items-center px-4 text-center">
                    <i class="fas fa-star mb-2 text-lg text-green-500"></i>
                    <strong class="text-2xl font-bold text-green-600">{{ $stats['satisfaction'] }}%</strong>
                    <span class="mt-1 text-xs uppercase tracking-wide text-gray-500">Satisfaction</span>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    window.cleanflowMapConfig = @json(config('cleanflow.map'));
    window.barangayData = @json($barangays);
</script>
<script src="{{ asset('js/map.js') }}"></script>
@endpush
