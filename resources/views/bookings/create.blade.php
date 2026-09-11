@extends('layouts.client')
@section('title', 'Book a Service - Home Cleaning Service')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />
@endpush

@section('content')
@php
    $serviceBasePrices = $services->mapWithKeys(function ($service) {
        return [$service->slug => (float) $service->price];
    });
    $serviceLabels = $services->mapWithKeys(function ($service) {
        return [$service->slug => $service->name];
    });
    $serviceScope = $services->mapWithKeys(function ($service) {
        return [$service->slug => $service->scopeSummary()];
    });
    $propertyTypeLabels = $pricingConfig['property_type_labels'];
    $propertyFees = $pricingConfig['property_fees'];
    $includedFloorArea = $pricingConfig['included_floor_area'];
    $floorAreaRates = $pricingConfig['floor_area_rates'];
    $perSquareMeterServices = $pricingConfig['per_square_meter_services'] ?? [];
    $flatRateRangeServices = $pricingConfig['flat_rate_range_services'] ?? [];
    $addOnCatalog = $pricingConfig['add_ons'];
    $servicePackages = $servicePackages ?? [];
    $paymentMethods = $paymentMethods ?? \App\Models\Booking::paymentMethods();
    $servicePlans = $servicePlans ?? \App\Models\Booking::servicePlans();
    $subscriptionFrequencies = $subscriptionFrequencies ?? \App\Models\Booking::subscriptionFrequencyLabels();
    $selectedAddOns = old('add_ons', []);
    $selectedAddOnQuantities = old('add_on_quantities', []);
    $selectedServiceType = old('service_type', request()->query('service'));
    $selectedPaymentMethod = old('payment_method', 'on_site_cash');
    $selectedServicePlan = old('service_plan', 'one_time');
    $selectedSubscriptionFrequency = old('subscription_frequency', 'weekly');
    $selectedSubscriptionOccurrences = old('subscription_occurrences', 4);
    $officeRateSlugs = ['office-basic', 'commercial', 'office-deep'];
    $officeRateServices = $services
        ->filter(fn ($service) => in_array($service->slug, $officeRateSlugs, true))
        ->sortBy(fn ($service) => array_search($service->slug, $officeRateSlugs, true))
        ->values();
    $googleMapsApiKey = config('services.google.maps_api_key');
    $bookingNow = $bookingNow ?? now(config('cleanflow.attendance_timezone', 'Asia/Manila'));
    $timeSlots = $timeSlots ?? ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00'];
    $profileAddress = $profileAddress ?? ['barangay' => auth()->user()?->barangay, 'street_address' => auth()->user()?->street];
    $selectedBarangay = old('barangay', $profileAddress['barangay'] ?? '');
    $selectedStreetAddress = old('street_address', $profileAddress['street_address'] ?? '');
@endphp

<div class="cleanflow-page-shell min-h-[calc(100vh-81px)] px-4 py-6 sm:px-6 sm:py-8">
    <div class="mx-auto max-w-7xl">

        <div class="cleanflow-hero mb-8 overflow-hidden px-6 py-7 text-white sm:px-8 lg:px-10">
            <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl">
                    <span class="cleanflow-kicker">
                        <i class="fas fa-sparkles"></i>
                        Client Booking Flow
                    </span>
                    <h1 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">Book a Cleaning Service</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-white/85 sm:text-[15px]">Build your cleaning plan in a few clear steps. Your quote updates instantly as you choose the package, property details, add-ons, schedule, and payment option.</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-3 xl:w-100">
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-4 backdrop-blur-sm">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/70">Instant Quote</div>
                        <div class="mt-2 text-sm font-semibold text-white">Live pricing</div>
                        <div class="mt-1 text-xs leading-5 text-white/75">See the total update while you complete the form.</div>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-4 backdrop-blur-sm">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/70">Flexible Request</div>
                        <div class="mt-2 text-sm font-semibold text-white">Preferred cleaner</div>
                        <div class="mt-1 text-xs leading-5 text-white/75">Request a cleaner and we'll honor it when the slot is open.</div>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-4 backdrop-blur-sm">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/70">Payment choice</div>
                        <div class="mt-2 text-sm font-semibold text-white">Cash or digital</div>
                        <div class="mt-1 text-xs leading-5 text-white/75">Choose one-time or recurring service with the payment option you prefer.</div>
                    </div>
                </div>
            </div>
        </div>

        @if ($errors->any())
        <div class="cleanflow-alert cleanflow-alert--error mb-6">
            <div class="text-sm font-semibold">Please review the booking form.</div>
            <div class="mt-1 text-sm">One or more fields need attention before the booking can be submitted.</div>
            <div class="mt-3 space-y-1 text-sm">
                @foreach ($errors->all() as $error)
                <div>&bull; {{ $error }}</div>
                @endforeach
            </div>
        </div>
        @endif

        <form action="{{ route('bookings.store') }}" method="POST" id="booking-form" class="space-y-6" onsubmit="this.querySelector('button[type=submit]').disabled=true;this.querySelector('button[type=submit]').innerHTML='<i class=\'fas fa-circle-notch fa-spin\'></i> Processing...';">
            @csrf

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Build your booking</div>
                    <p class="mt-1 text-sm text-slate-500">Choose each option in order. You can jump back to any step before submitting.</p>
                </div>
                <div class="text-xs font-semibold text-slate-500">Required unless marked optional</div>
            </div>

            <nav class="booking-progress flex gap-2 overflow-x-auto pb-1" aria-label="Booking steps">
                <a href="#booking-step-1" data-booking-progress-step="1" aria-current="step" class="booking-progress-step is-active inline-flex min-w-max items-center gap-2 rounded-full border px-3 py-2 text-xs font-semibold shadow-sm backdrop-blur">
                    <span class="booking-progress-number flex h-6 w-6 items-center justify-center rounded-full text-[11px] text-white">1</span>
                    Property
                </a>
                <a href="#booking-step-2" data-booking-progress-step="2" class="booking-progress-step inline-flex min-w-max items-center gap-2 rounded-full border px-3 py-2 text-xs font-semibold shadow-sm backdrop-blur">
                    <span class="booking-progress-number flex h-6 w-6 items-center justify-center rounded-full text-[11px] text-white">2</span>
                    Service
                </a>
                <a href="#booking-step-3" data-booking-progress-step="3" class="booking-progress-step inline-flex min-w-max items-center gap-2 rounded-full border px-3 py-2 text-xs font-semibold shadow-sm backdrop-blur">
                    <span class="booking-progress-number flex h-6 w-6 items-center justify-center rounded-full text-[11px] text-white">3</span>
                    Details
                </a>
                <a href="#booking-step-4" data-booking-progress-step="4" class="booking-progress-step inline-flex min-w-max items-center gap-2 rounded-full border px-3 py-2 text-xs font-semibold shadow-sm backdrop-blur">
                    <span class="booking-progress-number flex h-6 w-6 items-center justify-center rounded-full text-[11px] text-white">4</span>
                    Schedule
                </a>
                <a href="#booking-step-5" data-booking-progress-step="5" class="booking-progress-step inline-flex min-w-max items-center gap-2 rounded-full border px-3 py-2 text-xs font-semibold shadow-sm backdrop-blur">
                    <span class="booking-progress-number flex h-6 w-6 items-center justify-center rounded-full text-[11px] text-white">5</span>
                    Cleaner
                </a>
                <a href="#booking-step-6" data-booking-progress-step="6" class="booking-progress-step inline-flex min-w-max items-center gap-2 rounded-full border px-3 py-2 text-xs font-semibold shadow-sm backdrop-blur">
                    <span class="booking-progress-number flex h-6 w-6 items-center justify-center rounded-full text-[11px] text-white">6</span>
                    Payment
                </a>
            </div>

            <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_23rem]">
                <div class="space-y-5">
            <section id="booking-step-1" data-booking-step="1" class="cleanflow-panel scroll-mt-28 p-6 md:p-7">
                <div class="mb-6 flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white shadow-sm">1</div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Choose Property Type</h2>
                            <p class="text-sm text-slate-500">Start with the property that needs cleaning so the quote uses the right base adjustment.</p>
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Required</span>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <label class="block cursor-pointer">
                        <input type="radio" name="property_type" value="house" class="sr-only" {{ old('property_type') == 'house' ? 'checked' : '' }}>
                        <div class="property-card selection-card {{ old('property_type') == 'house' ? 'selected-card' : '' }} h-full p-5 text-center" data-value="house">
                            <div class="text-3xl text-blue-600"><i class="fas fa-house"></i></div>
                            <div class="mt-3 text-base font-semibold text-slate-900">House</div>
                            <div class="mt-1 text-xs text-slate-500">Included base rate</div>
                        </div>
                    </label>

                    <label class="block cursor-pointer">
                        <input type="radio" name="property_type" value="apartment" class="sr-only" {{ old('property_type') == 'apartment' ? 'checked' : '' }}>
                        <div class="property-card selection-card {{ old('property_type') == 'apartment' ? 'selected-card' : '' }} h-full p-5 text-center" data-value="apartment">
                            <div class="text-3xl text-blue-600"><i class="fas fa-building"></i></div>
                            <div class="mt-3 text-base font-semibold text-slate-900">Apartment</div>
                            <div class="mt-1 text-xs text-slate-500">Included base rate</div>
                        </div>
                    </label>

                    <label class="block cursor-pointer">
                        <input type="radio" name="property_type" value="boarding_house" class="sr-only" {{ old('property_type') == 'boarding_house' ? 'checked' : '' }}>
                        <div class="property-card selection-card {{ old('property_type') == 'boarding_house' ? 'selected-card' : '' }} h-full p-5 text-center" data-value="boarding_house">
                            <div class="text-3xl text-blue-600"><i class="fas fa-bed"></i></div>
                            <div class="mt-3 text-base font-semibold text-slate-900">Boarding House</div>
                            <div class="mt-1 text-xs text-slate-500">Included base rate</div>
                        </div>
                    </label>

                    <label class="block cursor-pointer">
                        <input type="radio" name="property_type" value="office" class="sr-only" {{ old('property_type') == 'office' ? 'checked' : '' }}>
                        <div class="property-card selection-card {{ old('property_type') == 'office' ? 'selected-card' : '' }} h-full p-5 text-center" data-value="office">
                            <div class="text-3xl text-blue-600"><i class="fas fa-briefcase"></i></div>
                            <div class="mt-3 text-base font-semibold text-slate-900">Office</div>
                            <div class="mt-1 text-xs text-slate-500">Office cleaning rates</div>
                        </div>
                    </label>
                </div>
                @error('property_type')
                <p class="mt-3 text-sm text-red-500">{{ $message }}</p>
                @enderror

                <div data-office-rates-panel class="mt-5 hidden overflow-hidden rounded-2xl border border-blue-100 bg-blue-50/70">
                    <div class="border-b border-blue-100 bg-white/70 px-4 py-3">
                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Office cleaning rates</div>
                        <div class="mt-1 text-sm text-slate-600">Choose an office cleaning package in the next step. Rates are billed by total floor area.</div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[420px] text-sm">
                            <thead class="bg-white/70 text-left text-xs font-bold uppercase tracking-[0.14em] text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Service</th>
                                    <th class="px-4 py-3 text-right">Rate</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-blue-100 bg-white/40 font-semibold text-slate-700">
                                @foreach($officeRateServices as $officeRateService)
                                <tr>
                                    <td class="px-4 py-3">{{ $officeRateService->name }}</td>
                                    <td class="px-4 py-3 text-right text-blue-700">&#8369;{{ number_format($officeRateService->price, 0) }}/sqm</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section id="booking-step-2" data-booking-step="2" class="cleanflow-panel scroll-mt-28 p-6 md:p-7">
                <div class="mb-6 flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white shadow-sm">2</div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Choose Service Type</h2>
                            <p class="text-sm text-slate-500">Pick the package that best matches the level of cleaning you want us to handle.</p>
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Required</span>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    @foreach($services as $service)
                    @php
                        $package = $servicePackages[$service->slug] ?? null;
                        $serviceFeatures = $package['features'] ?? [];
                        $scopeDefinition = $service->scopeDefinition();
                    @endphp
                    <label class="service-option block cursor-pointer" data-property-group="{{ in_array($service->slug, \App\Models\Service::OFFICE_SERVICE_SLUGS, true) ? 'office' : 'residential' }}">
                        <input type="radio" name="service_type" value="{{ $service->slug }}" class="sr-only" {{ $selectedServiceType == $service->slug ? 'checked' : '' }}>
                        <div class="service-card selection-card {{ $selectedServiceType == $service->slug ? 'selected-card' : '' }} h-full p-5 text-left" data-value="{{ $service->slug }}">
                            <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                                <img src="{{ $service->image_url }}" alt="{{ $service->image_alt }}" loading="lazy" decoding="async" class="h-32 w-full object-cover">
                                <div class="absolute left-3 top-3 flex h-10 w-10 items-center justify-center rounded-2xl bg-white/90 text-xl text-blue-600 shadow-sm backdrop-blur">
                                    <i class="fas {{ $package['icon'] ?? 'fa-broom' }}"></i>
                                </div>
                                @if(!empty($package['badge']))
                                <span class="absolute right-3 top-3 rounded-full bg-white/90 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-blue-700 shadow-sm backdrop-blur">
                                    {{ $package['badge'] }}
                                </span>
                                @endif
                            </div>
                            <div class="mt-4 text-base font-semibold text-slate-900">{{ $service->name }}</div>
                            <div class="mt-2 text-xs leading-5 text-slate-500">{{ $package['summary'] ?? $service->description }}</div>
                            @if(!empty($serviceFeatures))
                            <div class="mt-4 space-y-2">
                                @foreach($serviceFeatures as $feature)
                                <div class="flex items-start gap-2 text-xs leading-5 text-slate-500">
                                    <i class="fas fa-check-circle mt-0.5 text-[10px] text-green-500"></i>
                                    <span>{{ $feature }}</span>
                                </div>
                                @endforeach
                            </div>
                            @endif
                            <div class="mt-4 text-sm font-semibold text-blue-600">
                                @if(\App\Models\Service::usesPerSquareMeterPricing($service->slug))
                                    &#8369;{{ number_format($service->price, 0) }} per sqm
                                @elseif(\App\Models\Service::usesFlatRateRangePricing($service->slug) && ($range = \App\Models\Service::priceRangeForSlug($service->slug)))
                                    &#8369;{{ number_format($range['min'], 0) }} - &#8369;{{ number_format($range['max'], 0) }} flat rate
                                @else
                                    Starting at &#8369;{{ number_format($service->price, 0) }}
                                @endif
                            </div>
                            <div class="mt-3 text-xs font-semibold text-slate-500">
                                Up to {{ $service->scope_max_floor_area ? number_format($service->scope_max_floor_area) . ' sqm' : 'manual quote' }}
                                · {{ $service->scope_cleaner_count ?: 1 }} cleaner{{ ($service->scope_cleaner_count ?: 1) === 1 ? '' : 's' }}
                                · {{ $service->duration_minutes ?: \App\Models\Service::durationForSlug($service->slug) }} min base duration
                            </div>
                            <div class="mt-1 text-[11px] {{ $service->scopeIsApproved() ? 'text-emerald-700' : 'text-amber-700' }}">
                                {{ $service->scopeIsApproved() ? 'Approved measurable limit' : 'Provisional planning limit; larger requests may need review' }}
                            </div>
                            @if($service->scopeDefinitionIsComplete())
                            <details class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-left">
                                <summary class="cursor-pointer text-xs font-extrabold text-slate-700">View what is included and excluded</summary>
                                <div class="mt-3 space-y-3 text-xs leading-5 text-slate-600">
                                    <div><div class="font-bold text-slate-800">Included areas</div><div>{{ $scopeDefinition['included_areas'] }}</div></div>
                                    <div><div class="font-bold text-slate-800">Included tasks</div><div>{{ $scopeDefinition['included_tasks'] }}</div></div>
                                    <div><div class="font-bold text-slate-800">Not included</div><div>{{ $scopeDefinition['excluded_tasks'] }}</div></div>
                                    <div><div class="font-bold text-slate-800">Limits and extra work</div><div>{{ $scopeDefinition['condition_limits'] }} {{ $scopeDefinition['extra_work_policy'] }}</div></div>
                                </div>
                            </details>
                            @endif
                        </div>
                    </label>
                    @endforeach
                </div>
                <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-800" id="service-scope-disclaimer">
                    Package features are a summary, not a promise that every possible task is included. Final scope depends on property condition, access, equipment, and booked time; specialty work or extra workload may require an add-on, inspection, re-quote, or another visit.
                </div>
                @error('service_type')
                <p class="mt-3 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </section>

            <section id="booking-step-3" data-booking-step="3" class="cleanflow-panel scroll-mt-28 p-6 md:p-7">
                <div class="mb-6 flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white shadow-sm">3</div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Property Details</h2>
                            <p class="text-sm text-slate-500">These details define the basis of computation for the final quotation.</p>
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Required</span>
                </div>

                <div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Total Cleanable Floor Area (sqm)</label>
                        <input type="number" name="floor_area" value="{{ old('floor_area', $includedFloorArea) }}" min="10" max="1000" step="1" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-hidden transition focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                        <div class="mt-2 text-xs leading-5 text-slate-500">Enter the total floor area of all indoor spaces to be cleaned (e.g., bedrooms, living areas, kitchen, CR/bathrooms).</div>
                        <div class="text-xs leading-5 text-slate-500">Do not include lot area or outdoor areas unless they are part of the cleaning service.</div>
                        <div class="mt-1 text-xs font-semibold text-amber-700" id="scope-limit-note">Select a service to see its measurable scope limit.</div>
                        <div class="mt-1 text-xs font-semibold text-blue-700" id="cleaner-count-note">Select a service and floor area to estimate the required cleaners.</div>
                        @error('floor_area')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Number of Rooms</label>
                        <div class="booking-property-stepper-shell flex h-14 items-center overflow-hidden rounded-xl border border-gray-200 bg-gray-50">
                            <div class="pointer-events-none flex w-14 shrink-0 items-center justify-center border-r border-gray-200 text-lg text-slate-700" aria-hidden="true">
                                <i class="fas fa-bed"></i>
                            </div>
                            <button type="button" class="booking-property-stepper-button" data-stepper-action="decrement" data-stepper-target="rooms-input" aria-label="Decrease number of rooms"><i class="fas fa-minus" aria-hidden="true"></i></button>
                            <input id="rooms-input" type="number" name="rooms" value="{{ old('rooms', 1) }}" min="1" max="20" step="1" required inputmode="numeric" class="booking-property-stepper-input" aria-label="Number of rooms">
                            <button type="button" class="booking-property-stepper-button" data-stepper-action="increment" data-stepper-target="rooms-input" aria-label="Increase number of rooms"><i class="fas fa-plus" aria-hidden="true"></i></button>
                        </div>
                        <div class="mt-2 text-xs text-slate-500">e.g., bedrooms, guest rooms, etc.</div>
                        @error('rooms')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Number of Bathrooms / CRs</label>
                        <div class="booking-property-stepper-shell flex h-14 items-center overflow-hidden rounded-xl border border-gray-200 bg-gray-50">
                            <div class="pointer-events-none flex w-14 shrink-0 items-center justify-center border-r border-gray-200 text-lg text-slate-700" aria-hidden="true">
                                <i class="fas fa-bath"></i>
                            </div>
                            <button type="button" class="booking-property-stepper-button" data-stepper-action="decrement" data-stepper-target="bathrooms-input" aria-label="Decrease number of bathrooms"><i class="fas fa-minus" aria-hidden="true"></i></button>
                            <input id="bathrooms-input" type="number" name="bathrooms" value="{{ old('bathrooms', 1) }}" min="1" max="10" step="1" required inputmode="numeric" class="booking-property-stepper-input" aria-label="Number of bathrooms or CRs">
                            <button type="button" class="booking-property-stepper-button" data-stepper-action="increment" data-stepper-target="bathrooms-input" aria-label="Increase number of bathrooms"><i class="fas fa-plus" aria-hidden="true"></i></button>
                        </div>
                        <div class="mt-2 text-xs text-slate-500">Include all bathrooms/CRs to be cleaned.</div>
                        @error('bathrooms')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mt-5 rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm text-slate-600">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700">Pricing Basis</div>
                    <div class="mt-2 leading-6" id="floor-area-rule">Total cleanable floor area is billed per sqm based on the cleaning service you choose. Rooms and bathrooms/CRs describe the property and are not separate charges.</div>
                </div>

                <div class="mt-5">
                    <div class="mb-3">
                        <h3 class="text-sm font-semibold text-slate-900">Add-ons (optional)</h3>
                        <p class="mt-1 text-xs text-slate-500">Select extra cleaning tasks and enter a quantity when the add-on is charged per seat, mattress, unit, carpet, or cabinet/closet.</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach($addOnCatalog as $key => $addOn)
                        <label class="block cursor-pointer">
                            <input type="checkbox" name="add_ons[]" value="{{ $key }}" class="sr-only" {{ in_array($key, $selectedAddOns, true) ? 'checked' : '' }}>
                            <div class="addon-card selection-card {{ in_array($key, $selectedAddOns, true) ? 'selected-card' : '' }} h-full p-4" data-value="{{ $key }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900">{{ $addOn['label'] }}</div>
                                        <div class="mt-1 text-xs leading-5 text-slate-500">{{ $addOn['description'] }}</div>
                                    </div>
                                    <div class="text-right text-sm font-semibold text-blue-600">+&#8369;{{ number_format($addOn['price'], 0) }}<div class="text-[11px] font-medium text-slate-500">{{ $addOn['pricing_unit'] ?? \App\Models\Booking::ADD_ON_PRICING_UNIT }}</div></div>
                                </div>
                                @if(($addOn['pricing_unit'] ?? \App\Models\Booking::ADD_ON_PRICING_UNIT) !== \App\Models\Booking::ADD_ON_PRICING_UNIT)
                                <div class="mt-3 flex items-center justify-between gap-3 border-t border-slate-100 pt-3">
                                    <label for="add-on-quantity-{{ $key }}" class="text-xs font-semibold text-slate-600">Quantity</label>
                                    <input id="add-on-quantity-{{ $key }}" type="number" name="add_on_quantities[{{ $key }}]" value="{{ $selectedAddOnQuantities[$key] ?? 1 }}" min="1" max="50" step="1" data-add-on-quantity="{{ $key }}" onclick="event.stopPropagation()" class="w-20 rounded-lg border border-slate-300 px-2 py-1.5 text-center text-sm font-bold text-slate-700 focus:border-blue-500 focus:outline-hidden">
                                </div>
                                @endif
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('add_ons')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    @error('add_ons.*')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>
            </section>

            <section id="booking-step-4" data-booking-step="4" class="cleanflow-panel scroll-mt-28 p-6 md:p-7">
                <div class="mb-6 flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white shadow-sm">4</div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Schedule and Address</h2>
                            <p class="text-sm text-slate-500">Choose the preferred time and tell us exactly where the team should go.</p>
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Required</span>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Preferred Date</label>
                        <input type="date" name="scheduled_date" value="{{ old('scheduled_date') }}" min="{{ $bookingNow->toDateString() }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-hidden transition focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                        @error('scheduled_date')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Preferred Time</label>
                        <select name="scheduled_time" id="scheduled-time-select" data-selected="{{ old('scheduled_time') }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-hidden transition focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                            <option value="">Select time</option>
                            @foreach($timeSlots as $time)
                            <option value="{{ $time }}" {{ old('scheduled_time') == $time ? 'selected' : '' }}>{{ date('h:i A', strtotime($time)) }}</option>
                            @endforeach
                        </select>
                        <div id="schedule-availability-note" class="mt-2 text-xs leading-5 text-slate-500">For today, only future time slots are shown.</div>
                        @error('scheduled_time')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Barangay</label>
                        <select name="barangay" id="barangay-select" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-hidden transition focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                            <option value="">Select barangay</option>
                            @foreach($barangays as $b)
                            <option value="{{ $b }}" {{ $selectedBarangay == $b ? 'selected' : '' }}>{{ ucfirst($b) }}</option>
                            @endforeach
                        </select>
                        @error('barangay')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                            <label class="block text-sm font-semibold text-slate-700">Street / Purok / House Details</label>
                            <button type="button" id="use-current-location" class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:border-blue-300 hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-60">
                                <i class="fas fa-location-crosshairs"></i>
                                Use my current location
                            </button>
                        </div>
                        <input type="text" name="street_address" id="street-address-input" value="{{ $selectedStreetAddress }}" placeholder="Example: Purok 5, House 12, near barangay hall" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-hidden transition focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                        <input type="hidden" name="service_latitude" id="service-latitude" value="{{ old('service_latitude') }}">
                        <input type="hidden" name="service_longitude" id="service-longitude" value="{{ old('service_longitude') }}">
                        <div id="location-status-message" class="mt-2 text-xs text-slate-500">
                            Use your current location, then drag the pin or tap the map to fine-tune the service address.
                        </div>
                        @error('street_address')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                        @error('service_latitude')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                        @error('service_longitude')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div id="address-map-shell" class="mt-4 hidden overflow-hidden rounded-2xl border border-blue-100 bg-blue-50">
                    <div class="relative h-64 w-full">
                        <div id="address-map" class="absolute inset-0 z-10 h-full w-full"></div>
                        <div id="address-map-fallback" class="absolute inset-0 z-0 flex flex-col items-center justify-center gap-3 bg-blue-50 px-4 text-center text-sm text-slate-600">
                            <i class="fas fa-location-dot text-2xl text-blue-600"></i>
                            <div id="address-map-fallback-text">Location preview will appear here.</div>
                            <a id="address-map-link" href="#" target="_blank" rel="noopener" class="hidden rounded-full border border-blue-200 bg-white px-4 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-50">
                                Open in Google Maps
                            </a>
                        </div>
                    </div>
                    <div class="border-t border-blue-100 bg-white/90 px-4 py-3">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-700">Current Location Preview</div>
                                <div id="address-preview-text" class="mt-1 text-sm font-medium text-slate-900">Move the pin if needed, then confirm this address.</div>
                                <div id="barangay-preview-text" class="mt-1 text-xs text-slate-500">Barangay will update from the selected pin.</div>
                                <div id="service-center-distance" class="mt-1 text-xs text-slate-500"></div>
                            </div>
                            <button type="button" id="confirm-current-location" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60" disabled>
                                <i class="fas fa-check-circle"></i>
                                Confirm this location
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Special Notes (optional)</label>
                    <textarea name="notes" rows="3" placeholder="Any special instructions for our cleaning staff..." class="min-h-[100px] w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-hidden transition focus:border-blue-500 focus:ring-2 focus:ring-blue-200">{{ old('notes') }}</textarea>
                </div>
            </section>

            <section id="booking-step-5" data-booking-step="5" class="cleanflow-panel scroll-mt-28 p-6 md:p-7">
                <div class="mb-6 flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-sm font-bold text-white shadow-sm">5</div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Preferred Cleaner</h2>
                            <p class="text-sm text-slate-500">Add a cleaner request if you already have someone in mind. We'll honor it when the slot is still open.</p>
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full bg-blue-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-blue-700">Optional</span>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Preferred Cleaner (optional)</label>
                    <select name="preferred_staff_id" id="preferred-staff-select" data-selected="{{ old('preferred_staff_id') }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-hidden transition focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                        <option value="">No specific cleaner</option>
                        @foreach($preferredCleaners as $cleaner)
                        <option value="{{ $cleaner->id }}" {{ (string) old('preferred_staff_id') === (string) $cleaner->id ? 'selected' : '' }}>
                            {{ $cleaner->first_name }} {{ $cleaner->last_name }}{{ $cleaner->barangay ? ' - ' . ucfirst($cleaner->barangay) : '' }}
                        </option>
                        @endforeach
                    </select>
                    <div id="preferred-cleaner-note" class="mt-2 text-xs leading-5 text-slate-500">Pick a date and time to see cleaners available for that slot.</div>
                    @error('preferred_staff_id')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>
            </section>

            <section id="booking-step-6" data-booking-step="6" class="cleanflow-panel scroll-mt-28 p-6 md:p-7">
                <div class="mb-6 flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-700 text-sm font-bold text-white shadow-sm">6</div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Payment and Service Plan</h2>
                            <p class="text-sm text-slate-500">Finish the setup by choosing how you want to pay and whether the booking should repeat automatically.</p>
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full bg-blue-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-blue-700">Flexible</span>
                </div>

                <div class="space-y-6">
                    <div>
                        <div class="mb-3">
                            <h3 class="text-sm font-semibold text-slate-900">Payment Method</h3>
                            <p class="mt-1 text-xs text-slate-500">Digital payments are recorded immediately with a reference number. Cash stays pending until the service is completed and confirmed by admin.</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach($paymentMethods as $methodKey => $paymentLabel)
                            <label class="block cursor-pointer">
                                <input type="radio" name="payment_method" value="{{ $methodKey }}" class="sr-only" {{ $selectedPaymentMethod === $methodKey ? 'checked' : '' }}>
                                <div class="payment-card selection-card {{ $selectedPaymentMethod === $methodKey ? 'selected-card' : '' }} h-full p-4 text-left" data-value="{{ $methodKey }}">
                                    <div class="text-sm font-semibold text-slate-900">{{ $paymentLabel }}</div>
                                    <div class="mt-2 text-xs leading-5 text-slate-500">
                                        @if($methodKey === 'on_site_cash')
                                        Pay after the service is finished and marked completed.
                                        @else
                                        Pay digitally and store a payment reference in the booking record.
                                        @endif
                                    </div>
                                </div>
                            </label>
                            @endforeach
                        </div>
                        @error('payment_method')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <div class="mb-3">
                            <h3 class="text-sm font-semibold text-slate-900">Service Plan</h3>
                            <p class="mt-1 text-xs text-slate-500">Choose a subscription plan if you want the same service scheduled weekly, bi-weekly, or monthly.</p>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach($servicePlans as $planKey => $planLabel)
                            <label class="block cursor-pointer">
                                <input type="radio" name="service_plan" value="{{ $planKey }}" class="sr-only" {{ $selectedServicePlan === $planKey ? 'checked' : '' }}>
                                <div class="service-plan-card selection-card {{ $selectedServicePlan === $planKey ? 'selected-card' : '' }} h-full p-4 text-left" data-value="{{ $planKey }}">
                                    <div class="text-sm font-semibold text-slate-900">{{ $planLabel }}</div>
                                    <div class="mt-2 text-xs leading-5 text-slate-500">
                                        @if($planKey === 'subscription')
                                        Automatically create a recurring set of bookings for the same date and time pattern.
                                        @else
                                        Submit only one booking for the selected schedule.
                                        @endif
                                    </div>
                                </div>
                            </label>
                            @endforeach
                        </div>
                        @error('service_plan')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div id="subscription-plan-fields" class="rounded-2xl border border-blue-100 bg-blue-50 p-4 {{ $selectedServicePlan === 'subscription' ? '' : 'hidden' }}">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Recurring Frequency</label>
                                <select name="subscription_frequency" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm outline-hidden transition focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                    @foreach($subscriptionFrequencies as $frequencyKey => $frequencyLabel)
                                    <option value="{{ $frequencyKey }}" {{ $selectedSubscriptionFrequency === $frequencyKey ? 'selected' : '' }}>{{ $frequencyLabel }}</option>
                                    @endforeach
                                </select>
                                @error('subscription_frequency')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Number of Visits</label>
                                <select name="subscription_occurrences" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm outline-hidden transition focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                    @for($i = 2; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ (int) $selectedSubscriptionOccurrences === $i ? 'selected' : '' }}>{{ $i }} scheduled visits</option>
                                    @endfor
                                </select>
                                @error('subscription_occurrences')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="mt-3 text-xs text-blue-700" id="subscription-plan-note">
                            The system will create multiple bookings using the same service, schedule time, and service details.
                        </div>
                    </div>
                </div>
            </section>

                </div>

                <aside class="xl:sticky xl:top-28">
                    <section class="cleanflow-panel border border-slate-200 bg-white p-6 xl:max-h-[calc(100vh-8rem)] xl:overflow-y-auto xl:overscroll-contain">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                            <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-blue-700">Live Estimate</div>
                            <div class="mt-2 text-xl font-bold text-slate-900">Budget Summary</div>
                            <p class="mt-1 text-sm text-slate-500">Review every charge before you submit the booking.</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/80 text-lg text-blue-600 shadow-sm">
                                <i class="fas fa-receipt"></i>
                            </div>
                        </div>

                        <div class="mt-5 rounded-2xl border border-white/80 bg-white/80 p-4 shadow-sm">
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Current Selection</div>
                            <div class="mt-3 space-y-3 text-sm text-slate-600">
                                <div class="flex items-center justify-between gap-3">
                                    <span>Service</span>
                                    <span id="pb-current-service" class="text-right font-semibold text-slate-900">Choose a service</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span>Property</span>
                                    <span id="pb-current-property" class="text-right font-semibold text-slate-900">Choose a property</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span>Schedule</span>
                                    <span id="pb-current-schedule" class="text-right font-semibold text-slate-900">Pick date and time</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span>Plan</span>
                                    <span id="pb-current-plan" class="text-right font-semibold text-slate-900">One-Time Booking</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span>Payment</span>
                                    <span id="pb-current-payment" class="text-right font-semibold text-slate-900">Cash on Service Day</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 space-y-3 text-sm text-slate-600" id="price-breakdown">
                            <div id="pb-base-row" class="flex items-center justify-between">
                                <span>Base service amount</span>
                                <span id="pb-base">&#8369;0</span>
                            </div>
                            <div class="flex items-start justify-between gap-4" id="pb-property-row">
                                <div>
                                    <span>Property charge</span>
                                    <div id="pb-property-meta" class="text-xs text-slate-400">No extra charge applied.</div>
                                </div>
                                <span id="pb-property">&#8369;0</span>
                            </div>
                            <div class="flex items-start justify-between gap-4" id="pb-floor-area-row">
                                <div>
                                    <span>Floor area charge</span>
                                    <div id="pb-floor-area-meta" class="text-xs text-slate-400">No billable excess sqm yet.</div>
                                </div>
                                <span id="pb-floor-area">&#8369;0</span>
                            </div>
                            <div class="flex items-start justify-between gap-4" id="pb-add-ons-row">
                                <div>
                                    <span>Add-on charges</span>
                                    <div id="pb-add-ons-meta" class="text-xs text-slate-400">No add-ons selected.</div>
                                </div>
                                <span id="pb-add-ons">&#8369;0</span>
                            </div>
                            <div class="flex items-center justify-between border-t border-blue-200 pt-3">
                                <span class="text-base font-semibold text-slate-900">Estimated total</span>
                                <span id="pb-total" class="text-3xl font-bold tracking-tight text-blue-600">&#8369;0</span>
                            </div>
                        </div>

                        <div class="mt-4 rounded-xl bg-blue-100/70 px-4 py-3 text-xs font-medium text-blue-700" id="payment-summary-note">
                            The total is based on the service type, property type, floor area, and any selected add-ons. Cash payments stay pending until the service is completed and confirmed by admin.
                        </div>
                        <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-5 text-amber-800" id="estimate-disclaimer">
                            Estimate only: the current calculator adds no travel, tax, discount, or manual-adjustment charges. Any re-quote or scope change must be confirmed before payment.
                        </div>
                        <div class="mt-3 rounded-xl border border-blue-200 bg-white/80 px-4 py-3 text-xs text-slate-600" id="service-plan-summary-note">
                            This is currently set as a one-time booking.
                        </div>

                        <div class="mt-5 flex flex-col gap-3">
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3.5 font-semibold text-white transition hover:bg-blue-700">
                                <i class="fas fa-circle-check"></i>
                                Confirm Booking
                            </button>
                            <a href="{{ route('bookings.index') }}" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-gray-300 px-6 py-3 text-sm font-medium text-gray-600 transition hover:bg-gray-50">
                                Cancel
                            </a>
                        </div>
                    </section>
                </aside>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>
const basePrices = @json($serviceBasePrices);
const serviceLabels = @json($serviceLabels);
const serviceScope = @json($serviceScope);
const propertyFees = @json($propertyFees);
const propertyTypeLabels = @json($propertyTypeLabels);
const floorAreaRates = @json($floorAreaRates);
const includedFloorArea = @json($includedFloorArea);
const perSquareMeterServices = new Set(@json($perSquareMeterServices));
const flatRateRangeServices = @json($flatRateRangeServices);
const addOnCatalog = @json($addOnCatalog);
const paymentMethodLabels = @json($paymentMethods);
const servicePlanLabels = @json($servicePlans);
const subscriptionFrequencyLabels = @json($subscriptionFrequencies);
const validBarangays = @json(array_values($barangays));
const googleMapsEnabled = @json(!empty($googleMapsApiKey));
const serviceCenters = @json(collect(config('cleanflow.service_areas'))->where('type', 'service_center')->values()->all());
const barangayCenters = @json(config('cleanflow.barangay_centers'));
const scheduleAvailability = @json($preferredCleanerAvailability ?? []);
const peso = '\u20B1';

function formatCurrency(value) {
    return peso + Number(value).toLocaleString(undefined, {
        minimumFractionDigits: Number(value) % 1 === 0 ? 0 : 2,
        maximumFractionDigits: 2,
    });
}

function minutesFromTime(timeValue) {
    if (!timeValue || !/^\d{2}:\d{2}$/.test(timeValue)) {
        return null;
    }

    const [hours, minutes] = timeValue.split(':').map((part) => Number.parseInt(part, 10));

    return (hours * 60) + minutes;
}

function currentLocalDateValue() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function currentLocalTimeValue() {
    const now = new Date();

    return `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
}

function formatTimeLabel(timeValue) {
    const parsedTime = new Date(`1970-01-01T${timeValue}:00`);

    return Number.isNaN(parsedTime.getTime())
        ? timeValue
        : parsedTime.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
}

function selectedServiceDuration() {
    const serviceType = document.querySelector('input[name="service_type"]:checked')?.value;
    const floorArea = Number.parseInt(document.querySelector('input[name="floor_area"]')?.value || 0, 10);
    const scope = serviceScope[serviceType] || {};
    const baseDuration = Number(scheduleAvailability.serviceDurations?.[serviceType] || scope.base_duration_minutes || 120);
    const cleanerCapacity = Number(scope.capacity_sqm_per_cleaner || 0);
    const requiredCleaners = cleanerCapacity > 0 && floorArea > 0
        ? Math.ceil(floorArea / cleanerCapacity)
        : 1;

    return baseDuration * Math.max(1, requiredCleaners);
}

function slotIsFutureForSelectedDate(dateValue, timeValue) {
    if (!dateValue || !timeValue) {
        return true;
    }

    if (dateValue !== currentLocalDateValue()) {
        return true;
    }

    const slotMinutes = minutesFromTime(timeValue);
    const nowMinutes = minutesFromTime(currentLocalTimeValue());

    return slotMinutes !== null && nowMinutes !== null && slotMinutes > nowMinutes;
}

function refreshAvailableTimes() {
    const dateInput = document.querySelector('input[name="scheduled_date"]');
    const timeSelect = document.getElementById('scheduled-time-select');
    const note = document.getElementById('schedule-availability-note');

    if (!dateInput || !timeSelect) {
        return;
    }

    const selectedDate = dateInput.value;
    const currentSelection = timeSelect.value || timeSelect.dataset.selected || '';
    const timeSlots = scheduleAvailability.timeSlots || [];
    const todayValue = currentLocalDateValue();
    const availableSlots = timeSlots.filter((timeValue) => slotIsFutureForSelectedDate(selectedDate, timeValue));

    timeSelect.innerHTML = '';

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = selectedDate === todayValue && availableSlots.length === 0
        ? 'No time left today'
        : 'Select time';
    timeSelect.appendChild(placeholder);

    availableSlots.forEach((timeValue) => {
        const option = document.createElement('option');
        option.value = timeValue;
        option.textContent = formatTimeLabel(timeValue);
        option.selected = timeValue === currentSelection;
        timeSelect.appendChild(option);
    });

    if (!availableSlots.includes(currentSelection)) {
        timeSelect.value = '';
    }

    timeSelect.disabled = selectedDate === todayValue && availableSlots.length === 0;

    if (note) {
        if (!selectedDate) {
            note.textContent = 'Choose a date first. Today will only show future time slots.';
            note.className = 'mt-2 text-xs leading-5 text-slate-500';
        } else if (selectedDate === todayValue && availableSlots.length === 0) {
            note.textContent = 'No booking times are left today. Please choose another date.';
            note.className = 'mt-2 text-xs leading-5 text-amber-700';
        } else if (selectedDate === todayValue) {
            note.textContent = `Showing only times after ${formatTimeLabel(currentLocalTimeValue())} today.`;
            note.className = 'mt-2 text-xs leading-5 text-blue-700';
        } else {
            note.textContent = 'All standard booking times are available for this date unless capacity fills up.';
            note.className = 'mt-2 text-xs leading-5 text-slate-500';
        }
    }
}

function staffConflictsWithSelectedSlot(staffId, dateValue, timeValue) {
    if (!dateValue || !timeValue) {
        return false;
    }

    const selectedStart = minutesFromTime(timeValue);
    const selectedEnd = selectedStart + selectedServiceDuration() + Number(scheduleAvailability.restMinutes || 60);

    return (scheduleAvailability.assignments || []).some((assignment) => {
        if (Number(assignment.staffId) !== Number(staffId) || assignment.date !== dateValue) {
            return false;
        }

        const assignmentStart = minutesFromTime(assignment.time);
        const assignmentEnd = assignmentStart + Number(assignment.duration || 120) + Number(scheduleAvailability.restMinutes || 60);

        return selectedStart < assignmentEnd && assignmentStart < selectedEnd;
    });
}

function refreshPreferredCleaners() {
    const dateInput = document.querySelector('input[name="scheduled_date"]');
    const timeSelect = document.getElementById('scheduled-time-select');
    const cleanerSelect = document.getElementById('preferred-staff-select');
    const note = document.getElementById('preferred-cleaner-note');

    if (!dateInput || !timeSelect || !cleanerSelect) {
        return;
    }

    const selectedDate = dateInput.value;
    const selectedTime = timeSelect.value;
    const previousSelection = cleanerSelect.value || cleanerSelect.dataset.selected || '';
    const staff = scheduleAvailability.staff || [];
    const todayValue = currentLocalDateValue();

    const availableStaff = staff.filter((cleaner) => {
        if (selectedDate === todayValue && !cleaner.presentToday) {
            return false;
        }

        return !staffConflictsWithSelectedSlot(cleaner.id, selectedDate, selectedTime);
    });

    cleanerSelect.innerHTML = '';

    const emptyOption = document.createElement('option');
    emptyOption.value = '';
    emptyOption.textContent = availableStaff.length > 0 ? 'No specific cleaner' : 'No cleaner available for this slot';
    cleanerSelect.appendChild(emptyOption);

    availableStaff.forEach((cleaner) => {
        const option = document.createElement('option');
        option.value = cleaner.id;
        option.textContent = cleaner.barangay
            ? `${cleaner.name} - ${cleaner.barangay.charAt(0).toUpperCase()}${cleaner.barangay.slice(1)}`
            : cleaner.name;
        option.selected = String(cleaner.id) === String(previousSelection);
        cleanerSelect.appendChild(option);
    });

    if (!availableStaff.some((cleaner) => String(cleaner.id) === String(previousSelection))) {
        cleanerSelect.value = '';
    }

    cleanerSelect.disabled = availableStaff.length === 0;

    if (note) {
        if (!selectedDate || !selectedTime) {
            note.textContent = selectedDate === todayValue
                ? 'Select a future time to show cleaners who are punched in and free today.'
                : 'Pick a date and time to see cleaners available for that slot.';
        } else if (availableStaff.length === 0) {
            note.textContent = 'No preferred cleaner is available for the selected date and time. You can still submit without a preferred cleaner.';
        } else if (selectedDate === todayValue) {
            note.textContent = `Showing ${availableStaff.length} cleaner${availableStaff.length === 1 ? '' : 's'} punched in and free for this time today.`;
        } else {
            note.textContent = `Showing ${availableStaff.length} cleaner${availableStaff.length === 1 ? '' : 's'} without a conflict for this schedule.`;
        }
    }
}

function refreshScheduleDependentFields() {
    refreshAvailableTimes();
    refreshPreferredCleaners();
}

function calculateDistance(lat1, lng1, lat2, lng2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLng / 2) * Math.sin(dLng / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

function findNearestServiceCenter(lat, lng) {
    if (!serviceCenters || serviceCenters.length === 0) {
        return null;
    }

    let nearest = null;
    let minDistance = Infinity;

    serviceCenters.forEach(center => {
        const distance = calculateDistance(lat, lng, center.lat, center.lng);
        if (distance < minDistance) {
            minDistance = distance;
            nearest = { ...center, distance };
        }
    });

    return nearest;
}

function findNearestBarangay(lat, lng) {
    const centers = Object.entries(barangayCenters || {});

    if (centers.length === 0) {
        return '';
    }

    let nearestName = '';
    let minDistance = Infinity;

    centers.forEach(([name, center]) => {
        if (typeof center?.lat === 'undefined' || typeof center?.lng === 'undefined') {
            return;
        }

        const distance = calculateDistance(lat, lng, Number(center.lat), Number(center.lng));

        if (distance < minDistance) {
            minDistance = distance;
            nearestName = name;
        }
    });

    return nearestName;
}

function getSelectedAddOns() {
    return Array.from(document.querySelectorAll('input[name="add_ons[]"]:checked')).map((input) => input.value);
}

function getSelectedAddOnQuantities() {
    return Array.from(document.querySelectorAll('input[data-add-on-quantity]')).reduce((quantities, input) => {
        const quantity = Math.max(1, Math.min(50, parseInt(input.value || '1', 10) || 1));
        quantities[input.dataset.addOnQuantity] = quantity;

        return quantities;
    }, {});
}

function formatSchedule(dateValue, timeValue) {
    if (!dateValue && !timeValue) {
        return 'Pick date and time';
    }

    const parts = [];

    if (dateValue) {
        const parsedDate = new Date(`${dateValue}T00:00:00`);
        parts.push(Number.isNaN(parsedDate.getTime())
            ? dateValue
            : parsedDate.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }));
    }

    if (timeValue) {
        const parsedTime = new Date(`1970-01-01T${timeValue}:00`);
        parts.push(Number.isNaN(parsedTime.getTime())
            ? timeValue
            : parsedTime.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' }));
    }

    return parts.join(' | ');

    return parts.join(' · ');
}

let addressMap = null;
let addressMarker = null;
let pendingLocation = null;

function normalizeBarangayName(value) {
    return String(value || '')
        .toLowerCase()
        .replace(/^barangay\s+/i, '')
        .replace(/\s+/g, ' ')
        .trim();
}

function setLocationStatus(message, state = 'neutral') {
    const status = document.getElementById('location-status-message');
    if (!status) return;

    const classes = {
        neutral: 'mt-2 text-xs text-slate-500',
        success: 'mt-2 text-xs font-medium text-blue-700',
        warning: 'mt-2 text-xs font-medium text-amber-700',
        error: 'mt-2 text-xs font-medium text-red-600',
    };

    status.className = classes[state] || classes.neutral;
    status.textContent = message;
}

function findMatchingBarangay(results) {
    const normalizedValidBarangays = validBarangays.map((barangay) => ({
        original: barangay,
        normalized: normalizeBarangayName(barangay),
    }));

    for (const result of results || []) {
        for (const component of result.address_components || []) {
            const candidates = [component.long_name, component.short_name].map(normalizeBarangayName);
            const match = normalizedValidBarangays.find((barangay) => candidates.includes(barangay.normalized));

            if (match) {
                return match.original;
            }
        }
    }

    const combinedAddress = (results || []).map((result) => result.formatted_address || '').join(' ');
    return normalizedValidBarangays.find((barangay) => normalizeBarangayName(combinedAddress).includes(barangay.normalized))?.original || '';
}

function findMatchingBarangayFromText(value) {
    const normalizedValue = normalizeBarangayName(value);

    if (!normalizedValue) {
        return '';
    }

    return validBarangays.find((barangay) => normalizedValue.includes(normalizeBarangayName(barangay))) || '';
}

function detectBarangayFromLocation(lat, lng, results = []) {
    if (Array.isArray(results) && results.length) {
        const googleBarangay = findMatchingBarangay(results);

        if (googleBarangay) {
            return googleBarangay;
        }
    }

    if (typeof results === 'string') {
        const textBarangay = findMatchingBarangayFromText(results);

        if (textBarangay) {
            return textBarangay;
        }
    }

    return findNearestBarangay(lat, lng);
}

function setBarangaySelect(value) {
    const barangaySelect = document.getElementById('barangay-select');

    if (!barangaySelect || !value) {
        return false;
    }

    const option = Array.from(barangaySelect.options).find((item) => normalizeBarangayName(item.value) === normalizeBarangayName(value));

    if (!option) {
        return false;
    }

    barangaySelect.value = option.value;
    barangaySelect.dispatchEvent(new Event('change', { bubbles: true }));

    return true;
}

function isBarangayOnlyAddress(value) {
    const normalizedValue = normalizeBarangayName(value);

    return validBarangays.some((barangay) => normalizeBarangayName(barangay) === normalizedValue);
}

function streetAddressFromGoogle(results = []) {
    for (const result of results || []) {
        const components = result.address_components || [];
        const componentByType = (type) => components.find((component) => (component.types || []).includes(type));
        const route = componentByType('route')?.long_name || '';
        const streetNumber = componentByType('street_number')?.long_name || '';
        const premise = componentByType('premise')?.long_name || componentByType('establishment')?.long_name || '';
        const pointOfInterest = componentByType('point_of_interest')?.long_name || '';

        if (route) {
            return [streetNumber, route].filter(Boolean).join(' ');
        }

        const specificPlace = premise || pointOfInterest;

        if (specificPlace && !isBarangayOnlyAddress(specificPlace)) {
            return specificPlace;
        }
    }

    return '';
}

function setAddressMap(lat, lng, options = {}) {
    const shell = document.getElementById('address-map-shell');
    const mapEl = document.getElementById('address-map');
    const fallback = document.getElementById('address-map-fallback');
    const mapLink = document.getElementById('address-map-link');
    const fallbackText = document.getElementById('address-map-fallback-text');
    const position = { lat, lng };
    const googleMapsUrl = `https://www.google.com/maps?q=${lat},${lng}`;
    const preserveViewport = Boolean(options.preserveViewport);

    if (shell) {
        shell.classList.remove('hidden');
    }

    if (mapLink) {
        mapLink.href = googleMapsUrl;
        mapLink.classList.remove('hidden');
    }

    if (fallbackText) {
        fallbackText.textContent = `Location pin captured at ${lat.toFixed(6)}, ${lng.toFixed(6)}.`;
    }

    if (!mapEl) {
        return;
    }

    if (googleMapsEnabled && window.google && window.google.maps) {
        if (!addressMap) {
            addressMap = new google.maps.Map(mapEl, {
                center: position,
                zoom: 17,
                disableDefaultUI: false,
                draggable: true,
                scrollwheel: true,
                disableDoubleClickZoom: false,
                keyboardShortcuts: true,
                clickableIcons: true,
                gestureHandling: 'greedy',
                mapTypeId: 'roadmap',
            });

            addressMap.addListener('click', (event) => {
                if (event.latLng) {
                    handleAddressPinMoved(event.latLng.lat(), event.latLng.lng());
                }
            });
        }

        if (addressMap && !preserveViewport) {
            addressMap.setCenter(position);
            addressMap.setZoom(17);
        }

        if (!addressMarker) {
            addressMarker = new google.maps.Marker({
                position: position,
                map: addressMap,
                title: 'Service location pin',
                draggable: true,
            });

            addressMarker.addListener('dragend', (event) => {
                if (event.latLng) {
                    handleAddressPinMoved(event.latLng.lat(), event.latLng.lng());
                }
            });
        } else if (addressMarker) {
            addressMarker.setPosition(position);
        }
    } else if (window.L) {
        if (!addressMap) {
            addressMap = L.map(mapEl, {
                zoomControl: true,
                dragging: true,
                touchZoom: true,
                doubleClickZoom: true,
                scrollWheelZoom: true,
                boxZoom: true,
                keyboard: true,
                tap: true,
            }).setView([lat, lng], 17);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19,
            }).addTo(addressMap);

            addressMap.on('click', (event) => {
                handleAddressPinMoved(event.latlng.lat, event.latlng.lng);
            });
        }

        if (addressMap && !preserveViewport) {
            addressMap.setView([lat, lng], 17);
        }

        if (!addressMarker) {
            addressMarker = L.marker([lat, lng], { draggable: true }).addTo(addressMap).bindPopup('Service location pin');
            addressMarker.on('dragend', (event) => {
                const markerPosition = event.target.getLatLng();
                handleAddressPinMoved(markerPosition.lat, markerPosition.lng);
            });
        } else if (addressMarker) {
            addressMarker.setLatLng([lat, lng]);
        }
    }

    window.setTimeout(() => {
        if (!addressMap) {
            return;
        }

        if (googleMapsEnabled && window.google && window.google.maps) {
            google.maps.event.trigger(addressMap, 'resize');
            if (!preserveViewport) {
                addressMap.setCenter(position);
            }
        } else if (window.L) {
            addressMap.invalidateSize();
        }

        if (fallback) {
            fallback.classList.add('opacity-0', 'pointer-events-none');
        }
    }, 250);
}

async function handleAddressPinMoved(lat, lng) {
    setAddressMap(lat, lng, { preserveViewport: true });
    setLocationStatus('Pin moved. Looking up the nearest street address...', 'neutral');

    if (googleMapsEnabled && window.google?.maps?.Geocoder) {
        const geocoder = new google.maps.Geocoder();

        geocoder.geocode({ location: { lat, lng } }, (results, status) => {
            if (status === 'OK' && results?.length) {
                showLocationPreview(lat, lng, results, { preserveViewport: true });
                setLocationStatus('Pin moved. Confirm this location to use the updated address.', 'success');
                return;
            }

            reverseGeocodeWithOpenStreetMap(lat, lng)
                .then((address) => {
                    showLocationPreview(lat, lng, address || [], { preserveViewport: true });
                    setLocationStatus(
                        address
                            ? 'Pin moved. Confirm this location to use the updated street details.'
                            : 'Pin moved, but no street name was found. Type the street details manually.',
                        address ? 'success' : 'warning'
                    );
                })
                .catch(() => {
                    showLocationPreview(lat, lng, [], { preserveViewport: true });
                    setLocationStatus('Pin moved, but address lookup failed. Type the street details manually.', 'warning');
                });
        });

        return;
    }

    try {
        const address = await reverseGeocodeWithOpenStreetMap(lat, lng);
        showLocationPreview(lat, lng, address || [], { preserveViewport: true });
        setLocationStatus(
            address
                ? 'Pin moved. Confirm this location to use the updated street details.'
                : 'Pin moved, but no street name was found. Type the street details manually.',
            address ? 'success' : 'warning'
        );
    } catch (error) {
        showLocationPreview(lat, lng, [], { preserveViewport: true });
        setLocationStatus('Pin moved, but address lookup failed. Type the street details manually.', 'warning');
    }
}

function streetAddressFromOpenStreetMap(data) {
    const address = data?.address || {};
    const road = address.road || address.pedestrian || address.footway || address.path || '';

    if (!road) {
        return '';
    }

    const parts = [address.house_number, road].filter(Boolean);

    if (parts.length) {
        return parts.join(', ');
    }

    return '';
}

async function reverseGeocodeWithOpenStreetMap(lat, lng) {
    const url = new URL('https://nominatim.openstreetmap.org/reverse');
    url.searchParams.set('format', 'jsonv2');
    url.searchParams.set('lat', String(lat));
    url.searchParams.set('lon', String(lng));
    url.searchParams.set('zoom', '18');
    url.searchParams.set('addressdetails', '1');

    const response = await fetch(url.toString(), {
        headers: {
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        return '';
    }

    return streetAddressFromOpenStreetMap(await response.json());
}

function showLocationPreview(lat, lng, results = [], options = {}) {
    const bestAddress = typeof results === 'string'
        ? results
        : streetAddressFromGoogle(results);
    const detectedBarangay = detectBarangayFromLocation(lat, lng, results);
    const addressPreview = document.getElementById('address-preview-text');
    const barangayPreview = document.getElementById('barangay-preview-text');
    const serviceCenterDistance = document.getElementById('service-center-distance');
    const confirmButton = document.getElementById('confirm-current-location');

    pendingLocation = {
        lat,
        lng,
        results,
        address: bestAddress,
        barangay: detectedBarangay,
    };

    setAddressMap(lat, lng, options);

    if (addressPreview) {
        addressPreview.textContent = bestAddress || 'No street name found for this pin. Type the street/purok/house details manually.';
    }

    const nearestCenter = findNearestServiceCenter(lat, lng);
    if (serviceCenterDistance && nearestCenter) {
        const distanceKm = nearestCenter.distance;
        const distanceText = distanceKm < 1
            ? `${Math.round(distanceKm * 1000)}m`
            : `${distanceKm.toFixed(1)}km`;
        serviceCenterDistance.textContent = `Distance to ${nearestCenter.name} Service Center: ${distanceText}`;
    } else if (serviceCenterDistance) {
        serviceCenterDistance.textContent = '';
    }

    if (barangayPreview) {
        barangayPreview.textContent = detectedBarangay
            ? `Detected barangay: ${detectedBarangay}`
            : 'Barangay could not be detected. Select the barangay yourself.';
    }

    if (confirmButton) {
        confirmButton.disabled = false;
        confirmButton.innerHTML = '<i class="fas fa-check-circle"></i> Confirm this location';
    }

    setLocationStatus(
        bestAddress
            ? 'Drag the pin or tap the map if needed. Confirm it to fill the street details and barangay.'
            : 'Drag the pin or tap the map if needed. Confirming saves the pin and detected barangay, but you still need to type street details manually.',
        bestAddress ? 'success' : 'warning'
    );
}

function fillLocationFields(lat, lng, results = []) {
    document.getElementById('service-latitude').value = lat.toFixed(7);
    document.getElementById('service-longitude').value = lng.toFixed(7);

    const streetInput = document.getElementById('street-address-input');
    const detectedBarangay = detectBarangayFromLocation(lat, lng, results);
    const barangayWasSet = setBarangaySelect(detectedBarangay);
    const bestAddress = typeof results === 'string'
        ? results
        : streetAddressFromGoogle(results);

    if (bestAddress && streetInput && !isBarangayOnlyAddress(bestAddress)) {
        streetInput.value = bestAddress;
        setLocationStatus(
            barangayWasSet
                ? `Location confirmed. Street details were filled and barangay was set to ${detectedBarangay}.`
                : 'Location confirmed. Street details were filled, but barangay could not be detected.',
            barangayWasSet ? 'success' : 'warning'
        );
        return;
    }

    setLocationStatus(
        barangayWasSet
            ? `Location confirmed and barangay was set to ${detectedBarangay}. Type the street/purok/house details manually.`
            : 'Detected location saved, but no street name or barangay was found. Type the street details manually and select barangay.',
        barangayWasSet ? 'success' : 'warning'
    );
}

function confirmCurrentLocation() {
    if (!pendingLocation) {
        setLocationStatus('Detect your current location first, then confirm it.', 'warning');
        return;
    }

    fillLocationFields(pendingLocation.lat, pendingLocation.lng, pendingLocation.results);

    document.getElementById('address-map-shell')?.classList.add('hidden');

    const confirmButton = document.getElementById('confirm-current-location');
    if (confirmButton) {
        confirmButton.disabled = true;
        confirmButton.innerHTML = '<i class="fas fa-check-circle"></i> Location confirmed';
    }
}

function resetCurrentLocationButton() {
    const button = document.getElementById('use-current-location');

    if (button) {
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-location-crosshairs"></i> Use my current location';
    }
}

function useCurrentLocation() {
    const button = document.getElementById('use-current-location');

    if (!navigator.geolocation) {
        setLocationStatus('Current location is not supported by this browser.', 'error');
        return;
    }

    if (!window.L && (!googleMapsEnabled || !window.google || !window.google.maps)) {
        setLocationStatus('Map preview is still loading. Wait a moment, then try again.', 'warning');
        return;
    }

    if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Locating...';
    }

    setLocationStatus('Waiting for browser location permission...', 'neutral');

    let locationHandled = false;
    const locationTimeout = window.setTimeout(() => {
        if (locationHandled) {
            return;
        }

        locationHandled = true;
        resetCurrentLocationButton();
        setLocationStatus('Location is taking too long. Make sure location permission is allowed and GPS/location services are enabled, then try again.', 'warning');
    }, 10000);

    navigator.geolocation.getCurrentPosition(
        (position) => {
            if (locationHandled) {
                return;
            }

            locationHandled = true;
            window.clearTimeout(locationTimeout);

            const lat = Number(position.coords.latitude);
            const lng = Number(position.coords.longitude);

            showLocationPreview(lat, lng, []);
            setLocationStatus('Location found. Loading street address...', 'neutral');

            if (!googleMapsEnabled || !window.google?.maps?.Geocoder) {
                reverseGeocodeWithOpenStreetMap(lat, lng)
                    .then((address) => {
                        showLocationPreview(lat, lng, address || []);
                        setLocationStatus(
                            address
                                ? 'Street address found. Confirm the pin to fill the street details.'
                                : 'Location found, but no street address was returned. Type the street manually.',
                            address ? 'success' : 'warning'
                        );
                    })
                    .catch(() => {
                        showLocationPreview(lat, lng, []);
                        setLocationStatus('Location found, but address lookup failed. Type the street manually.', 'warning');
                    })
                    .finally(resetCurrentLocationButton);

                return;
            }

            const geocoder = new google.maps.Geocoder();
            let geocodeHandled = false;
            const geocodeTimeout = window.setTimeout(() => {
                if (geocodeHandled) {
                    return;
                }

                geocodeHandled = true;
                reverseGeocodeWithOpenStreetMap(lat, lng)
                    .then((address) => {
                        showLocationPreview(lat, lng, address || []);
                        setLocationStatus(
                            address
                                ? 'Street address found. Confirm the pin to fill the street details.'
                                : 'Location found, but no street address was returned. Type the street manually.',
                            address ? 'success' : 'warning'
                        );
                    })
                    .catch(() => {
                        showLocationPreview(lat, lng, []);
                        setLocationStatus('Location found, but address lookup failed. Type the street manually.', 'warning');
                    })
                    .finally(resetCurrentLocationButton);
            }, 8000);

            geocoder.geocode({ location: { lat, lng } }, (results, status) => {
                if (geocodeHandled) {
                    return;
                }

                geocodeHandled = true;
                window.clearTimeout(geocodeTimeout);

                if (status === 'OK' && results?.length) {
                    showLocationPreview(lat, lng, results);
                    resetCurrentLocationButton();
                    return;
                }

                reverseGeocodeWithOpenStreetMap(lat, lng)
                    .then((address) => {
                        showLocationPreview(lat, lng, address || []);
                        setLocationStatus(
                            address
                                ? 'Street address found. Confirm the pin to fill the street details.'
                                : 'Location found, but no street address was returned. Type the street manually.',
                            address ? 'success' : 'warning'
                        );
                    })
                    .catch(() => {
                        showLocationPreview(lat, lng, []);
                        setLocationStatus('Location found, but address lookup failed. Type the street manually.', 'warning');
                    })
                    .finally(resetCurrentLocationButton);
            });
        },
        (error) => {
            if (locationHandled) {
                return;
            }

            locationHandled = true;
            window.clearTimeout(locationTimeout);

            const message = error.code === error.PERMISSION_DENIED
                ? 'Location permission was denied. Allow location access in the browser, then try again.'
                : 'Current location could not be detected. Check GPS/internet and try again.';

            setLocationStatus(message, 'error');
            resetCurrentLocationButton();
        },
        {
            enableHighAccuracy: false,
            timeout: 8000,
            maximumAge: 120000,
        }
    );
}

function updatePrice() {
    const serviceType = document.querySelector('input[name="service_type"]:checked')?.value;
    const propertyType = document.querySelector('input[name="property_type"]:checked')?.value;
    const floorArea = parseInt(document.querySelector('input[name="floor_area"]')?.value || 0, 10);
    const selectedAddOns = getSelectedAddOns();
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'on_site_cash';
    const servicePlan = document.querySelector('input[name="service_plan"]:checked')?.value || 'one_time';
    const subscriptionFrequency = document.querySelector('select[name="subscription_frequency"]')?.value || 'weekly';
    const subscriptionOccurrences = parseInt(document.querySelector('select[name="subscription_occurrences"]')?.value || 4, 10);
    const scheduledDate = document.querySelector('input[name="scheduled_date"]')?.value || '';
    const scheduledTime = document.querySelector('select[name="scheduled_time"]')?.value || '';

    const isPerSquareMeter = perSquareMeterServices.has(serviceType);
    const flatRateRange = flatRateRangeServices[serviceType] || null;
    const isFlatRateRange = Boolean(flatRateRange);
    const basePrice = isPerSquareMeter
        ? 0
        : isFlatRateRange
        ? Number(basePrices[serviceType] || flatRateRange?.min || 0)
        : Number(basePrices[serviceType] || 0);
    const propertyFee = isFlatRateRange ? 0 : (propertyFees[propertyType] || 0);
    const floorAreaRate = floorAreaRates[serviceType] || 0;
    const billableFloorArea = isFlatRateRange ? 0 : isPerSquareMeter ? Math.max(0, floorArea) : Math.max(0, floorArea - includedFloorArea);
    const floorAreaFee = billableFloorArea * floorAreaRate;
    const selectedAddOnQuantities = getSelectedAddOnQuantities();
    const addOnsFee = selectedAddOns.reduce((sum, key) => sum + (Number(addOnCatalog[key]?.price || 0) * Number(selectedAddOnQuantities[key] || 1)), 0);
    const total = basePrice + propertyFee + floorAreaFee + addOnsFee;

    document.getElementById('pb-base').textContent = formatCurrency(basePrice);
    document.getElementById('pb-base-row').classList.toggle('hidden', isPerSquareMeter);
    document.getElementById('pb-property').textContent = formatCurrency(propertyFee);
    document.getElementById('pb-floor-area').textContent = floorAreaFee > 0 ? '+' + formatCurrency(floorAreaFee) : formatCurrency(0);
    document.getElementById('pb-add-ons').textContent = addOnsFee > 0 ? '+' + formatCurrency(addOnsFee) : formatCurrency(0);
    document.getElementById('pb-total').textContent = formatCurrency(total);
    document.getElementById('pb-current-service').textContent = serviceType ? (serviceLabels[serviceType] || 'Selected service') : 'Choose a service';
    document.getElementById('pb-current-property').textContent = propertyType ? (propertyTypeLabels[propertyType] || 'Selected property') : 'Choose a property';
    document.getElementById('pb-current-schedule').textContent = formatSchedule(scheduledDate, scheduledTime).replace(/\s*[\u00C2\u00B7]+\s*/g, ' | ');
    document.getElementById('pb-current-plan').textContent = servicePlanLabels[servicePlan] || 'One-Time Booking';
    document.getElementById('pb-current-payment').textContent = paymentMethodLabels[paymentMethod] || 'Cash on Service Day';

    document.getElementById('pb-property-meta').textContent = propertyType
        ? isFlatRateRange
            ? `${serviceLabels[serviceType]} uses flat-rate pricing for standard homes.`
            : `${propertyTypeLabels[propertyType] || 'Selected property'}${propertyFee > 0 ? ' adds an adjustment.' : ' has no extra charge.'}`
        : 'Select a property type.';
    document.getElementById('pb-floor-area-meta').textContent = floorArea > 0
        ? isFlatRateRange
            ? `Floor area is covered by the selected flat-rate package.`
            : isPerSquareMeter
            ? `${billableFloorArea} sqm x ${formatCurrency(floorAreaRate)}/sqm`
            : `${billableFloorArea} billable sqm x ${formatCurrency(floorAreaRate)}/sqm after ${includedFloorArea} sqm included`
        : `Enter floor area to compute any excess-square-meter charge.`;
    document.getElementById('pb-add-ons-meta').textContent = selectedAddOns.length > 0
        ? selectedAddOns.map((key) => {
            const quantity = Number(selectedAddOnQuantities[key] || 1);
            const unit = addOnCatalog[key]?.pricing_unit || 'per booking';

            return unit === 'per booking' ? addOnCatalog[key]?.label : `${addOnCatalog[key]?.label} (${quantity} ${unit.replace(/^per /, '')})`;
        }).join(', ')
        : 'No add-ons selected.';

    const floorAreaRule = document.getElementById('floor-area-rule');
    const scopeLimitNote = document.getElementById('scope-limit-note');
    const floorAreaInput = document.querySelector('input[name="floor_area"]');
    const scope = serviceScope[serviceType] || null;
    const scopeLimit = Number(scope?.max_floor_area || 0);
    const scopeApproved = scope?.status === 'approved';

    if (floorAreaInput) {
        floorAreaInput.max = scopeApproved && scopeLimit > 0 ? String(scopeLimit) : '1000';
    }

    if (scopeLimitNote) {
        scopeLimitNote.textContent = scope
            ? scopeLimit > 0
                ? (scopeApproved ? 'Approved limit: up to ' : 'Provisional planning limit: up to ')
                    + scopeLimit
                    + ' sqm with '
                    + (scope.cleaner_count || 1)
                    + ' cleaner'
                    + (Number(scope.cleaner_count || 1) === 1 ? '' : 's')
                    + (scopeApproved ? '. Larger requests are blocked for manual quoting.' : (scope.manual_review_above_limit ? '. Larger requests are sent for manual review.' : '. Larger requests require staff confirmation.'))
                : 'No measurable area limit is configured; confirm the workload before accepting the booking.'
            : 'Select a service to see its measurable scope limit.';
    }

    const cleanerCountNote = document.getElementById('cleaner-count-note');
    if (cleanerCountNote) {
        const cleanerCapacity = Number(scope?.capacity_sqm_per_cleaner || 0);
        const requiredCleaners = cleanerCapacity > 0 && floorArea > 0
            ? Math.ceil(floorArea / cleanerCapacity)
            : 0;
        const maxCleaners = {{ (int) config('cleanflow.staffing.max_cleaners_per_booking', 20) }};

        cleanerCountNote.textContent = scope && requiredCleaners > 0
            ? `${requiredCleaners} cleaner${requiredCleaners === 1 ? '' : 's'} recommended for ${floorArea} sqm (${cleanerCapacity} sqm per cleaner).`
                + ` Estimated service time: ${Number(scope.base_duration_minutes || scheduleAvailability.serviceDurations?.[serviceType] || 120) * requiredCleaners} minutes.`
                + (requiredCleaners > maxCleaners ? ` More than ${maxCleaners} cleaners requires manual review.` : '')
            : 'Select a service and floor area to estimate the required cleaners.';
    }

    if (floorAreaRule) {
        floorAreaRule.textContent = serviceType
            ? isFlatRateRange
                ? `${serviceLabels[serviceType]} is quoted as a ${formatCurrency(flatRateRange.min)}-${formatCurrency(flatRateRange.max)} flat rate for a standard 2-3 bedroom home.`
                : isPerSquareMeter
                ? `${serviceLabels[serviceType]} is billed at ${formatCurrency(floorAreaRate)}/sqm using the full cleanable floor area across all floors. Rooms and bathrooms/CRs are recorded for planning and are not separate charges.`
                : `The first ${includedFloorArea} sqm are included in ${serviceLabels[serviceType]}. Excess floor area is billed at ${formatCurrency(floorAreaRate)}/sqm.`
            : `Floor area is billed per sqm based on the selected service.`;
    }

    const paymentSummaryNote = document.getElementById('payment-summary-note');
    if (paymentSummaryNote) {
        paymentSummaryNote.textContent = paymentMethod === 'on_site_cash'
            ? 'This estimate includes the selected service, floor area, and add-ons. Cash payments stay pending until the service is completed and confirmed by admin.'
            : `This estimate includes the selected service, floor area, and add-ons. ${paymentMethodLabels[paymentMethod] || 'Digital payment'} is recorded immediately with a payment reference.`;
    }

    const servicePlanSummaryNote = document.getElementById('service-plan-summary-note');
    if (servicePlanSummaryNote) {
        servicePlanSummaryNote.textContent = servicePlan === 'subscription'
            ? `This booking will create ${subscriptionOccurrences} scheduled visits on a ${String(subscriptionFrequencyLabels[subscriptionFrequency] || subscriptionFrequency).toLowerCase()} plan.`
            : 'This is currently set as a one-time booking.';
    }
}

function updateBookingSteppers() {
    document.querySelectorAll('[data-stepper-target]').forEach((button) => {
        const input = document.getElementById(button.dataset.stepperTarget);
        if (!input) {
            return;
        }

        const value = Number(input.value || input.min || 1);
        const minimum = Number(input.min || 1);
        const maximum = Number(input.max || 999);
        button.disabled = button.dataset.stepperAction === 'decrement'
            ? value <= minimum
            : value >= maximum;
    });
}

document.querySelectorAll('[data-stepper-action]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.stepperTarget);
        if (!input) {
            return;
        }

        const currentValue = Number(input.value || input.min || 1);
        const minimum = Number(input.min || 1);
        const maximum = Number(input.max || 999);
        const nextValue = button.dataset.stepperAction === 'increment'
            ? Math.min(maximum, currentValue + 1)
            : Math.max(minimum, currentValue - 1);

        input.value = String(nextValue);
        input.dispatchEvent(new Event('input', { bubbles: true }));
        updateBookingSteppers();
    });
});

document.querySelectorAll('.booking-property-stepper-input').forEach((input) => {
    input.addEventListener('input', () => {
        const minimum = Number(input.min || 1);
        const maximum = Number(input.max || 999);
        const value = Number(input.value || minimum);
        input.value = String(Math.min(maximum, Math.max(minimum, value)));
        updateBookingSteppers();
    });
});

updateBookingSteppers();

function syncSelectedCards(groupName, cardSelector) {
    const selectedValue = document.querySelector(`input[name="${groupName}"]:checked`)?.value;
    document.querySelectorAll(cardSelector).forEach((card) => {
        card.classList.toggle('selected-card', card.dataset.value === selectedValue);
    });
}

function syncAddOnCards() {
    document.querySelectorAll('.addon-card').forEach((card) => {
        const checkbox = card.closest('label')?.querySelector('input[name="add_ons[]"]');
        card.classList.toggle('selected-card', Boolean(checkbox?.checked));
    });
}

const bookingProgressLinks = Array.from(document.querySelectorAll('[data-booking-progress-step]'));
const bookingStepSections = Array.from(document.querySelectorAll('[data-booking-step]'));

function updateBookingProgress() {
    if (!bookingProgressLinks.length || !bookingStepSections.length) {
        return;
    }

    const marker = Math.min(window.innerHeight * 0.32, 280);
    let activeStep = 1;

    bookingStepSections.forEach((section) => {
        if (section.getBoundingClientRect().top <= marker) {
            activeStep = Number(section.dataset.bookingStep);
        }
    });

    if (window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 4) {
        activeStep = Number(bookingStepSections.at(-1).dataset.bookingStep);
    }

    bookingProgressLinks.forEach((link) => {
        const step = Number(link.dataset.bookingProgressStep);
        const isActive = step === activeStep;
        link.classList.toggle('is-active', isActive);

        if (isActive) {
            link.setAttribute('aria-current', 'step');
        } else {
            link.removeAttribute('aria-current');
        }
    });
}

bookingProgressLinks.forEach((link) => {
    link.addEventListener('click', () => {
        window.setTimeout(updateBookingProgress, 350);
    });
});

window.addEventListener('scroll', updateBookingProgress, { passive: true });
window.addEventListener('resize', updateBookingProgress);

function toggleSubscriptionFields() {
    const servicePlan = document.querySelector('input[name="service_plan"]:checked')?.value || 'one_time';
    const subscriptionFields = document.getElementById('subscription-plan-fields');

    if (subscriptionFields) {
        subscriptionFields.classList.toggle('hidden', servicePlan !== 'subscription');
    }
}

function toggleOfficeRatePanel() {
    const propertyType = document.querySelector('input[name="property_type"]:checked')?.value;
    const officeRatesPanel = document.querySelector('[data-office-rates-panel]');

    if (officeRatesPanel) {
        officeRatesPanel.classList.toggle('hidden', propertyType !== 'office');
    }
}

function filterServicesForProperty() {
    const propertyType = document.querySelector('input[name="property_type"]:checked')?.value;
    const requiredGroup = propertyType === 'office' ? 'office' : 'residential';
    const options = Array.from(document.querySelectorAll('.service-option'));

    options.forEach((option) => {
        const input = option.querySelector('input[name="service_type"]');
        const matches = option.dataset.propertyGroup === requiredGroup;

        option.classList.toggle('hidden', !matches);
        if (input) {
            input.disabled = !matches;
            if (!matches) {
                input.checked = false;
            }
        }
    });

    let selectedService = document.querySelector('input[name="service_type"]:checked:not(:disabled)');
    if (!selectedService) {
        selectedService = document.querySelector('input[name="service_type"]:not(:disabled)');
        if (selectedService) {
            selectedService.checked = true;
        }
    }

    syncSelectedCards('service_type', '.service-card');
    refreshPreferredCleaners();
}

document.querySelectorAll('input[name="property_type"]').forEach((input) => {
    input.addEventListener('change', function () {
        syncSelectedCards('property_type', '.property-card');
        toggleOfficeRatePanel();
        filterServicesForProperty();
        updatePrice();
    });
});

document.querySelectorAll('input[name="service_type"]').forEach((input) => {
    input.addEventListener('change', function () {
        syncSelectedCards('service_type', '.service-card');
        refreshPreferredCleaners();
        updatePrice();
    });
});

document.querySelectorAll('input[name="payment_method"]').forEach((input) => {
    input.addEventListener('change', function () {
        syncSelectedCards('payment_method', '.payment-card');
        updatePrice();
    });
});

document.querySelectorAll('input[name="service_plan"]').forEach((input) => {
    input.addEventListener('change', function () {
        syncSelectedCards('service_plan', '.service-plan-card');
        toggleSubscriptionFields();
        updatePrice();
    });
});

document.querySelectorAll('input[name="scheduled_date"], select[name="scheduled_time"]').forEach((input) => {
    input.addEventListener('change', function () {
        refreshScheduleDependentFields();
        updatePrice();
    });
});

document.querySelectorAll('select[name="subscription_frequency"], select[name="subscription_occurrences"]').forEach((input) => {
    input.addEventListener('change', updatePrice);
});

document.querySelector('input[name="floor_area"]')?.addEventListener('input', updatePrice);

document.querySelectorAll('input[name="add_ons[]"]').forEach((input) => {
    input.addEventListener('change', function () {
        syncAddOnCards();
        updatePrice();
    });
});

document.querySelectorAll('input[data-add-on-quantity]').forEach((input) => {
    input.addEventListener('input', updatePrice);
    input.addEventListener('click', (event) => event.stopPropagation());
});

document.getElementById('use-current-location')?.addEventListener('click', useCurrentLocation);
document.getElementById('confirm-current-location')?.addEventListener('click', confirmCurrentLocation);

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

if (!document.querySelector('input[name="payment_method"]:checked')) {
    const firstPaymentMethod = document.querySelector('input[name="payment_method"]');
    if (firstPaymentMethod) {
        firstPaymentMethod.checked = true;
    }
}

if (!document.querySelector('input[name="service_plan"]:checked')) {
    const firstServicePlan = document.querySelector('input[name="service_plan"]');
    if (firstServicePlan) {
        firstServicePlan.checked = true;
    }
}

syncSelectedCards('property_type', '.property-card');
syncSelectedCards('service_type', '.service-card');
syncSelectedCards('payment_method', '.payment-card');
syncSelectedCards('service_plan', '.service-plan-card');
syncAddOnCards();
toggleSubscriptionFields();
toggleOfficeRatePanel();
filterServicesForProperty();
refreshScheduleDependentFields();
window.setInterval(refreshScheduleDependentFields, 60000);
updatePrice();
</script>
@if($googleMapsApiKey)
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($googleMapsApiKey) }}"></script>
@endif
@endpush
@endsection
