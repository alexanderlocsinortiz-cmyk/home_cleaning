@extends('layouts.staff')
@section('title', 'Service Areas')
@section('page-title', 'Service Areas')
@section('page-subtitle', 'Coverage map for Valencia City, Bukidnon')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@section('content')
<section class="bg-gray-50 min-h-screen px-4 py-6 md:px-8 md:py-8">
    <div class="mb-6 text-center md:mb-8">
        <h2 class="mb-2 text-2xl font-bold text-gray-800 md:text-3xl">
            <i class="fas fa-map-marked-alt text-emerald-500 mr-2"></i> Service Area Map
        </h2>
        <p class="text-base text-gray-600 md:text-lg">Interactive map of all 31 barangays served in Valencia City, Bukidnon</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4 lg:min-h-[550px] lg:h-[calc(100vh-280px)]">
        <div class="order-2 flex flex-col gap-4 overflow-y-auto rounded-xl bg-white p-4 shadow-md lg:order-1 lg:col-span-1">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input type="text" id="barangaySearch" placeholder="Search barangay..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-emerald-500">
            </div>

            <div>
                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Filter by Service Type</h4>
                <div class="flex flex-wrap gap-2">
                    <button class="filter-btn px-3 py-1 rounded-full border border-gray-300 bg-emerald-600 text-white text-xs font-medium transition-colors" data-filter="all">All</button>
                    <button class="filter-btn px-3 py-1 rounded-full border border-gray-300 bg-white text-gray-700 hover:bg-emerald-600 hover:text-white text-xs font-medium transition-colors" data-filter="residential">Residential</button>
                    <button class="filter-btn px-3 py-1 rounded-full border border-gray-300 bg-white text-gray-700 hover:bg-emerald-600 hover:text-white text-xs font-medium transition-colors" data-filter="commercial">Commercial</button>
                    <button class="filter-btn px-3 py-1 rounded-full border border-gray-300 bg-white text-gray-700 hover:bg-emerald-600 hover:text-white text-xs font-medium transition-colors" data-filter="office">Office</button>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Legend</h4>
                <div class="flex items-center gap-2 text-sm">
                    <span class="w-3 h-3 bg-red-500 rounded-full"></span> Service Center
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <span class="w-3 h-3 bg-emerald-600 rounded-full"></span> Residential
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <span class="w-3 h-3 bg-orange-500 rounded-full"></span> Commercial
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <span class="w-3 h-3 bg-green-500 rounded-full"></span> Office
                </div>
            </div>

            <div class="flex-1 overflow-y-auto">
                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Barangays</h4>
                <ul id="barangayList" class="space-y-1">
                    @foreach($barangays as $b)
                    <li class="flex items-center gap-2 p-2 rounded-lg cursor-pointer text-sm hover:bg-emerald-50 justify-between" data-type="{{ $b['type'] }}" data-lat="{{ $b['lat'] }}" data-lng="{{ $b['lng'] }}" data-name="{{ $b['name'] }}">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0 {{ $b['type'] == 'service_center' ? 'bg-red-500' : ($b['type'] == 'residential' ? 'bg-emerald-600' : ($b['type'] == 'commercial' ? 'bg-orange-500' : 'bg-green-500')) }}"></span>
                            {{ $b['name'] }}
                        </div>
                        <small class="text-gray-500 text-xs">{{ ucfirst(str_replace('_', ' ', $b['type'])) }}</small>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="order-1 flex flex-col gap-4 lg:order-2 lg:col-span-3">
            <div id="map" class="z-10 min-h-[320px] flex-1 rounded-xl shadow-md md:min-h-[400px]"></div>

            <div class="grid grid-cols-2 gap-4 rounded-xl bg-white p-4 shadow-md md:grid-cols-4 md:p-6">
                <div class="text-center">
                    <i class="fas fa-map-marker-alt text-emerald-500 text-xl mb-1 block"></i>
                    <strong class="text-2xl font-bold block">{{ $stats['barangays'] }}</strong>
                    <span class="text-gray-500 text-xs">Barangays</span>
                </div>
                <div class="text-center">
                    <i class="fas fa-users text-emerald-500 text-xl mb-1 block"></i>
                    <strong class="text-2xl font-bold block" id="statCustomers">{{ $stats['customers'] }}</strong>
                    <span class="text-gray-500 text-xs">Customers</span>
                </div>
                <div class="text-center">
                    <i class="fas fa-user-tie text-emerald-500 text-xl mb-1 block"></i>
                    <strong class="text-2xl font-bold block">{{ $stats['staff'] }}</strong>
                    <span class="text-gray-500 text-xs">Staff</span>
                </div>
                <div class="text-center">
                    <i class="fas fa-star text-emerald-500 text-xl mb-1 block"></i>
                    <strong class="text-2xl font-bold block">{{ $stats['satisfaction'] }}%</strong>
                    <span class="text-gray-500 text-xs">Satisfaction</span>
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

