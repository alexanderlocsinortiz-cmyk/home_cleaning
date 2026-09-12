@extends('layouts.admin')
@section('title', 'Service Areas')
@section('page-title', 'Service Areas')
@section('page-subtitle', 'Provider coverage across all 22 cities and municipalities of Bukidnon')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}"/>
<style>
    .admin-service-area-page {
        padding: 1.5rem;
    }

    .admin-service-area-grid {
        align-items: flex-start;
    }

    .admin-service-area-map {
        height: 560px;
        min-height: 360px;
    }

    .admin-service-area-directory {
        min-height: 0;
    }

    .admin-service-area-list {
        max-height: 380px;
        min-height: 0;
        overflow-y: auto;
        overscroll-behavior: contain;
    }

    @media (max-width: 1279px) {
        .admin-service-area-map {
            height: 420px;
        }

        .admin-service-area-list {
            max-height: 360px;
        }
    }
</style>
@endpush

@section('content')
<div class="admin-page-content admin-service-area-page">
    <div class="mx-auto flex max-w-6xl flex-col gap-6">

        <div class="admin-service-area-grid grid grid-cols-1 items-start gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
            <div class="rounded-[28px] border border-slate-200 bg-white p-4 shadow-sm">
                <div id="map" class="admin-service-area-map rounded-xl" style="height: 560px; min-height: 360px;"></div>
            </div>

            <div class="admin-service-area-directory flex min-h-0 flex-col gap-4 rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900">Coverage Directory</h3>
                    <p class="mt-1 text-sm text-slate-500">Review all Bukidnon provider coverage areas and the Valencia barangays currently configured for customer bookings.</p>
                </div>

                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                    <input
                        type="text"
                        id="barangaySearch"
                        placeholder="Search barangay..."
                        class="w-full rounded-xl border border-slate-200 py-2.5 pl-10 pr-4 text-sm text-slate-700 outline-hidden transition focus:border-emerald-500"
                    >
                </div>

                <div>
                    <h3 class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-500">Filter By Area Type</h3>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="filter-btn rounded-full border border-slate-200 bg-emerald-600 px-3 py-1 text-xs font-semibold text-white transition-colors" data-filter="all">All</button>
                        <button type="button" class="filter-btn rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700 transition-colors hover:bg-emerald-600 hover:text-white" data-filter="residential">Residential</button>
                        <button type="button" class="filter-btn rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700 transition-colors hover:bg-emerald-600 hover:text-white" data-filter="commercial">Commercial</button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 rounded-2xl bg-slate-50 p-4">
                    <div class="rounded-lg bg-white p-3 text-center shadow-sm">
                        <div class="text-lg font-bold text-slate-800">{{ count($coverageAreas) }}</div>
                        <div class="text-xs text-slate-500">Bukidnon areas</div>
                    </div>
                    <div class="rounded-lg bg-white p-3 text-center shadow-sm">
                        <div class="text-lg font-bold text-purple-700">{{ count($providerCoveragePoints) }}</div>
                        <div class="text-xs text-slate-500">Areas with provider pins</div>
                    </div>
                </div>

                <div class="rounded-2xl border border-teal-100 bg-teal-50 p-4">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-700 text-white"><i class="fas fa-map-location-dot"></i></span>
                        <div>
                            <h3 class="text-sm font-bold text-teal-900">Bukidnon provider coverage</h3>
                            <p class="mt-1 text-xs leading-5 text-teal-800">Teal markers show the 22 city/municipality centers. Purple markers show areas with approved and activated providers. Exact provider base pins remain in the provider directory.</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-100">
                    <div class="border-b border-slate-100 px-4 py-3">
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Bukidnon coverage areas</div>
                        <p class="mt-1 text-xs text-slate-500">Click a city or municipality to center the map.</p>
                    </div>
                    <ul id="coverageAreaList" class="max-h-72 divide-y divide-slate-100 overflow-y-auto">
                        @foreach($coverageAreas as $area)
                        <li class="flex cursor-pointer items-center justify-between gap-3 px-4 py-2.5 text-sm hover:bg-teal-50" data-name="{{ $area['name'] }}">
                            <span class="font-semibold text-slate-700">{{ $area['name'] }}</span>
                            @if(collect($providerCoveragePoints)->firstWhere('name', $area['name']))
                                <span class="rounded-full bg-purple-100 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-purple-700">Provider pin</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-slate-500">No pin</span>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                </div>

                <div class="admin-service-area-list rounded-xl border border-slate-100" style="max-height: 380px; min-height: 0; overflow-y: auto; overscroll-behavior: contain;">
                    <div class="border-b border-slate-100 px-4 py-3 text-xs font-bold uppercase tracking-wider text-slate-500">Valencia customer booking barangays</div>
                    <ul id="barangayList" class="divide-y divide-slate-100">
                        @foreach($barangays as $barangay)
                        <li
                            class="flex cursor-pointer items-center justify-between gap-3 px-4 py-3 text-sm hover:bg-emerald-50"
                            data-type="{{ $barangay['type'] }}"
                            data-lat="{{ $barangay['lat'] }}"
                            data-lng="{{ $barangay['lng'] }}"
                            data-name="{{ $barangay['name'] }}"
                        >
                            <div>
                                <div class="font-semibold text-slate-800">{{ $barangay['name'] }}</div>
                            </div>
                            <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600">
                                {{ ucfirst(str_replace('_', ' ', $barangay['type'])) }}
                            </span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>
window.cleanflowMapConfig = @json(config('cleanflow.coverage_map'));
window.barangayData = @json($barangays);
window.cleanflowCoverageData = @json($coverageAreas);
window.providerCoverageData = @json($providerCoveragePoints);
</script>
<script src="{{ asset('js/map.js') }}"></script>
@endpush
