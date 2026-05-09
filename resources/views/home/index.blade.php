@extends('layouts.app')
@section('title', 'Home Cleaning Service - Professional Cleaning in Valencia City')

@section('content')
@php
    $servicePackages = $servicePackages ?? [];
    $includedFloorArea = (int) ($pricingConfig['included_floor_area'] ?? 30);
    $pricingAddOns = $pricingConfig['add_ons'] ?? [];

    $isAuthenticated = auth()->check();
    $userRole = $isAuthenticated ? auth()->user()->role : null;

    $primaryCtaUrl = match ($userRole) {
        'client' => route('bookings.create'),
        'staff', 'admin' => route($userRole . '.dashboard'),
        default => '#instant-quote',
    };

    $primaryCtaLabel = $isAuthenticated && $userRole === 'client'
        ? 'Book Now'
        : 'Get My Instant Quote';
    $secondaryCtaUrl = '#pricing';
    $secondaryCtaLabel = 'See Starting Prices';

    $serviceCardUrl = match ($userRole) {
        'client' => route('bookings.create'),
        'staff', 'admin' => route($userRole . '.dashboard'),
        default => '#instant-quote',
    };

    $instantQuotePackages = [
        ['slug' => 'basic', 'label' => 'Basic', 'base' => 570, 'area_rate' => 8],
        ['slug' => 'deep', 'label' => 'Deep', 'base' => 1200, 'area_rate' => 12],
        ['slug' => 'commercial', 'label' => 'Office', 'base' => 1600, 'area_rate' => 13],
        ['slug' => 'postconstruction', 'label' => 'Post-Con', 'base' => 1800, 'area_rate' => 14],
        ['slug' => 'moveinout', 'label' => 'Move-in', 'base' => 2000, 'area_rate' => 15],
    ];

    $instantQuotePropertyOptions = [
        ['key' => 'house', 'label' => 'House', 'fee' => 0],
        ['key' => 'apartment', 'label' => 'Apartment', 'fee' => 200],
        ['key' => 'boarding_house', 'label' => 'Boarding House', 'fee' => 300],
    ];
    $defaultInstantQuoteTotal = (int) (($instantQuotePackages[0]['base'] ?? 0) + ($instantQuotePropertyOptions[0]['fee'] ?? 0));

    $serviceCardLabel = $isAuthenticated && $userRole === 'client'
        ? 'Book This Service'
        : 'Get Instant Quote';
    $quoteCheckoutUrl = $isAuthenticated && $userRole === 'client'
        ? route('bookings.create')
        : route('register');
    $quoteCtaLabel = 'Continue with Estimate of &#8369;' . number_format($defaultInstantQuoteTotal, 0);
    $quoteCtaNote = $isAuthenticated && $userRole === 'client'
        ? 'Your selections open directly in the booking form.'
        : 'Use this estimate as your starting point before you continue.';

    $instantQuoteAddOnIcons = [
        'window_glass' => 'fa-panorama',
        'refrigerator' => 'fa-temperature-low',
        'inside_cabinets' => 'fa-table-cells-large',
        'sofa_vacuum' => 'fa-couch',
        'pet_hair_removal' => 'fa-paw',
        'eco_friendly_supplies' => 'fa-leaf',
    ];
    $showEarlyLaunchBanner = (bool) config('cleanflow.marketing.show_early_launch_banner', false);
    $businessStartYear = (int) config('cleanflow.marketing.business_start_year', 2024);

    $heroBenefits = [
        [
            'icon' => 'fa-id-card',
            'title' => 'NBI-Cleared Cleaning Team',
            'text' => 'Every cleaner passes an NBI background check before being assigned to a client home.',
        ],
        [
            'icon' => 'fa-calendar-check',
            'title' => 'Booking Confirmation Updates',
            'text' => 'You stay informed from schedule confirmation to service completion.',
        ],
        [
            'icon' => 'fa-camera',
            'title' => 'Photo Proof of Service',
            'text' => 'Completed visits can include before-and-after photos for extra peace of mind.',
        ],
    ];

    $heroTrustPoints = [
        'Instant quote before you commit',
        'NBI-cleared cleaners',
        'Same-day slots when availability opens',
        'One-time or recurring cleaning plans',
    ];

    $trustSignals = [
        [
            'icon' => 'fa-tags',
            'title' => 'Upfront starting prices',
            'description' => 'See package pricing early, then use the instant quote to estimate your real total.',
        ],
        [
            'icon' => 'fa-location-dot',
            'title' => 'All ' . $stats['barangays'] . ' barangays covered',
            'description' => 'Service is available across every barangay in Valencia City.',
        ],
        [
            'icon' => 'fa-bolt',
            'title' => 'Flexible booking options',
            'description' => 'Choose one-time, weekly, bi-weekly, or monthly cleaning based on your routine.',
        ],
        [
            'icon' => 'fa-user-shield',
            'title' => 'Local support team',
            'description' => 'Cleaner assignments and schedule confirmations are handled in-house by our Valencia City team.',
        ],
    ];

    $workflowSteps = [
        [
            'title' => 'Get an instant quote',
            'desc' => 'Choose the service, home type, and add-ons. See your estimate before you commit.',
            'icon' => 'fa-receipt',
            'classes' => 'bg-primary-100 text-primary-600',
        ],
        [
            'title' => 'Pick your schedule',
            'desc' => 'Choose the date and time that fit your week, then confirm the details for your home.',
            'icon' => 'fa-calendar-check',
            'classes' => 'bg-primary-100 text-primary-600',
        ],
        [
            'title' => 'Relax while we clean',
            'desc' => 'Your NBI-cleared cleaner arrives, completes the job, and sends photo proof when done.',
            'icon' => 'fa-circle-check',
            'classes' => 'bg-primary-100 text-primary-600',
        ],
    ];

    $faqs = [
        [
            'question' => 'Which areas do you currently serve?',
            'answer' => 'We serve all ' . $stats['barangays'] . ' barangays in Valencia City.',
        ],
        [
            'question' => 'How is pricing computed?',
            'answer' => 'Each package has a starting rate. Your final total depends on your property type, bedrooms, bathrooms, floor area beyond ' . $includedFloorArea . ' sqm, and any add-ons you choose.',
        ],
        [
            'question' => 'Which payment methods are available?',
            'answer' => 'You can pay with cash on cleaning day, GCash, or Maya.',
        ],
        [
            'question' => 'Can I request eco-friendly cleaning?',
            'answer' => 'Yes. You can add eco-friendly supplies when you book if you prefer a greener clean.',
        ],
        [
            'question' => 'Can I book recurring cleaning visits?',
            'answer' => 'Yes. You can choose one-time, weekly, bi-weekly, or monthly cleaning.',
        ],
        [
            'question' => 'Can I request a specific cleaner?',
            'answer' => 'Yes. Request your preferred cleaner during booking and we will let you know if that person is available for your schedule.',
        ],
        [
            'question' => 'Can I cancel or reschedule a booking?',
            'answer' => 'Yes. If your booking is still waiting for confirmation and no cleaner has been assigned yet, you can change or cancel it from your account.',
        ],
        [
            'question' => 'Are your cleaners background-checked?',
            'answer' => 'Yes. Every cleaner on our team passes an NBI clearance check before being assigned to any client home.',
        ],
        [
            'question' => 'How do I track my booking?',
            'answer' => 'Once we confirm your schedule, you can follow updates from your account.',
        ],
        [
            'question' => 'Is there a cancellation fee?',
            'answer' => 'There is no fee for cancelling a pending booking before a cleaner has been assigned.',
        ],
    ];
@endphp

<div class="home-page bg-slate-50">
    <section class="home-hero-shell relative overflow-hidden text-white">
        <div class="hero-section container-pad relative z-10 mx-auto max-w-5xl px-6 pt-16 pb-14 lg:pt-24 lg:pb-20">
            <div class="mx-auto space-y-6 text-center reveal-on-scroll">
                @if($showEarlyLaunchBanner)
                <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/12 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-white/85">
                    <i class="fas fa-bullhorn"></i>
                    Early launch in progress: limited daily slots may open first to reviewed bookings
                </div>
                @endif
                <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-primary-50">
                    <i class="fas fa-house text-white"></i>
                    Trusted home cleaning for Valencia City
                </span>
                <h1 class="mx-auto max-w-3xl text-4xl font-extrabold leading-[1.06] text-white sm:text-5xl lg:text-5xl">
                    Professional Home Cleaning — Priced Upfront, Done Right
                </h1>
                <p class="mx-auto max-w-2xl text-base leading-8 text-primary-50/90 lg:text-lg">
                    Get an instant quote, pick your schedule, and let an NBI-cleared local team handle the rest. No surprises on price. No strangers you can't verify.
                </p>
                <div class="hero-buttons flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ $primaryCtaUrl }}" class="sales-primary-button inline-flex items-center justify-center gap-2 rounded-full px-6 py-3 font-semibold text-white transition hover:-translate-y-0.5">
                        <i class="fas fa-broom"></i>
                        <span>{{ $primaryCtaLabel }}</span>
                    </a>
                    <a href="{{ $secondaryCtaUrl }}" class="sales-secondary-button inline-flex items-center justify-center gap-2 rounded-full px-6 py-3 font-medium transition">
                        <i class="fas fa-tags"></i>
                        <span>{{ $secondaryCtaLabel }}</span>
                    </a>
                </div>
                <p class="text-sm font-medium text-white/75">
                    See your price first. Book only when you're ready.
                </p>
                <div class="flex flex-wrap justify-center gap-2 pt-1 text-xs text-primary-50/90 lg:text-sm">
                    @foreach($heroTrustPoints as $point)
                    <div class="home-trust-pill inline-flex items-center gap-2 rounded-full px-3 py-2">
                        <i class="fas fa-check-circle text-white"></i>
                        <span>{{ $point }}</span>
                    </div>
                    @endforeach
                </div>
                <div class="mt-10 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    @foreach($heroBenefits as $benefit)
                    <div class="flex items-start gap-3 rounded-2xl border border-white/15 bg-white/10 p-4 text-left backdrop-blur-sm">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/15 text-white">
                            <i class="fas {{ $benefit['icon'] }}"></i>
                        </div>
                        <div>
                            <div class="text-sm font-bold text-white">{{ $benefit['title'] }}</div>
                            <div class="mt-1 text-xs leading-5 text-white/70">{{ $benefit['text'] }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="section-padding bg-white py-20">
        <div class="container-pad mx-auto max-w-7xl px-6">
            <div class="section-heading mx-auto mb-14 max-w-3xl text-center reveal-on-scroll">
                <h2 class="section-title text-3xl font-bold text-slate-900 lg:text-5xl">How it works</h2>
                <p class="section-subtitle mt-4 text-lg leading-8 text-slate-500">
                    The path should feel easy: price first, schedule second, clean home third.
                </p>
            </div>
            <div class="relative">
                <div class="absolute left-[18%] right-[18%] top-10 hidden h-px bg-slate-200 xl:block"></div>
                <div class="steps-grid relative grid grid-cols-1 gap-8 md:grid-cols-3">
                    @foreach($workflowSteps as $index => $step)
                    <div class="relative reveal-on-scroll">
                        <div class="workflow-card rounded-3xl border border-slate-200 bg-slate-50 p-7 text-center shadow-sm">
                            <div class="mx-auto flex h-18 w-18 items-center justify-center rounded-full border-4 border-white {{ $step['classes'] }} text-xl shadow-sm">
                                {{ $index + 1 }}
                            </div>
                            <div class="mt-4 flex justify-center">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl {{ $step['classes'] }}">
                                    <i class="fas {{ $step['icon'] }}"></i>
                                </div>
                            </div>
                            <h3 class="mt-5 text-xl font-bold text-slate-900">{{ $step['title'] }}</h3>
                            <p class="mt-4 text-sm leading-7 text-slate-500">{{ $step['desc'] }}</p>
                        </div>
                        @if(!$loop->last)
                        <div class="workflow-arrow absolute -right-6 top-1/2 z-10 hidden h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full border border-primary-100 bg-white text-primary-600 shadow-sm xl:flex">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            <p class="mx-auto mt-8 max-w-3xl text-center text-sm font-medium leading-7 text-slate-500 reveal-on-scroll">
                Start with the quote on this page, then continue into scheduling and booking.
            </p>
        </div>
    </section>

    <section class="stats-section bg-slate-50 py-14">
        <div class="container-pad mx-auto max-w-7xl px-6">
            <div class="stats-grid grid grid-cols-2 gap-6 md:grid-cols-4">
                @foreach($trustSignals as $signal)
                <div class="stats-card rounded-3xl border p-6 text-left shadow-sm reveal-on-scroll">
                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-50 text-primary-600">
                        <i class="fas {{ $signal['icon'] }}"></i>
                    </div>
                    <div class="mt-4 text-lg font-extrabold text-slate-900">{{ $signal['title'] }}</div>
                    <div class="mt-2 text-sm leading-6 text-slate-500">{{ $signal['description'] }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="services" class="section-padding bg-white py-20">
        <div class="container-pad mx-auto max-w-7xl px-6">
            <div id="pricing" class="scroll-mt-28"></div>
            <div class="section-heading mx-auto mb-12 max-w-3xl text-center reveal-on-scroll">
                <h2 class="section-title text-3xl font-bold text-slate-900 lg:text-5xl">Choose the clean that fits your home</h2>
                <p class="section-subtitle mt-4 text-lg leading-8 text-slate-500">
                    Starting prices are easy to scan. Use the instant quote below to see what your home is likely to cost before you book.
                </p>
            </div>
            <div class="services-grid grid grid-cols-1 gap-8 md:grid-cols-2 xl:grid-cols-3">
                @foreach($services as $service)
                @php
                    $package = $servicePackages[$service->slug] ?? null;
                    $features = array_slice($package['features'] ?? [], 0, 3);
                @endphp
                <article class="service-card reveal-on-scroll flex h-full flex-col rounded-3xl border border-slate-200 p-8 shadow-sm transition duration-300 hover:-translate-y-2 hover:shadow-xl">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-100 text-primary-600">
                            <i class="fas {{ $package['icon'] ?? 'fa-broom' }} text-xl"></i>
                        </div>
                        @if(!empty($package['badge']))
                        <span class="rounded-full bg-primary-50 px-4 py-2 text-[11px] font-semibold uppercase tracking-wide text-primary-700">
                            {{ $package['badge'] }}
                        </span>
                        @endif
                    </div>
                    <h3 class="mt-6 text-2xl font-bold text-slate-900">{{ $service->name }}</h3>
                    <p class="mt-4 text-sm leading-7 text-slate-500">{{ $package['summary'] ?? $service->description }}</p>
                    @if(!empty($features))
                    <div class="mt-5 space-y-3">
                        @foreach($features as $feature)
                        <div class="flex items-start gap-3 text-sm leading-6 text-slate-500">
                            <i class="fas fa-check-circle mt-1 text-primary-500"></i>
                            <span>{{ $feature }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                    <div class="mt-5 text-base font-semibold text-primary-600">
                        Starting at &#8369;{{ number_format($service->price, 0) }}
                    </div>
                    <div class="service-note mt-5 rounded-2xl bg-slate-50 px-4 py-4 text-sm leading-6 text-slate-600">
                        {{ $package['highlight'] ?? 'This package can be requested directly through our Valencia City cleaning team.' }}
                    </div>
                    <a href="{{ $serviceCardUrl }}" class="sales-primary-button service-action mt-7 inline-flex w-full items-center justify-center rounded-2xl px-6 py-3.5 text-sm font-semibold text-white transition hover:-translate-y-0.5">
                        {{ $serviceCardLabel }}
                    </a>
                </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="instant-quote" class="section-padding bg-white py-20">
        <div class="container-pad mx-auto max-w-7xl px-6">
            <div class="section-heading mx-auto mb-12 max-w-3xl text-center reveal-on-scroll">
                <h2 class="section-title text-3xl font-bold text-slate-900 lg:text-5xl">Get your instant quote</h2>
                <p class="section-subtitle mt-4 text-lg leading-8 text-slate-500">
                    Build the price live. The total stays visible while you compare packages, home size, and add-ons.
                </p>
            </div>
            <div class="mt-12 reveal-on-scroll">
                <div class="relative overflow-hidden rounded-4xl border border-white/45 bg-linear-to-br from-white/75 via-sky-50/70 to-slate-100/80 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.14)] backdrop-blur-xl lg:p-8">
                    <div class="absolute inset-x-0 top-0 h-1.5 bg-linear-to-r from-primary-500 via-cyan-400 to-primary-600"></div>
                    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
                        <div>
                            <div class="inline-flex items-center gap-2 rounded-full border border-primary-200/70 bg-white/70 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-primary-700">
                                <i class="fas fa-bolt"></i>
                                Instant Quote
                            </div>
                            <h3 class="mt-4 text-2xl font-extrabold text-slate-900 sm:text-3xl">Estimate your cleaning total in seconds</h3>
                            <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">
                                Set your package, property type, floor area, add-ons, and extra rooms. The estimate updates live so you can compare options without losing the total.
                            </p>

                            <div class="mt-6 space-y-5">
                                <div>
                                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-600">Choose Package</label>
                                    <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
                                        @foreach($instantQuotePackages as $package)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="iq_package" value="{{ $package['slug'] }}" class="peer sr-only" {{ $loop->first ? 'checked' : '' }}>
                                            <span class="block rounded-2xl border border-slate-200 bg-white/80 px-3 py-3 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md peer-checked:border-primary-400 peer-checked:bg-primary-50/80">
                                                <span class="block text-sm font-bold text-slate-900">{{ $package['label'] }}</span>
                                                <span class="mt-0.5 block text-xs text-slate-500">&#8369;{{ number_format($package['base'], 0) }} base</span>
                                                <span class="mt-1 block text-[11px] text-slate-500">
                                                    @if($package['area_rate'] > 0)
                                                    &#8369;{{ number_format($package['area_rate'], 0) }}/sqm over {{ $includedFloorArea }}
                                                    @else
                                                    No sqm excess fee
                                                    @endif
                                                </span>
                                            </span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="iq_property" class="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-600">Property Type</label>
                                        <select id="iq_property" class="w-full rounded-2xl border border-slate-200 bg-white/85 px-4 py-3 text-sm font-medium text-slate-700 outline-hidden transition focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                                            @foreach($instantQuotePropertyOptions as $property)
                                            <option value="{{ $property['key'] }}">{{ $property['label'] }}{{ $property['fee'] > 0 ? ' (+₱' . number_format($property['fee'], 0) . ')' : ' (+₱0)' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label for="iq_rooms" class="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-600">Bedrooms</label>
                                            <select id="iq_rooms" class="w-full rounded-2xl border border-slate-200 bg-white/85 px-3 py-3 text-sm font-medium text-slate-700 outline-hidden transition focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                                                @for($i = 1; $i <= 8; $i++)
                                                <option value="{{ $i }}">{{ $i }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div>
                                            <label for="iq_bathrooms" class="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-600">Bathrooms</label>
                                            <select id="iq_bathrooms" class="w-full rounded-2xl border border-slate-200 bg-white/85 px-3 py-3 text-sm font-medium text-slate-700 outline-hidden transition focus:border-primary-500 focus:ring-2 focus:ring-primary-200">
                                                @for($i = 1; $i <= 6; $i++)
                                                <option value="{{ $i }}">{{ $i }}</option>
                                                @endfor
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-white/70 bg-white/70 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <label for="iq_floor_area" class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-600">Floor Area</label>
                                        <span id="iq_floor_area_value" class="text-sm font-bold text-slate-900">{{ $includedFloorArea }} sqm</span>
                                    </div>
                                    <input id="iq_floor_area" type="range" min="{{ $includedFloorArea }}" max="200" step="1" value="{{ $includedFloorArea }}" class="mt-4 h-2.5 w-full cursor-pointer appearance-none rounded-full bg-slate-200 accent-primary-600">
                                    <div class="mt-2 flex items-center justify-between text-[11px] font-medium text-slate-500">
                                        <span>{{ $includedFloorArea }} sqm</span>
                                        <span>200 sqm</span>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-white/70 bg-white/70 p-4">
                                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-600">Add-ons</label>
                                    <p class="mb-3 text-xs text-slate-500">Tap to include extras and instantly update your quote.</p>
                                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        @foreach($pricingAddOns as $key => $addOn)
                                        <label class="cursor-pointer">
                                            <input type="checkbox" name="iq_add_ons[]" value="{{ $key }}" class="peer sr-only">
                                            <span class="flex h-full items-start gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-primary-300 hover:shadow-md peer-checked:border-primary-500 peer-checked:bg-primary-50">
                                                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 peer-checked:bg-primary-100 peer-checked:text-primary-700">
                                                    <i class="fas {{ $instantQuoteAddOnIcons[$key] ?? 'fa-sparkles' }}"></i>
                                                </span>
                                                <span class="min-w-0">
                                                    <span class="block text-sm font-semibold text-slate-900">{{ $addOn['label'] }}</span>
                                                    <span class="mt-0.5 block text-xs text-primary-700">+&#8369;{{ number_format($addOn['price'], 0) }}</span>
                                                </span>
                                            </span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <aside class="instant-quote-summary hidden rounded-3xl border border-white/70 bg-white/78 p-5 shadow-lg backdrop-blur-md lg:block sm:p-6">
                            <div class="text-xs font-semibold uppercase tracking-[0.16em] text-primary-700">Total Estimate</div>
                            <div id="iq_total" class="mt-3 text-4xl font-extrabold tracking-tight text-slate-900">&#8369;{{ number_format($defaultInstantQuoteTotal, 0) }}</div>
                            <div class="mt-2 text-xs font-medium text-slate-500">For Valencia City service areas. Final total is confirmed before the booking is submitted.</div>

                            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50/85 p-4">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-600">Live Breakdown</div>
                                <ul id="iq_breakdown_list" class="mt-3 space-y-2 text-sm text-slate-700"></ul>
                                <p id="iq_formula_line" class="mt-3 text-xs leading-5 text-slate-500"></p>
                            </div>

                            <a id="iq_book_button" href="{{ $quoteCheckoutUrl }}" class="sales-primary-button mt-5 inline-flex w-full items-center justify-center gap-2 rounded-2xl px-6 py-3.5 text-sm font-bold text-white transition hover:-translate-y-0.5">
                                <i class="fas fa-receipt"></i>
                                <span id="iq_book_button_label">{!! $quoteCtaLabel !!}</span>
                            </a>
                            <p class="mt-2 text-center text-[11px] text-slate-500">{{ $quoteCtaNote }}</p>
                        </aside>
                    </div>
                </div>
            </div>
            <div class="instant-quote-mobile-sheet lg:hidden">
                <div class="instant-quote-mobile-sheet__inner">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-primary-700">Total Estimate</div>
                            <div id="iq_mobile_total" class="mt-2 truncate text-2xl font-extrabold tracking-tight text-slate-900">&#8369;{{ number_format($defaultInstantQuoteTotal, 0) }}</div>
                            <div class="mt-1 text-[11px] text-slate-500">Live estimate while you compare add-ons and home size.</div>
                        </div>
                        <div class="rounded-full bg-primary-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-primary-700">Sticky</div>
                    </div>
                    <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50/95 p-4">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-600">Live Breakdown</div>
                        <ul id="iq_mobile_breakdown_list" class="mt-3 max-h-28 space-y-2 overflow-y-auto pr-1 text-sm text-slate-700"></ul>
                        <p id="iq_mobile_formula_line" class="mt-3 text-xs leading-5 text-slate-500"></p>
                    </div>
                    <a id="iq_fab_button" href="{{ $quoteCheckoutUrl }}" class="sales-primary-button mt-4 flex items-center justify-center gap-2 rounded-2xl px-4 py-3 text-center text-white">
                        <i class="fas fa-arrow-right"></i>
                        <span id="iq_fab_button_label">{!! $quoteCtaLabel !!}</span>
                    </a>
                </div>
            </div>
            <p class="mt-8 text-center text-sm font-medium text-slate-500 reveal-on-scroll">
                No hidden fees. Your total is confirmed before the booking is processed.
            </p>
        </div>
    </section>


    <section id="faq" class="section-padding bg-slate-50 py-20">
        <div class="container-pad mx-auto max-w-7xl px-6">
            <div class="section-heading mx-auto mb-12 max-w-3xl text-center reveal-on-scroll">
                <h2 class="section-title text-3xl font-bold text-slate-900 lg:text-5xl">Frequently asked questions</h2>
                <p class="section-subtitle mt-4 text-lg leading-8 text-slate-500">
                    Clear answers for the questions that usually slow down a first booking.
                </p>
            </div>
            <div class="faq-list mx-auto max-w-4xl space-y-4">
                @foreach($faqs as $faq)
                <details class="faq-accordion faq-item group rounded-3xl border border-slate-200 bg-white px-6 py-5 shadow-sm reveal-on-scroll">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                        <span class="faq-question text-left text-lg font-semibold text-slate-900">{{ $faq['question'] }}</span>
                        <span class="faq-chevron flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition">
                            <i class="fas fa-chevron-down text-sm"></i>
                        </span>
                    </summary>
                    <div class="faq-answer mt-4 border-t border-slate-100 pt-4 text-sm leading-7 text-slate-500">
                        {{ $faq['answer'] }}
                    </div>
                </details>
                @endforeach
            </div>
        </div>
    </section>

    <section class="home-cta-shell cta-section py-24 text-white">
        <div class="container-pad mx-auto max-w-4xl px-6 text-center reveal-on-scroll">
            <h2 class="text-4xl font-extrabold lg:text-5xl">See your price. Book only when you're ready.</h2>
            <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-white/80">
                NBI-cleared cleaners. Upfront pricing. No commitment until you confirm.
            </p>
            <div class="cta-buttons mt-10 flex flex-wrap justify-center gap-4">
                <a href="{{ $primaryCtaUrl }}" class="sales-primary-button rounded-2xl px-8 py-3.5 font-semibold text-white transition hover:-translate-y-0.5">{{ $primaryCtaLabel }}</a>
                <a href="{{ route('login') }}" class="sales-secondary-button rounded-2xl px-8 py-3.5 font-medium transition">Login</a>
            </div>
            <div class="cta-note mt-8 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm text-white/80">
                <i class="fas fa-comment-dots text-white/60"></i>
                <span>Need help first? Message us during operating hours.</span>
            </div>
        </div>
    </section>
</div>

@push('scripts')
<script>
(() => {
    const packageMap = @json(collect($instantQuotePackages)->mapWithKeys(fn ($package) => [$package['slug'] => $package])->all());
    const propertyMap = @json(collect($instantQuotePropertyOptions)->mapWithKeys(fn ($property) => [$property['key'] => $property])->all());
    const addOnCatalog = @json($pricingAddOns);
    const checkoutBaseUrl = @json($quoteCheckoutUrl);
    const includedArea = @json($includedFloorArea);
    const maxArea = 200;
    const extraBedroomRate = 50;
    const extraBathroomRate = 100;
    const ecoAddOnKey = 'eco_friendly_supplies';

    const packageInputs = Array.from(document.querySelectorAll('input[name="iq_package"]'));
    const propertySelect = document.getElementById('iq_property');
    const roomsSelect = document.getElementById('iq_rooms');
    const bathroomsSelect = document.getElementById('iq_bathrooms');
    const floorAreaSlider = document.getElementById('iq_floor_area');
    const floorAreaValue = document.getElementById('iq_floor_area_value');
    const addOnInputs = Array.from(document.querySelectorAll('input[name="iq_add_ons[]"]'));
    const totalElement = document.getElementById('iq_total');
    const mobileTotalElement = document.getElementById('iq_mobile_total');
    const breakdownListElement = document.getElementById('iq_breakdown_list');
    const mobileBreakdownListElement = document.getElementById('iq_mobile_breakdown_list');
    const formulaLineElement = document.getElementById('iq_formula_line');
    const mobileFormulaLineElement = document.getElementById('iq_mobile_formula_line');
    const bookButton = document.getElementById('iq_book_button');
    const bookButtonLabel = document.getElementById('iq_book_button_label');
    const fabButton = document.getElementById('iq_fab_button');
    const fabButtonLabel = document.getElementById('iq_fab_button_label');
    const ecoAddOnInput = addOnInputs.find((input) => input.value === ecoAddOnKey) ?? null;

    if (!packageInputs.length || !propertySelect || !roomsSelect || !bathroomsSelect || !floorAreaSlider || !floorAreaValue || !mobileTotalElement || !mobileBreakdownListElement || !mobileFormulaLineElement || !fabButton || !bookButton) {
        return;
    }

    const formatPeso = (amount) => `\u20B1${Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
    let displayedTotal = 0;
    let totalAnimationFrame = null;
    let lastSelectedPackageSlug = packageInputs.find((input) => input.checked)?.value ?? packageInputs[0].value;

    const getSelectedPackage = () => {
        const checkedPackage = packageInputs.find((input) => input.checked)?.value || packageInputs[0].value;
        return packageMap[checkedPackage];
    };

    const getFloorArea = () => {
        const rawFloorArea = Number.parseInt(floorAreaSlider.value, 10);

        if (!Number.isFinite(rawFloorArea)) {
            return includedArea;
        }

        return Math.min(maxArea, Math.max(includedArea, rawFloorArea));
    };

    const updateTotalDisplays = (value) => {
        if (totalElement) {
            totalElement.textContent = formatPeso(value);
        }

        if (mobileTotalElement) {
            mobileTotalElement.textContent = formatPeso(value);
        }

        const dynamicLabel = `Continue with Estimate of ${formatPeso(value)}`;

        if (bookButtonLabel) {
            bookButtonLabel.textContent = dynamicLabel;
        }

        if (fabButtonLabel) {
            fabButtonLabel.textContent = dynamicLabel;
        }
    };

    const animateTotalTo = (nextTotal, shouldAnimate = false) => {
        if (totalAnimationFrame) {
            cancelAnimationFrame(totalAnimationFrame);
            totalAnimationFrame = null;
        }

        if (!shouldAnimate) {
            displayedTotal = nextTotal;
            updateTotalDisplays(displayedTotal);
            return;
        }

        const startValue = displayedTotal;
        const duration = 320;
        const startTime = performance.now();

        const frame = (currentTime) => {
            const elapsed = Math.min((currentTime - startTime) / duration, 1);
            const eased = 1 - Math.pow(1 - elapsed, 3);
            displayedTotal = Math.round(startValue + ((nextTotal - startValue) * eased));
            updateTotalDisplays(displayedTotal);

            if (elapsed < 1) {
                totalAnimationFrame = requestAnimationFrame(frame);
            } else {
                displayedTotal = nextTotal;
                updateTotalDisplays(displayedTotal);
                totalAnimationFrame = null;
            }
        };

        totalAnimationFrame = requestAnimationFrame(frame);
    };

    const buildBreakdown = ({ packageBase, propertyFee, sqmExcessCost, roomBathCost, addOnsCost }) => {
        const lineItems = [
            packageBase > 0 ? { label: 'Package Base', value: packageBase } : null,
            propertyFee > 0 ? { label: 'Property Fee', value: propertyFee } : null,
            sqmExcessCost > 0 ? { label: 'Excess Area', value: sqmExcessCost } : null,
            roomBathCost > 0 ? { label: 'Room/Bath Add-ons', value: roomBathCost } : null,
            addOnsCost > 0 ? { label: 'Service Add-ons', value: addOnsCost } : null,
        ].filter(Boolean);

        const breakdownMarkup = lineItems.map((item) => `
            <li class="flex items-center justify-between gap-3">
                <span>${item.label}</span>
                <span class="font-semibold text-slate-900">${formatPeso(item.value)}</span>
            </li>
        `).join('');
        const formulaCopy = lineItems.length
            ? `Calculation: ${lineItems.map((item) => `${formatPeso(item.value)} ${item.label.toLowerCase()}`).join(' + ')}`
            : 'Calculation: no billable items selected yet.';

        if (breakdownListElement) {
            breakdownListElement.innerHTML = breakdownMarkup;
        }

        if (mobileBreakdownListElement) {
            mobileBreakdownListElement.innerHTML = breakdownMarkup;
        }

        if (formulaLineElement) {
            formulaLineElement.textContent = formulaCopy;
        }

        if (mobileFormulaLineElement) {
            mobileFormulaLineElement.textContent = formulaCopy;
        }
    };

    const calculateInstantQuote = ({ animateTotal = false } = {}) => {
        const selectedPackage = getSelectedPackage();
        const selectedProperty = propertyMap[propertySelect.value] ?? propertyMap.house;
        const floorArea = getFloorArea();
        const bedrooms = Math.max(1, Number.parseInt(roomsSelect.value, 10) || 1);
        const bathrooms = Math.max(1, Number.parseInt(bathroomsSelect.value, 10) || 1);
        const selectedAddOns = addOnInputs.filter((input) => input.checked).map((input) => input.value);

        const packageBase = Number(selectedPackage?.base ?? 0);
        const propertyFee = Number(selectedProperty?.fee ?? 0);
        const areaRate = Number(selectedPackage?.area_rate ?? 0);

        const excessSqm = Math.max(0, floorArea - includedArea);
        const sqmExcessCost = excessSqm * areaRate;

        const extraBedroomCount = Math.max(0, bedrooms - 1);
        const extraBathroomCount = Math.max(0, bathrooms - 1);
        const roomBathCost = (extraBedroomCount * extraBedroomRate) + (extraBathroomCount * extraBathroomRate);
        const addOnsCost = selectedAddOns.reduce((total, key) => total + Number(addOnCatalog[key]?.price ?? 0), 0);

        const totalEstimate = packageBase + propertyFee + sqmExcessCost + roomBathCost + addOnsCost;

        floorAreaSlider.value = String(floorArea);
        floorAreaValue.textContent = `${floorArea} sqm`;
        animateTotalTo(totalEstimate, animateTotal);
        buildBreakdown({ packageBase, propertyFee, sqmExcessCost, roomBathCost, addOnsCost });

        const prefillParams = new URLSearchParams({
            service_type: selectedPackage?.slug ?? 'basic',
            property_type: selectedProperty?.key ?? 'house',
            floor_area: String(floorArea),
            rooms: String(bedrooms),
            bathrooms: String(bathrooms),
            add_ons: selectedAddOns.join(','),
            estimated_total: String(totalEstimate),
            quote_source: 'landing_instant_quote',
        });

        const quoteHref = `${checkoutBaseUrl}?${prefillParams.toString()}`;
        bookButton.href = quoteHref;

        if (fabButton) {
            fabButton.href = quoteHref;
        }
    };

    packageInputs.forEach((input) => input.addEventListener('change', () => {
        const selectedPackage = getSelectedPackage();

        if (selectedPackage?.slug === 'deep' && lastSelectedPackageSlug !== 'deep' && ecoAddOnInput) {
            ecoAddOnInput.checked = true;
        }

        lastSelectedPackageSlug = selectedPackage?.slug ?? lastSelectedPackageSlug;
        calculateInstantQuote();
    }));
    propertySelect.addEventListener('change', () => calculateInstantQuote());
    roomsSelect.addEventListener('change', () => calculateInstantQuote());
    bathroomsSelect.addEventListener('change', () => calculateInstantQuote());
    floorAreaSlider.addEventListener('input', () => calculateInstantQuote());
    addOnInputs.forEach((input) => input.addEventListener('change', () => calculateInstantQuote({ animateTotal: true })));

    calculateInstantQuote();
})();
</script>
@endpush
@endsection

