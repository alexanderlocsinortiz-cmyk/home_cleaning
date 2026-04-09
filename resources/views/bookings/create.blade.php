@extends('layouts.client')
@section('title', 'Book a Service - Home Cleaning Service')

@push('styles')
<style>
@media (max-width: 767px) {
    .booking-shell {
        padding: 1.5rem 1rem !important;
    }

    .booking-option-grid,
    .booking-grid-2 {
        grid-template-columns: 1fr !important;
    }

    .booking-actions {
        flex-direction: column !important;
        align-items: stretch !important;
    }

    .booking-actions > * {
        width: 100% !important;
        justify-content: center !important;
    }
}

.selection-card {
    transition: all 0.2s ease;
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    background: white;
}

.selection-card:hover {
    border-color: #4ade80 !important;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
}

.selection-card.selected-card {
    border-color: #22c55e !important;
    background: #f0fdf4 !important;
    box-shadow: 0 12px 24px rgba(34, 197, 94, 0.12);
}
</style>
@endpush

@section('content')
@php
    $serviceBasePrices = $services->mapWithKeys(function ($service) {
        return [$service->slug => (float) $service->price];
    });
@endphp

<div class="booking-shell min-h-[calc(100vh-81px)] bg-gray-50 px-6 py-8" style="font-family: 'DM Sans', sans-serif;">
    <div class="mx-auto max-w-4xl">

        <div class="mb-8 rounded-2xl bg-gradient-to-r from-green-600 to-teal-500 px-8 py-6 text-white shadow-sm">
            <h1 class="text-3xl font-bold">Book a Cleaning Service</h1>
            <p class="mt-2 text-sm text-white/85">Choose your service, property details, schedule, and address. Your final price updates automatically.</p>
        </div>

        @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-red-700 shadow-sm">
            <div class="text-sm font-semibold">Please review the booking form.</div>
            <div class="mt-1 text-sm">One or more fields need attention before the booking can be submitted.</div>
            <div class="mt-3 space-y-1 text-sm">
                @foreach ($errors->all() as $error)
                <div>&bull; {{ $error }}</div>
                @endforeach
            </div>
        </div>
        @endif

        <form action="{{ route('bookings.store') }}" method="POST" id="booking-form" class="space-y-5">
            @csrf

            <section class="rounded-2xl bg-white p-6 shadow-sm">
                <div class="mb-5 flex items-start gap-3 border-b border-gray-100 pb-4">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-green-600 text-sm font-bold text-white">1</div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Choose Property Type</h2>
                        <p class="text-sm text-slate-500">Tell us what kind of property needs cleaning.</p>
                    </div>
                </div>

                <div class="booking-option-grid grid gap-4 md:grid-cols-3">
                    <label class="block cursor-pointer">
                        <input type="radio" name="property_type" value="house" class="hidden" {{ old('property_type') == 'house' ? 'checked' : '' }}>
                        <div class="property-card selection-card {{ old('property_type') == 'house' ? 'selected-card' : '' }} p-5 text-center" data-value="house">
                            <div class="text-3xl text-green-600"><i class="fas fa-house"></i></div>
                            <div class="mt-3 text-base font-semibold text-slate-900">House</div>
                            <div class="mt-1 text-xs text-slate-500">Standard rate</div>
                        </div>
                    </label>

                    <label class="block cursor-pointer">
                        <input type="radio" name="property_type" value="apartment" class="hidden" {{ old('property_type') == 'apartment' ? 'checked' : '' }}>
                        <div class="property-card selection-card {{ old('property_type') == 'apartment' ? 'selected-card' : '' }} p-5 text-center" data-value="apartment">
                            <div class="text-3xl text-green-600"><i class="fas fa-building"></i></div>
                            <div class="mt-3 text-base font-semibold text-slate-900">Apartment</div>
                            <div class="mt-1 text-xs text-slate-500">Plus &#8369;200 adjustment</div>
                        </div>
                    </label>

                    <label class="block cursor-pointer">
                        <input type="radio" name="property_type" value="boarding_house" class="hidden" {{ old('property_type') == 'boarding_house' ? 'checked' : '' }}>
                        <div class="property-card selection-card {{ old('property_type') == 'boarding_house' ? 'selected-card' : '' }} p-5 text-center" data-value="boarding_house">
                            <div class="text-3xl text-green-600"><i class="fas fa-bed"></i></div>
                            <div class="mt-3 text-base font-semibold text-slate-900">Boarding House</div>
                            <div class="mt-1 text-xs text-slate-500">Plus &#8369;300 adjustment</div>
                        </div>
                    </label>
                </div>
                @error('property_type')
                <p class="mt-3 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </section>

            <section class="rounded-2xl bg-white p-6 shadow-sm">
                <div class="mb-5 flex items-start gap-3 border-b border-gray-100 pb-4">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-green-600 text-sm font-bold text-white">2</div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Choose Service Type</h2>
                        <p class="text-sm text-slate-500">Select the cleaning service that best matches your needs.</p>
                    </div>
                </div>

                <div class="booking-option-grid grid gap-4 md:grid-cols-3">
                    @foreach($services as $service)
                    @php
                        $icons = [
                            'basic' => 'fa-broom',
                            'deep' => 'fa-spray-can-sparkles',
                            'moveinout' => 'fa-truck-moving',
                        ];
                        $descs = [
                            'basic' => 'Routine cleaning for regularly maintained spaces.',
                            'deep' => 'Detailed cleaning for areas needing extra attention.',
                            'moveinout' => 'Full property cleaning before or after moving.',
                        ];
                    @endphp
                    <label class="block cursor-pointer">
                        <input type="radio" name="service_type" value="{{ $service->slug }}" class="hidden" {{ old('service_type') == $service->slug ? 'checked' : '' }}>
                        <div class="service-card selection-card {{ old('service_type') == $service->slug ? 'selected-card' : '' }} h-full p-5 text-center" data-value="{{ $service->slug }}">
                            <div class="text-3xl text-green-600"><i class="fas {{ $icons[$service->slug] ?? 'fa-broom' }}"></i></div>
                            <div class="mt-3 text-base font-semibold text-slate-900">{{ $service->name }}</div>
                            <div class="mt-2 text-xs leading-5 text-slate-500">{{ $descs[$service->slug] ?? $service->description }}</div>
                            <div class="mt-3 text-sm font-semibold text-green-600">Starting at &#8369;{{ number_format($service->price, 0) }}</div>
                        </div>
                    </label>
                    @endforeach
                </div>
                @error('service_type')
                <p class="mt-3 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </section>

            <section class="rounded-2xl bg-white p-6 shadow-sm">
                <div class="mb-5 flex items-start gap-3 border-b border-gray-100 pb-4">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-green-600 text-sm font-bold text-white">3</div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Property Details</h2>
                        <p class="text-sm text-slate-500">These details help us calculate a more accurate service price.</p>
                    </div>
                </div>

                <div class="booking-grid-2 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Number of Rooms</label>
                        <select name="rooms" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none transition focus:border-green-400 focus:ring-2 focus:ring-green-400">
                            @for($i = 1; $i <= 10; $i++)
                            <option value="{{ $i }}" {{ old('rooms', 1) == $i ? 'selected' : '' }}>{{ $i }} Room{{ $i > 1 ? 's' : '' }}</option>
                            @endfor
                        </select>
                        <div class="mt-2 text-xs text-slate-500">Plus &#8369;50 per extra room</div>
                        @error('rooms')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Number of Bathrooms</label>
                        <select name="bathrooms" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none transition focus:border-green-400 focus:ring-2 focus:ring-green-400">
                            @for($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}" {{ old('bathrooms', 1) == $i ? 'selected' : '' }}>{{ $i }} Bathroom{{ $i > 1 ? 's' : '' }}</option>
                            @endfor
                        </select>
                        <div class="mt-2 text-xs text-slate-500">Plus &#8369;100 per extra bathroom</div>
                        @error('bathrooms')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section class="rounded-2xl bg-white p-6 shadow-sm">
                <div class="mb-5 flex items-start gap-3 border-b border-gray-100 pb-4">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-green-600 text-sm font-bold text-white">4</div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Schedule and Address</h2>
                        <p class="text-sm text-slate-500">Choose your preferred schedule and tell us exactly where to go.</p>
                    </div>
                </div>

                <div class="booking-grid-2 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Preferred Date</label>
                        <input type="date" name="scheduled_date" value="{{ old('scheduled_date') }}" min="{{ date('Y-m-d', strtotime('+1 day')) }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none transition focus:border-green-400 focus:ring-2 focus:ring-green-400">
                        @error('scheduled_date')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Preferred Time</label>
                        <select name="scheduled_time" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none transition focus:border-green-400 focus:ring-2 focus:ring-green-400">
                            <option value="">Select time</option>
                            @foreach(['07:00', '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00'] as $time)
                            <option value="{{ $time }}" {{ old('scheduled_time') == $time ? 'selected' : '' }}>{{ date('h:i A', strtotime($time)) }}</option>
                            @endforeach
                        </select>
                        @error('scheduled_time')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="booking-grid-2 mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Barangay</label>
                        <select name="barangay" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none transition focus:border-green-400 focus:ring-2 focus:ring-green-400">
                            <option value="">Select barangay</option>
                            @foreach($barangays as $b)
                            <option value="{{ $b }}" {{ old('barangay') == $b ? 'selected' : '' }}>{{ ucfirst($b) }}</option>
                            @endforeach
                        </select>
                        @error('barangay')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Street / Purok / House Details</label>
                        <input type="text" name="street_address" value="{{ old('street_address') }}" placeholder="Example: Purok 5, House 12, near barangay hall" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none transition focus:border-green-400 focus:ring-2 focus:ring-green-400">
                        @error('street_address')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mt-4">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Special Notes (optional)</label>
                    <textarea name="notes" rows="3" placeholder="Any special instructions for our cleaning staff..." class="min-h-[100px] w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-none transition focus:border-green-400 focus:ring-2 focus:ring-green-400">{{ old('notes') }}</textarea>
                </div>
            </section>

            <section class="rounded-2xl border border-green-200 bg-gradient-to-br from-green-50 to-teal-50 p-6 shadow-sm">
                <div class="text-lg font-bold text-slate-900">Price Summary</div>
                <div class="mt-4 space-y-3 text-sm text-slate-600" id="price-breakdown">
                    <div class="flex items-center justify-between">
                        <span>Base service price</span>
                        <span id="pb-base">&#8369;0</span>
                    </div>
                    <div class="flex items-center justify-between" id="pb-property-row">
                        <span>Property type adjustment</span>
                        <span id="pb-property">&#8369;0</span>
                    </div>
                    <div class="flex items-center justify-between" id="pb-rooms-row">
                        <span>Rooms adjustment</span>
                        <span id="pb-rooms">&#8369;0</span>
                    </div>
                    <div class="flex items-center justify-between" id="pb-bathrooms-row">
                        <span>Bathrooms adjustment</span>
                        <span id="pb-bathrooms">&#8369;0</span>
                    </div>
                    <div class="flex items-center justify-between border-t border-green-200 pt-3">
                        <span class="text-base font-semibold text-slate-900">Total Price</span>
                        <span id="pb-total" class="text-2xl font-bold text-green-600">&#8369;0</span>
                    </div>
                </div>
                <div class="mt-4 rounded-xl bg-green-100/70 px-4 py-3 text-xs font-medium text-green-700">
                    Payment is collected on-site after service completion.
                </div>
            </section>

            <div class="booking-actions flex items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-green-600 px-8 py-3 font-semibold text-white transition hover:bg-green-700">
                    <i class="fas fa-circle-check"></i>
                    Confirm Booking
                </button>
                <a href="{{ route('bookings.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-gray-300 px-6 py-3 text-sm font-medium text-gray-600 transition hover:bg-gray-50">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
const basePrices = @json($serviceBasePrices);
const propertyFees = { house: 0, apartment: 200, boarding_house: 300 };
const peso = '\u20B1';

function updatePrice() {
    const serviceType = document.querySelector('input[name="service_type"]:checked')?.value;
    const propertyType = document.querySelector('input[name="property_type"]:checked')?.value;
    const rooms = parseInt(document.querySelector('select[name="rooms"]')?.value || 1, 10);
    const bathrooms = parseInt(document.querySelector('select[name="bathrooms"]')?.value || 1, 10);

    const basePrice = basePrices[serviceType] || 0;
    const propertyFee = propertyFees[propertyType] || 0;
    const roomsFee = (rooms - 1) * 50;
    const bathroomsFee = (bathrooms - 1) * 100;
    const total = basePrice + propertyFee + roomsFee + bathroomsFee;

    document.getElementById('pb-base').textContent = peso + basePrice.toLocaleString();
    document.getElementById('pb-property').textContent = propertyFee > 0 ? '+' + peso + propertyFee.toLocaleString() : peso + '0';
    document.getElementById('pb-rooms').textContent = roomsFee > 0 ? '+' + peso + roomsFee.toLocaleString() : peso + '0';
    document.getElementById('pb-bathrooms').textContent = bathroomsFee > 0 ? '+' + peso + bathroomsFee.toLocaleString() : peso + '0';
    document.getElementById('pb-total').textContent = peso + total.toLocaleString();

    document.getElementById('pb-property-row').style.display = propertyFee > 0 ? 'flex' : 'none';
    document.getElementById('pb-rooms-row').style.display = roomsFee > 0 ? 'flex' : 'none';
    document.getElementById('pb-bathrooms-row').style.display = bathroomsFee > 0 ? 'flex' : 'none';
}

function syncSelectedCards(groupName, cardSelector) {
    const selectedValue = document.querySelector(`input[name="${groupName}"]:checked`)?.value;
    document.querySelectorAll(cardSelector).forEach((card) => {
        card.classList.toggle('selected-card', card.dataset.value === selectedValue);
    });
}

document.querySelectorAll('input[name="property_type"]').forEach((input) => {
    input.addEventListener('change', function () {
        syncSelectedCards('property_type', '.property-card');
        updatePrice();
    });
});

document.querySelectorAll('input[name="service_type"]').forEach((input) => {
    input.addEventListener('change', function () {
        syncSelectedCards('service_type', '.service-card');
        updatePrice();
    });
});

document.querySelectorAll('select[name="rooms"], select[name="bathrooms"]').forEach((input) => {
    input.addEventListener('change', updatePrice);
});

if (!document.querySelector('input[name="service_type"]:checked')) {
    const firstService = document.querySelector('input[name="service_type"]');
    if (firstService) {
        firstService.checked = true;
    }
}

if (!document.querySelector('input[name="property_type"]:checked')) {
    const firstProperty = document.querySelector('input[name="property_type"]');
    if (firstProperty) {
        firstProperty.checked = true;
    }
}

syncSelectedCards('property_type', '.property-card');
syncSelectedCards('service_type', '.service-card');
updatePrice();
</script>
@endsection
