@extends('layouts.client')
@section('title', 'Book a Service - Home Cleaning Service')

@section('content')
@php
    $serviceBasePrices = $services->mapWithKeys(function ($service) {
        return [$service->slug => (float) $service->price];
    });
    $serviceLabels = $services->mapWithKeys(function ($service) {
        return [$service->slug => $service->name];
    });
    $propertyTypeLabels = $pricingConfig['property_type_labels'];
    $propertyFees = $pricingConfig['property_fees'];
    $includedFloorArea = $pricingConfig['included_floor_area'];
    $floorAreaRates = $pricingConfig['floor_area_rates'];
    $addOnCatalog = $pricingConfig['add_ons'];
    $servicePackages = $servicePackages ?? [];
    $paymentMethods = $paymentMethods ?? \App\Models\Booking::paymentMethods();
    $servicePlans = $servicePlans ?? \App\Models\Booking::servicePlans();
    $subscriptionFrequencies = $subscriptionFrequencies ?? \App\Models\Booking::subscriptionFrequencyLabels();
    $selectedAddOns = old('add_ons', []);
    $selectedPaymentMethod = old('payment_method', 'on_site_cash');
    $selectedServicePlan = old('service_plan', 'one_time');
    $selectedSubscriptionFrequency = old('subscription_frequency', 'weekly');
    $selectedSubscriptionOccurrences = old('subscription_occurrences', 4);
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
                        <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-white/70">Ready To Pay</div>
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

            <div class="booking-progress flex gap-2 overflow-x-auto pb-1 sm:flex-wrap">
                <span class="inline-flex min-w-max items-center gap-2 rounded-full border border-slate-200 bg-white/85 px-3 py-2 text-xs font-semibold text-slate-600 shadow-sm backdrop-blur">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-accent-500 text-[11px] text-white">1</span>
                    Property
                </span>
                <span class="inline-flex min-w-max items-center gap-2 rounded-full border border-slate-200 bg-white/85 px-3 py-2 text-xs font-semibold text-slate-600 shadow-sm backdrop-blur">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-accent-600 text-[11px] text-white">2</span>
                    Service
                </span>
                <span class="inline-flex min-w-max items-center gap-2 rounded-full border border-slate-200 bg-white/85 px-3 py-2 text-xs font-semibold text-slate-600 shadow-sm backdrop-blur">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-secondary-600 text-[11px] text-white">3</span>
                    Details
                </span>
                <span class="inline-flex min-w-max items-center gap-2 rounded-full border border-slate-200 bg-white/85 px-3 py-2 text-xs font-semibold text-slate-600 shadow-sm backdrop-blur">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-primary-600 text-[11px] text-white">4</span>
                    Schedule
                </span>
                <span class="inline-flex min-w-max items-center gap-2 rounded-full border border-slate-200 bg-white/85 px-3 py-2 text-xs font-semibold text-slate-600 shadow-sm backdrop-blur">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-accent-700 text-[11px] text-white">5</span>
                    Cleaner
                </span>
                <span class="inline-flex min-w-max items-center gap-2 rounded-full border border-slate-200 bg-white/85 px-3 py-2 text-xs font-semibold text-slate-600 shadow-sm backdrop-blur">
                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-700 text-[11px] text-white">6</span>
                    Payment
                </span>
            </div>

            <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_23rem]">
                <div class="space-y-5">
            <section class="cleanflow-panel p-6 md:p-7">
                <div class="mb-6 flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-600 text-sm font-bold text-white shadow-sm">1</div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Choose Property Type</h2>
                            <p class="text-sm text-slate-500">Start with the property that needs cleaning so the quote uses the right base adjustment.</p>
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Required</span>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <label class="block cursor-pointer">
                        <input type="radio" name="property_type" value="house" class="hidden" {{ old('property_type') == 'house' ? 'checked' : '' }}>
                        <div class="property-card selection-card {{ old('property_type') == 'house' ? 'selected-card' : '' }} h-full p-5 text-center" data-value="house">
                            <div class="text-3xl text-green-600"><i class="fas fa-house"></i></div>
                            <div class="mt-3 text-base font-semibold text-slate-900">House</div>
                            <div class="mt-1 text-xs text-slate-500">Included base rate</div>
                        </div>
                    </label>

                    <label class="block cursor-pointer">
                        <input type="radio" name="property_type" value="apartment" class="hidden" {{ old('property_type') == 'apartment' ? 'checked' : '' }}>
                        <div class="property-card selection-card {{ old('property_type') == 'apartment' ? 'selected-card' : '' }} h-full p-5 text-center" data-value="apartment">
                            <div class="text-3xl text-green-600"><i class="fas fa-building"></i></div>
                            <div class="mt-3 text-base font-semibold text-slate-900">Apartment</div>
                            <div class="mt-1 text-xs text-slate-500">Plus &#8369;200 adjustment</div>
                        </div>
                    </label>

                    <label class="block cursor-pointer sm:col-span-2 lg:col-span-1">
                        <input type="radio" name="property_type" value="boarding_house" class="hidden" {{ old('property_type') == 'boarding_house' ? 'checked' : '' }}>
                        <div class="property-card selection-card {{ old('property_type') == 'boarding_house' ? 'selected-card' : '' }} h-full p-5 text-center" data-value="boarding_house">
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

            <section class="cleanflow-panel p-6 md:p-7">
                <div class="mb-6 flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-600 text-sm font-bold text-white shadow-sm">2</div>
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
                        $serviceFeatures = array_slice($package['features'] ?? [], 0, 2);
                    @endphp
                    <label class="block cursor-pointer">
                        <input type="radio" name="service_type" value="{{ $service->slug }}" class="hidden" {{ old('service_type') == $service->slug ? 'checked' : '' }}>
                        <div class="service-card selection-card {{ old('service_type') == $service->slug ? 'selected-card' : '' }} h-full p-5 text-left" data-value="{{ $service->slug }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-2xl text-green-600">
                                    <i class="fas {{ $package['icon'] ?? 'fa-broom' }}"></i>
                                </div>
                                @if(!empty($package['badge']))
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-emerald-700">
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
                                    <i class="fas fa-check-circle mt-0.5 text-[10px] text-emerald-500"></i>
                                    <span>{{ $feature }}</span>
                                </div>
                                @endforeach
                            </div>
                            @endif
                            <div class="mt-4 text-sm font-semibold text-green-600">Starting at &#8369;{{ number_format($service->price, 0) }}</div>
                        </div>
                    </label>
                    @endforeach
                </div>
                @error('service_type')
                <p class="mt-3 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </section>

            <section class="cleanflow-panel p-6 md:p-7">
                <div class="mb-6 flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-600 text-sm font-bold text-white shadow-sm">3</div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Property Details</h2>
                            <p class="text-sm text-slate-500">These details define the basis of computation for the final quotation.</p>
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Required</span>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Number of Rooms</label>
                        <select name="rooms" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-hidden transition focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                            @for($i = 1; $i <= 10; $i++)
                            <option value="{{ $i }}" {{ old('rooms', 1) == $i ? 'selected' : '' }}>{{ $i }} Room{{ $i > 1 ? 's' : '' }}</option>
                            @endfor
                        </select>
                        <div class="mt-2 text-xs text-slate-500">Plus &#8369;50 per extra room</div>
                        @error('rooms')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Number of Bathrooms</label>
                        <select name="bathrooms" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-hidden transition focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                            @for($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}" {{ old('bathrooms', 1) == $i ? 'selected' : '' }}>{{ $i }} Bathroom{{ $i > 1 ? 's' : '' }}</option>
                            @endfor
                        </select>
                        <div class="mt-2 text-xs text-slate-500">Plus &#8369;100 per extra bathroom</div>
                        @error('bathrooms')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Floor Area (sqm)</label>
                        <input type="number" name="floor_area" value="{{ old('floor_area', $includedFloorArea) }}" min="10" max="1000" step="1" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-hidden transition focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                        <div class="mt-2 text-xs text-slate-500">The first {{ $includedFloorArea }} sqm are included. Excess area is charged per sqm based on the selected service.</div>
                        @error('floor_area')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mt-5 rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-sm text-slate-600">
                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Pricing Basis</div>
                    <div class="mt-2 leading-6" id="floor-area-rule">The first {{ $includedFloorArea }} sqm are included. Any excess area is billed per sqm based on the cleaning service you choose.</div>
                </div>

                <div class="mt-5">
                    <div class="mb-3">
                        <h3 class="text-sm font-semibold text-slate-900">Add-ons (optional)</h3>
                        <p class="mt-1 text-xs text-slate-500">Select only the extra cleaning tasks you want included in the quotation, including the eco-friendly cleaning option.</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach($addOnCatalog as $key => $addOn)
                        <label class="block cursor-pointer">
                            <input type="checkbox" name="add_ons[]" value="{{ $key }}" class="hidden" {{ in_array($key, $selectedAddOns, true) ? 'checked' : '' }}>
                            <div class="addon-card selection-card {{ in_array($key, $selectedAddOns, true) ? 'selected-card' : '' }} h-full p-4" data-value="{{ $key }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-semibold text-slate-900">{{ $addOn['label'] }}</div>
                                        <div class="mt-1 text-xs leading-5 text-slate-500">{{ $addOn['description'] }}</div>
                                    </div>
                                    <div class="text-sm font-semibold text-green-600">+&#8369;{{ number_format($addOn['price'], 0) }}</div>
                                </div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('add_ons')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    @error('add_ons.*')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>
            </section>

            <section class="cleanflow-panel p-6 md:p-7">
                <div class="mb-6 flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-600 text-sm font-bold text-white shadow-sm">4</div>
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
                        <input type="date" name="scheduled_date" value="{{ old('scheduled_date') }}" min="{{ date('Y-m-d', strtotime('+1 day')) }}" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-hidden transition focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                        @error('scheduled_date')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Preferred Time</label>
                        <select name="scheduled_time" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-hidden transition focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                            <option value="">Select time</option>
                            @foreach(['07:00', '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00'] as $time)
                            <option value="{{ $time }}" {{ old('scheduled_time') == $time ? 'selected' : '' }}>{{ date('h:i A', strtotime($time)) }}</option>
                            @endforeach
                        </select>
                        @error('scheduled_time')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Barangay</label>
                        <select name="barangay" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-hidden transition focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                            <option value="">Select barangay</option>
                            @foreach($barangays as $b)
                            <option value="{{ $b }}" {{ old('barangay') == $b ? 'selected' : '' }}>{{ ucfirst($b) }}</option>
                            @endforeach
                        </select>
                        @error('barangay')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Street / Purok / House Details</label>
                        <input type="text" name="street_address" value="{{ old('street_address') }}" placeholder="Example: Purok 5, House 12, near barangay hall" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-hidden transition focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                        @error('street_address')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="mt-4">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Special Notes (optional)</label>
                    <textarea name="notes" rows="3" placeholder="Any special instructions for our cleaning staff..." class="min-h-[100px] w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-hidden transition focus:border-primary-500 focus:ring-2 focus:ring-primary-200">{{ old('notes') }}</textarea>
                </div>
            </section>

            <section class="cleanflow-panel p-6 md:p-7">
                <div class="mb-6 flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-600 text-sm font-bold text-white shadow-sm">5</div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Preferred Cleaner</h2>
                            <p class="text-sm text-slate-500">Add a cleaner request if you already have someone in mind. We'll honor it when the slot is still open.</p>
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-700">Optional</span>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Preferred Cleaner (optional)</label>
                    <select name="preferred_staff_id" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm outline-hidden transition focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                        <option value="">No specific cleaner</option>
                        @foreach($preferredCleaners as $cleaner)
                        <option value="{{ $cleaner->id }}" {{ (string) old('preferred_staff_id') === (string) $cleaner->id ? 'selected' : '' }}>
                            {{ $cleaner->first_name }} {{ $cleaner->last_name }}{{ $cleaner->barangay ? ' - ' . ucfirst($cleaner->barangay) : '' }}
                        </option>
                        @endforeach
                    </select>
                    <div class="mt-2 text-xs leading-5 text-slate-500">Requesting a cleaner does not guarantee assignment. If they are not available at your selected date and time, we will notify you and assign another available cleaner.</div>
                    @error('preferred_staff_id')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                </div>
            </section>

            <section class="cleanflow-panel p-6 md:p-7">
                <div class="mb-6 flex items-start justify-between gap-4 border-b border-slate-100 pb-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-700 text-sm font-bold text-white shadow-sm">6</div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Payment and Service Plan</h2>
                            <p class="text-sm text-slate-500">Finish the setup by choosing how you want to pay and whether the booking should repeat automatically.</p>
                        </div>
                    </div>
                    <span class="shrink-0 rounded-full bg-accent-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-accent-700">Flexible</span>
                </div>

                <div class="space-y-6">
                    <div>
                        <div class="mb-3">
                            <h3 class="text-sm font-semibold text-slate-900">Payment Method</h3>
                            <p class="mt-1 text-xs text-slate-500">Digital payments are recorded immediately with a reference number. Cash stays pending until the service is completed.</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach($paymentMethods as $methodKey => $paymentLabel)
                            <label class="block cursor-pointer">
                                <input type="radio" name="payment_method" value="{{ $methodKey }}" class="hidden" {{ $selectedPaymentMethod === $methodKey ? 'checked' : '' }}>
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
                                <input type="radio" name="service_plan" value="{{ $planKey }}" class="hidden" {{ $selectedServicePlan === $planKey ? 'checked' : '' }}>
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

                    <div id="subscription-plan-fields" class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 {{ $selectedServicePlan === 'subscription' ? '' : 'hidden' }}">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Recurring Frequency</label>
                                <select name="subscription_frequency" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm outline-hidden transition focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                                    @foreach($subscriptionFrequencies as $frequencyKey => $frequencyLabel)
                                    <option value="{{ $frequencyKey }}" {{ $selectedSubscriptionFrequency === $frequencyKey ? 'selected' : '' }}>{{ $frequencyLabel }}</option>
                                    @endforeach
                                </select>
                                @error('subscription_frequency')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-slate-700">Number of Visits</label>
                                <select name="subscription_occurrences" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm outline-hidden transition focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                                    @for($i = 2; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ (int) $selectedSubscriptionOccurrences === $i ? 'selected' : '' }}>{{ $i }} scheduled visits</option>
                                    @endfor
                                </select>
                                @error('subscription_occurrences')<p class="mt-2 text-sm text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="mt-3 text-xs text-emerald-700" id="subscription-plan-note">
                            The system will create multiple bookings using the same service, schedule time, and service details.
                        </div>
                    </div>
                </div>
            </section>

                </div>

                <aside class="xl:sticky xl:top-28">
                    <section class="cleanflow-panel border border-slate-200 bg-white p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-[11px] font-semibold uppercase tracking-[0.2em] text-emerald-700">Live Quote</div>
                                <div class="mt-2 text-xl font-bold text-slate-900">Price Summary</div>
                                <p class="mt-1 text-sm text-slate-500">Keep an eye on the total while you build the booking.</p>
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/80 text-lg text-emerald-600 shadow-sm">
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
                            <div class="flex items-center justify-between">
                                <span>Base service price</span>
                                <span id="pb-base">&#8369;0</span>
                            </div>
                            <div class="flex items-start justify-between gap-4" id="pb-property-row">
                                <div>
                                    <span>Property type adjustment</span>
                                    <div id="pb-property-meta" class="text-xs text-slate-400">No extra charge applied.</div>
                                </div>
                                <span id="pb-property">&#8369;0</span>
                            </div>
                            <div class="flex items-start justify-between gap-4" id="pb-rooms-row">
                                <div>
                                    <span>Rooms adjustment</span>
                                    <div id="pb-rooms-meta" class="text-xs text-slate-400">1 room included in the base setup.</div>
                                </div>
                                <span id="pb-rooms">&#8369;0</span>
                            </div>
                            <div class="flex items-start justify-between gap-4" id="pb-bathrooms-row">
                                <div>
                                    <span>Bathrooms adjustment</span>
                                    <div id="pb-bathrooms-meta" class="text-xs text-slate-400">1 bathroom included in the base setup.</div>
                                </div>
                                <span id="pb-bathrooms">&#8369;0</span>
                            </div>
                            <div class="flex items-start justify-between gap-4" id="pb-floor-area-row">
                                <div>
                                    <span>Floor area adjustment</span>
                                    <div id="pb-floor-area-meta" class="text-xs text-slate-400">No billable excess sqm yet.</div>
                                </div>
                                <span id="pb-floor-area">&#8369;0</span>
                            </div>
                            <div class="flex items-start justify-between gap-4" id="pb-add-ons-row">
                                <div>
                                    <span>Add-ons</span>
                                    <div id="pb-add-ons-meta" class="text-xs text-slate-400">No add-ons selected.</div>
                                </div>
                                <span id="pb-add-ons">&#8369;0</span>
                            </div>
                            <div class="flex items-center justify-between border-t border-green-200 pt-3">
                                <span class="text-base font-semibold text-slate-900">Total Price</span>
                                <span id="pb-total" class="text-3xl font-bold tracking-tight text-green-600">&#8369;0</span>
                            </div>
                        </div>

                        <div class="mt-4 rounded-xl bg-green-100/70 px-4 py-3 text-xs font-medium text-green-700" id="payment-summary-note">
                            The total is based on the service type, property type, rooms, bathrooms, floor area, and any selected add-ons. Cash payments stay pending until the service is completed.
                        </div>
                        <div class="mt-3 rounded-xl border border-emerald-200 bg-white/80 px-4 py-3 text-xs text-slate-600" id="service-plan-summary-note">
                            This is currently set as a one-time booking.
                        </div>

                        <div class="mt-5 flex flex-col gap-3">
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-green-600 px-6 py-3.5 font-semibold text-white transition hover:bg-green-700">
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

<script>
const basePrices = @json($serviceBasePrices);
const serviceLabels = @json($serviceLabels);
const propertyFees = @json($propertyFees);
const propertyTypeLabels = @json($propertyTypeLabels);
const floorAreaRates = @json($floorAreaRates);
const includedFloorArea = @json($includedFloorArea);
const addOnCatalog = @json($addOnCatalog);
const paymentMethodLabels = @json($paymentMethods);
const servicePlanLabels = @json($servicePlans);
const subscriptionFrequencyLabels = @json($subscriptionFrequencies);
const peso = '\u20B1';

function formatCurrency(value) {
    return peso + Number(value).toLocaleString(undefined, {
        minimumFractionDigits: Number(value) % 1 === 0 ? 0 : 2,
        maximumFractionDigits: 2,
    });
}

function getSelectedAddOns() {
    return Array.from(document.querySelectorAll('input[name="add_ons[]"]:checked')).map((input) => input.value);
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

function updatePrice() {
    const serviceType = document.querySelector('input[name="service_type"]:checked')?.value;
    const propertyType = document.querySelector('input[name="property_type"]:checked')?.value;
    const rooms = parseInt(document.querySelector('select[name="rooms"]')?.value || 1, 10);
    const bathrooms = parseInt(document.querySelector('select[name="bathrooms"]')?.value || 1, 10);
    const floorArea = parseInt(document.querySelector('input[name="floor_area"]')?.value || 0, 10);
    const selectedAddOns = getSelectedAddOns();
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'on_site_cash';
    const servicePlan = document.querySelector('input[name="service_plan"]:checked')?.value || 'one_time';
    const subscriptionFrequency = document.querySelector('select[name="subscription_frequency"]')?.value || 'weekly';
    const subscriptionOccurrences = parseInt(document.querySelector('select[name="subscription_occurrences"]')?.value || 4, 10);
    const scheduledDate = document.querySelector('input[name="scheduled_date"]')?.value || '';
    const scheduledTime = document.querySelector('select[name="scheduled_time"]')?.value || '';

    const basePrice = basePrices[serviceType] || 0;
    const propertyFee = propertyFees[propertyType] || 0;
    const roomsFee = (rooms - 1) * 50;
    const bathroomsFee = (bathrooms - 1) * 100;
    const floorAreaRate = floorAreaRates[serviceType] || 0;
    const billableFloorArea = Math.max(0, floorArea - includedFloorArea);
    const floorAreaFee = billableFloorArea * floorAreaRate;
    const addOnsFee = selectedAddOns.reduce((sum, key) => sum + Number(addOnCatalog[key]?.price || 0), 0);
    const total = basePrice + propertyFee + roomsFee + bathroomsFee + floorAreaFee + addOnsFee;

    document.getElementById('pb-base').textContent = formatCurrency(basePrice);
    document.getElementById('pb-property').textContent = propertyFee > 0 ? '+' + formatCurrency(propertyFee) : formatCurrency(0);
    document.getElementById('pb-rooms').textContent = roomsFee > 0 ? '+' + formatCurrency(roomsFee) : formatCurrency(0);
    document.getElementById('pb-bathrooms').textContent = bathroomsFee > 0 ? '+' + formatCurrency(bathroomsFee) : formatCurrency(0);
    document.getElementById('pb-floor-area').textContent = floorAreaFee > 0 ? '+' + formatCurrency(floorAreaFee) : formatCurrency(0);
    document.getElementById('pb-add-ons').textContent = addOnsFee > 0 ? '+' + formatCurrency(addOnsFee) : formatCurrency(0);
    document.getElementById('pb-total').textContent = formatCurrency(total);
    document.getElementById('pb-current-service').textContent = serviceType ? (serviceLabels[serviceType] || 'Selected service') : 'Choose a service';
    document.getElementById('pb-current-property').textContent = propertyType ? (propertyTypeLabels[propertyType] || 'Selected property') : 'Choose a property';
    document.getElementById('pb-current-schedule').textContent = formatSchedule(scheduledDate, scheduledTime).replace(/\s*[\u00C2\u00B7]+\s*/g, ' | ');
    document.getElementById('pb-current-plan').textContent = servicePlanLabels[servicePlan] || 'One-Time Booking';
    document.getElementById('pb-current-payment').textContent = paymentMethodLabels[paymentMethod] || 'Cash on Service Day';

    document.getElementById('pb-property-meta').textContent = propertyType
        ? `${propertyTypeLabels[propertyType] || 'Selected property'}${propertyFee > 0 ? ' adds an adjustment.' : ' has no extra charge.'}`
        : 'Select a property type.';
    document.getElementById('pb-rooms-meta').textContent = rooms > 1
        ? `${rooms - 1} extra room${rooms - 1 > 1 ? 's' : ''} x ${formatCurrency(50)}`
        : '1 room included in the base setup.';
    document.getElementById('pb-bathrooms-meta').textContent = bathrooms > 1
        ? `${bathrooms - 1} extra bathroom${bathrooms - 1 > 1 ? 's' : ''} x ${formatCurrency(100)}`
        : '1 bathroom included in the base setup.';
    document.getElementById('pb-floor-area-meta').textContent = floorArea > 0
        ? `${billableFloorArea} billable sqm x ${formatCurrency(floorAreaRate)}/sqm after ${includedFloorArea} sqm included`
        : `Enter floor area to compute any excess-square-meter charge.`;
    document.getElementById('pb-add-ons-meta').textContent = selectedAddOns.length > 0
        ? selectedAddOns.map((key) => addOnCatalog[key]?.label).join(', ')
        : 'No add-ons selected.';

    const floorAreaRule = document.getElementById('floor-area-rule');
    if (floorAreaRule) {
        floorAreaRule.textContent = serviceType
            ? `The first ${includedFloorArea} sqm are included in ${serviceLabels[serviceType]}. Excess floor area is billed at ${formatCurrency(floorAreaRate)}/sqm.`
            : `The first ${includedFloorArea} sqm are included. Excess area is billed per sqm based on the selected service.`;
    }

    const paymentSummaryNote = document.getElementById('payment-summary-note');
    if (paymentSummaryNote) {
        paymentSummaryNote.textContent = paymentMethod === 'on_site_cash'
            ? 'The total is based on the service type, property type, rooms, bathrooms, floor area, and any selected add-ons. Cash payments stay pending until the service is completed.'
            : `The total is based on the service type, property type, rooms, bathrooms, floor area, and any selected add-ons. ${paymentMethodLabels[paymentMethod] || 'Digital payment'} is recorded immediately with a payment reference.`;
    }

    const servicePlanSummaryNote = document.getElementById('service-plan-summary-note');
    if (servicePlanSummaryNote) {
        servicePlanSummaryNote.textContent = servicePlan === 'subscription'
            ? `This booking will create ${subscriptionOccurrences} scheduled visits on a ${String(subscriptionFrequencyLabels[subscriptionFrequency] || subscriptionFrequency).toLowerCase()} plan.`
            : 'This is currently set as a one-time booking.';
    }
}

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

function toggleSubscriptionFields() {
    const servicePlan = document.querySelector('input[name="service_plan"]:checked')?.value || 'one_time';
    const subscriptionFields = document.getElementById('subscription-plan-fields');

    if (subscriptionFields) {
        subscriptionFields.classList.toggle('hidden', servicePlan !== 'subscription');
    }
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

document.querySelectorAll('select[name="rooms"], select[name="bathrooms"]').forEach((input) => {
    input.addEventListener('change', updatePrice);
});

document.querySelectorAll('input[name="scheduled_date"], select[name="scheduled_time"]').forEach((input) => {
    input.addEventListener('change', updatePrice);
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
updatePrice();
</script>
@endsection
