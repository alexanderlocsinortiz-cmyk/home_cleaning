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

    $instantQuoteDefinitions = [
        ['slugs' => ['basic', 'basic-clean'], 'label' => 'Basic'],
        ['slugs' => ['deep'], 'label' => 'Deep'],
        ['slugs' => ['moveinout'], 'label' => 'Move-in'],
        ['slugs' => ['postconstruction'], 'label' => 'Post-Con'],
    ];
    $instantQuotePackages = collect($instantQuoteDefinitions)
        ->map(function (array $definition) use ($services): ?array {
            $service = collect($definition['slugs'])
                ->map(fn (string $slug) => $services->firstWhere('slug', $slug))
                ->filter()
                ->first();

            if (! $service || ! \App\Models\Service::usesPerSquareMeterPricing($service->slug)) {
                return null;
            }

            return [
                'slug' => $service->slug,
                'label' => $definition['label'],
                'base' => 0,
                'area_rate' => (float) $service->price,
                'pricing_unit' => 'sqm',
            ];
        })
        ->filter()
        ->values()
        ->all();

    $instantQuotePropertyOptions = [
        ['key' => 'house', 'label' => 'House', 'fee' => 0],
        ['key' => 'apartment', 'label' => 'Apartment', 'fee' => 0],
        ['key' => 'boarding_house', 'label' => 'Boarding House', 'fee' => 0],
    ];
    $defaultInstantQuoteFloorArea = 30;
    $defaultInstantQuoteTotal = (int) (($instantQuotePackages[0]['base'] ?? 0) + (($instantQuotePackages[0]['area_rate'] ?? 0) * $defaultInstantQuoteFloorArea) + ($instantQuotePropertyOptions[0]['fee'] ?? 0));

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
        'yard_sweeping' => 'fa-broom',
    ];
    $showEarlyLaunchBanner = (bool) config('cleanflow.marketing.show_early_launch_banner', false);
    $businessStartYear = (int) config('cleanflow.marketing.business_start_year', 2024);

    $heroBenefits = [
        [
            'icon' => 'fa-id-card',
            'title' => 'Trusted & Reliable',
            'text' => 'Every cleaner passes an NBI background check before being assigned to a client home.',
        ],
        [
            'icon' => 'fa-calendar-check',
            'title' => 'High Quality Cleaning',
            'text' => 'You stay informed from schedule confirmation to service completion.',
        ],
        [
            'icon' => 'fa-camera',
            'title' => 'On-Time Service',
            'text' => 'Completed visits can include before-and-after photos for extra peace of mind.',
        ],
    ];

    $heroTrustPoints = [
        'Instant quote before you commit',
        'NBI-cleared cleaners',
        'Same-day slots when availability opens',
        'One-time or recurring cleaning plans',
    ];

    $workflowSteps = [
        [
            'title' => 'Get an instant quote',
            'desc' => 'Choose the service, home type, and add-ons. See your estimate before you commit.',
            'icon' => 'fa-receipt',
            'classes' => 'bg-blue-100 text-blue-600',
        ],
        [
            'title' => 'Pick your schedule',
            'desc' => 'Choose the date and time that fit your week, then confirm the details for your home.',
            'icon' => 'fa-calendar-check',
            'classes' => 'bg-blue-100 text-blue-600',
        ],
        [
            'title' => 'Relax while we clean',
            'desc' => 'Your NBI-cleared cleaner arrives, completes the job, and sends photo proof when done.',
            'icon' => 'fa-circle-check',
            'classes' => 'bg-blue-100 text-blue-600',
        ],
    ];

    $faqs = [
        [
            'question' => 'Which areas do you currently serve?',
            'answer' => 'We serve all ' . $stats['barangays'] . ' barangays in Valencia City.',
        ],
        [
            'question' => 'How is pricing computed?',
            'answer' => 'Each package has a starting rate. Your final total depends on your property type, floor area, and any add-ons you choose.',
        ],
        [
            'question' => 'Which payment methods are available?',
            'answer' => 'You can pay with cash on cleaning day, GCash, or Maya.',
        ],
        [
            'question' => 'Can I request yard sweeping?',
            'answer' => 'Yes. You can add yard sweeping when you book if you want accessible outdoor areas included.',
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
    <section class="home-hero-shell relative overflow-hidden">
        <img
            class="home-hero-bg"
            src="{{ asset('images/landing-cleaning-hero.png') }}?v=20260510"
            alt=""
            aria-hidden="true"
        >
        <div class="home-hero-bg-overlay" aria-hidden="true"></div>
        <div class="hero-section container-pad relative z-10 mx-auto grid max-w-7xl items-center gap-12 px-6 pt-16 pb-16 lg:min-h-[760px] lg:grid-cols-[minmax(0,0.86fr)_minmax(480px,1.14fr)] lg:gap-16 lg:pt-24 lg:pb-24">
            <div class="max-w-xl space-y-6 reveal-on-scroll">
                @if($showEarlyLaunchBanner)
                <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-white/80 px-4 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-blue-700 shadow-sm">
                    <i class="fas fa-bullhorn"></i>
                    Early launch in progress: limited daily slots may open first to reviewed bookings
                </div>
                @endif
                <span class="inline-flex items-center gap-2 text-sm font-extrabold uppercase tracking-wide text-blue-600">
                    <i class="fas fa-sparkles"></i>
                    A clean home, a happy home
                </span>
                <h1 class="max-w-xl text-5xl font-black leading-[1.04] text-slate-950 sm:text-6xl lg:text-7xl">
                    Professional <span class="text-blue-600">Home Cleaning</span> Services
                </h1>
                <p class="max-w-lg text-base leading-8 text-slate-600 lg:text-lg">
                    We provide top-quality cleaning services to make your home spotless, fresh, and comfortable.
                </p>
                <p class="sr-only">Trusted home cleaning for Valencia City.</p>
                <div class="hero-buttons flex flex-wrap items-center gap-3">
                    <a href="{{ $primaryCtaUrl }}" class="sales-primary-button inline-flex items-center justify-center gap-2 rounded-full px-7 py-3.5 text-base font-bold text-white transition hover:-translate-y-0.5">
                        <i class="fas fa-sparkles text-sm"></i>
                        <span>Book a Cleaning</span>
                    </a>
                    <a href="{{ $secondaryCtaUrl }}" class="inline-flex items-center justify-center gap-2 rounded-full border border-blue-100 bg-white/80 px-6 py-3 font-semibold text-blue-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-white">
                        <i class="fas fa-tags"></i>
                        <span>{{ $secondaryCtaLabel }}</span>
                    </a>
                </div>
                <p class="text-sm font-medium text-slate-500">
                    See your price first. Book only when you're ready.
                </p>
                <div class="hidden">
                    @foreach($heroTrustPoints as $point)
                    <div class="home-trust-pill inline-flex items-center gap-2 rounded-full px-3 py-2">
                        <i class="fas fa-check-circle text-white"></i>
                        <span>{{ $point }}</span>
                    </div>
                    @endforeach
                </div>
                <div class="mt-10 grid max-w-xl grid-cols-3 gap-5">
                    @foreach(array_slice($heroBenefits, 0, 3) as $benefit)
                    <div class="min-w-0">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-blue-600 shadow-sm ring-1 ring-blue-100">
                            <i class="fas {{ $benefit['icon'] }}"></i>
                        </div>
                        <div class="mt-3 text-sm font-bold leading-5 text-slate-900">{{ $benefit['title'] }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="home-hero-slider reveal-on-scroll" data-advertising-slider>
                <div class="relative aspect-[0.98] min-h-[430px] sm:aspect-[1.18] lg:min-h-[520px]">
                    <article class="home-hero-slide absolute inset-0 grid" data-ad-slide aria-hidden="false">
                        <img src="{{ asset('images/services/ChatGPT Image Sep 4, 2026, 02_14_05 AM.png') }}" alt="A professional cleaner working in a bright home" class="home-hero-slide__image">
                        <div class="home-hero-slide__veil" aria-hidden="true"></div>
                        <div class="home-hero-slide__content">
                            <span class="home-hero-slide__eyebrow">General cleaning</span>
                            <h2 class="home-hero-slide__title">A Cleaner Home,<br>Less Stress</h2>
                            <p class="home-hero-slide__copy">Professional home cleaning you can schedule in just a few clicks.</p>
                            <a href="{{ $primaryCtaUrl }}" class="home-hero-slide__cta">Book a Cleaning <i class="fas fa-arrow-right text-xs"></i></a>
                        </div>
                    </article>
                    <article class="home-hero-slide absolute inset-0 hidden" data-ad-slide aria-hidden="true">
                        <img src="{{ asset('images/services/deep.jpg') }}" alt="Deep cleaning service" class="home-hero-slide__image">
                        <div class="home-hero-slide__veil" aria-hidden="true"></div>
                        <div class="home-hero-slide__content">
                            <span class="home-hero-slide__eyebrow">Deep cleaning</span>
                            <h2 class="home-hero-slide__title">Give Every Corner<br>A Fresh Start</h2>
                            <p class="home-hero-slide__copy">Choose a deeper clean for kitchens, bathrooms, and the spaces that need extra care.</p>
                            <a href="{{ $primaryCtaUrl }}" class="home-hero-slide__cta">Book a Deep Clean <i class="fas fa-arrow-right text-xs"></i></a>
                        </div>
                    </article>
                    <article class="home-hero-slide absolute inset-0 hidden" data-ad-slide aria-hidden="true">
                        <img src="{{ asset('images/services/weeklymaintenance.jpg') }}" alt="Regular maintenance cleaning" class="home-hero-slide__image">
                        <div class="home-hero-slide__veil" aria-hidden="true"></div>
                        <div class="home-hero-slide__content">
                            <span class="home-hero-slide__eyebrow">Flexible scheduling</span>
                            <h2 class="home-hero-slide__title">A Fresh Home,<br>Every Week</h2>
                            <p class="home-hero-slide__copy">Keep your home comfortable with weekly, bi-weekly, or monthly cleaning plans.</p>
                            <a href="{{ $primaryCtaUrl }}" class="home-hero-slide__cta">Choose a Schedule <i class="fas fa-arrow-right text-xs"></i></a>
                        </div>
                    </article>
                </div>
                <button type="button" class="home-hero-slider__arrow home-hero-slider__arrow--prev" data-ad-prev aria-label="Previous announcement"><i class="fas fa-chevron-left text-xs"></i></button>
                <button type="button" class="home-hero-slider__arrow home-hero-slider__arrow--next" data-ad-next aria-label="Next announcement"><i class="fas fa-chevron-right text-xs"></i></button>
                <div class="home-hero-slider__dots" role="tablist" aria-label="Choose announcement">
                    <button type="button" class="home-hero-slider__dot is-active" data-ad-dot aria-label="Show announcement 1" aria-selected="true"></button>
                    <button type="button" class="home-hero-slider__dot" data-ad-dot aria-label="Show announcement 2" aria-selected="false"></button>
                    <button type="button" class="home-hero-slider__dot" data-ad-dot aria-label="Show announcement 3" aria-selected="false"></button>
                </div>
            </div>
        </div>
    </section>

    <section id="services" class="section-padding bg-white py-20">
        <div class="container-pad mx-auto max-w-7xl px-6">
            <div id="pricing" class="scroll-mt-28"></div>
            <div class="section-heading mx-auto mb-12 max-w-3xl text-center reveal-on-scroll">
                <div class="text-sm font-extrabold uppercase tracking-[0.18em] text-blue-600">Our services</div>
                <h2 class="section-title mt-3 text-3xl font-bold text-slate-900 lg:text-5xl">Choose the clean that fits your home</h2>
                <p class="section-subtitle mt-4 text-lg leading-8 text-slate-500">
                    Explore a service, then open its details to see what is included before you book.
                </p>
            </div>
            <div class="services-grid grid grid-cols-1 gap-8 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($services as $service)
                @php
                    $ratingStats = $serviceRatingStats[$service->id] ?? null;
                    $ratingCount = (int) ($ratingStats->total ?? 0);
                    $ratingAverage = $ratingCount > 0 ? (float) $ratingStats->average : null;
                    $priceLabel = \App\Models\Service::usesPerSquareMeterPricing($service->slug)
                        ? '&#8369;' . number_format($service->price, 0) . ' per sqm'
                        : (
                            \App\Models\Service::usesFlatRateRangePricing($service->slug) && ($range = \App\Models\Service::priceRangeForSlug($service->slug))
                                ? '&#8369;' . number_format($range['min'], 0) . ' - &#8369;' . number_format($range['max'], 0)
                                : 'Starts at &#8369;' . number_format($service->price, 0)
                        );
                @endphp
                <a href="{{ route('services.show', $service) }}" aria-label="View details for {{ $service->name }}" class="home-service-card group block focus:outline-none">
                    <div class="home-service-media relative overflow-hidden rounded-2xl bg-slate-100 shadow-sm ring-1 ring-slate-200 transition duration-300 group-hover:-translate-y-1 group-hover:shadow-xl group-focus:ring-4 group-focus:ring-blue-100">
                        <img src="{{ $service->image_url }}" alt="{{ $service->image_alt }}" loading="lazy" class="home-service-image transition duration-500 group-hover:scale-105">
                    </div>
                    <div class="mt-4 flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <h3 class="truncate text-base font-bold text-slate-950">{{ $service->name }}</h3>
                            <div class="mt-1 text-sm font-semibold text-slate-900">{!! $priceLabel !!}</div>
                        </div>
                        <div class="shrink-0 text-right text-sm font-semibold text-slate-700">
                            @if($ratingAverage !== null)
                                <span class="inline-flex items-center gap-1">
                                    <i class="fas fa-star text-amber-400"></i>
                                    {{ number_format($ratingAverage, 1) }}
                                </span>
                                <div class="mt-1 text-xs font-medium text-slate-500">
                                    {{ $ratingCount }} review{{ $ratingCount === 1 ? '' : 's' }}
                                </div>
                            @else
                                <span class="text-xs font-semibold text-slate-400">No reviews yet</span>
                            @endif
                        </div>
                    </div>
                    <span class="sr-only">Open details for {{ $service->name }}</span>
                </a>
                @endforeach
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
                        <div class="workflow-arrow absolute -right-6 top-1/2 z-10 hidden h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full border border-blue-100 bg-white text-blue-600 shadow-sm xl:flex">
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

    <section class="reviews-section bg-slate-50 py-16">
        <div class="container-pad mx-auto max-w-7xl px-6">
            <div class="mb-10 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div class="max-w-2xl reveal-on-scroll">
                    <div class="text-sm font-extrabold uppercase tracking-wide text-blue-600">Reviews</div>
                    <h2 class="mt-3 text-3xl font-black tracking-tight text-slate-950 md:text-4xl">What clients say after the clean</h2>
                    <p class="mt-3 text-base leading-7 text-slate-500">Recent feedback from completed bookings keeps the service honest.</p>
                </div>
                <div class="rounded-2xl border border-blue-100 bg-white px-5 py-4 text-left shadow-sm reveal-on-scroll md:text-right">
                    <div class="text-3xl font-black text-blue-700">
                        {{ !empty($reviewStats['average']) ? number_format($reviewStats['average'], 1) : '-' }}
                    </div>
                    <div class="mt-1 text-sm font-semibold text-slate-500">
                        {{ (int) ($reviewStats['count'] ?? 0) }} submitted review{{ (int) ($reviewStats['count'] ?? 0) === 1 ? '' : 's' }}
                    </div>
                </div>
            </div>

            @if($landingReviews->count())
                <div class="testimonials-grid grid gap-6 md:grid-cols-3">
                    @foreach($landingReviews as $review)
                        @php
                            $clientFirst = $review->client?->first_name;
                            $clientLastInitial = $review->client?->last_name ? mb_substr($review->client->last_name, 0, 1) . '.' : '';
                            $clientName = $clientFirst ? trim($clientFirst . ' ' . $clientLastInitial) : 'Verified client';
                            $serviceName = $review->booking?->service?->name ?? 'Home cleaning';
                        @endphp
                        <article class="testimonial-card rounded-3xl border border-blue-100 bg-white p-6 shadow-sm reveal-on-scroll">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex gap-1 text-blue-500">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fas fa-star {{ $i <= $review->stars ? '' : 'text-slate-200' }}"></i>
                                    @endfor
                                </div>
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ $review->stars }}/5</span>
                            </div>
                            <p class="mt-5 text-base leading-8 text-slate-600">"{{ $review->comment }}"</p>
                            <div class="mt-6 flex items-center gap-3 border-t border-slate-100 pt-5">
                                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-blue-100 text-sm font-black text-blue-700">
                                    {{ mb_substr($clientName, 0, 1) }}
                                </div>
                                <div>
                                    <div class="font-bold text-slate-900">{{ $clientName }}</div>
                                    <div class="text-sm text-slate-500">{{ $serviceName }}</div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="rounded-3xl border border-blue-100 bg-white p-8 text-center shadow-sm reveal-on-scroll">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3 class="mt-4 text-xl font-black text-slate-950">No public reviews yet</h3>
                    <p class="mx-auto mt-2 max-w-xl text-sm leading-7 text-slate-500">
                        Customer reviews will appear here after completed bookings receive written feedback.
                    </p>
                </div>
            @endif
        </div>
    </section>

    <section id="services-pricing-legacy" class="hidden section-padding bg-white py-20" aria-hidden="true">
        <div class="container-pad mx-auto max-w-7xl px-6">
            <div id="pricing-legacy" class="scroll-mt-28"></div>
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
                    $scope = $service->scopeSummary();
                    $scopeDefinition = $service->scopeDefinition();
                @endphp
                <article class="service-card reveal-on-scroll flex h-full flex-col rounded-3xl border border-slate-200 p-8 shadow-sm transition duration-300 hover:-translate-y-2 hover:shadow-xl">
                    <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-slate-100">
                        <img src="{{ $service->image_url }}" alt="{{ $service->image_alt }}" loading="lazy" decoding="async" class="h-40 w-full object-cover">
                        <div class="absolute left-4 top-4 flex h-11 w-11 items-center justify-center rounded-2xl bg-white/90 text-blue-600 shadow-sm backdrop-blur">
                            <i class="fas {{ $package['icon'] ?? 'fa-broom' }} text-lg"></i>
                        </div>
                        @if(!empty($package['badge']))
                        <span class="absolute right-3 top-3 rounded-full bg-white/90 px-4 py-2 text-[11px] font-semibold uppercase tracking-wide text-blue-700 shadow-sm backdrop-blur">
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
                            <i class="fas fa-check-circle mt-1 text-blue-500"></i>
                            <span>{{ $feature }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                    <div class="mt-5 text-base font-semibold text-blue-600">
                        @if(\App\Models\Service::usesPerSquareMeterPricing($service->slug))
                            &#8369;{{ number_format($service->price, 0) }} per sqm
                        @elseif(\App\Models\Service::usesFlatRateRangePricing($service->slug) && ($range = \App\Models\Service::priceRangeForSlug($service->slug)))
                            &#8369;{{ number_format($range['min'], 0) }} - &#8369;{{ number_format($range['max'], 0) }} flat rate
                        @else
                            Starting at &#8369;{{ number_format($service->price, 0) }}
                        @endif
                    </div>
                    <div class="mt-2 text-xs font-semibold text-slate-500">
                        Up to {{ $scope['max_floor_area'] ? number_format($scope['max_floor_area']) . ' sqm' : 'manual quote' }}
                        · {{ $scope['cleaner_count'] }} cleaner{{ $scope['cleaner_count'] === 1 ? '' : 's' }}
                        · {{ $service->duration_minutes ?: \App\Models\Service::durationForSlug($service->slug) }} minutes
                    </div>
                    <div class="service-note mt-5 rounded-2xl bg-slate-50 px-4 py-4 text-sm leading-6 text-slate-600">
                        {{ $package['highlight'] ?? 'This package can be requested directly through our Valencia City cleaning team.' }}
                    </div>
                    <div class="mt-3 rounded-2xl border {{ $scope['status'] === 'approved' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800' }} px-4 py-3 text-xs leading-5">
                        <span class="font-extrabold">{{ $scope['status'] === 'approved' ? 'Approved scope basis.' : 'Provisional package scope.' }}</span>
                        {{ $scope['status'] === 'approved' ? ' Measurable limits apply.' : ' Features are a planning baseline; condition, access, equipment, and booked time can change the final scope.' }}
                    </div>
                    @if($service->scopeDefinitionIsComplete())
                    <details class="mt-4 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left">
                        <summary class="cursor-pointer text-xs font-extrabold text-slate-700">View package boundaries</summary>
                        <div class="mt-3 space-y-2 text-xs leading-5 text-slate-600">
                            <div><span class="font-bold text-slate-800">Included:</span> {{ $scopeDefinition['included_tasks'] }}</div>
                            <div><span class="font-bold text-slate-800">Excluded:</span> {{ $scopeDefinition['excluded_tasks'] }}</div>
                            <div><span class="font-bold text-slate-800">Limits:</span> {{ $scopeDefinition['condition_limits'] }}</div>
                        </div>
                    </details>
                    @endif
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
                <div class="relative overflow-hidden rounded-4xl border border-blue-100 bg-white/90 p-6 shadow-[0_20px_50px_rgba(15,23,42,0.14)] backdrop-blur-xl lg:p-8">
                    <div class="absolute inset-x-0 top-0 h-1.5 bg-blue-600"></div>
                    <div class="grid gap-6 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
                        <div>
                            <div class="inline-flex items-center gap-2 rounded-full border border-blue-200/70 bg-white/70 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-blue-700">
                                <i class="fas fa-bolt"></i>
                                Instant Quote
                            </div>
                            <h3 class="mt-4 text-2xl font-extrabold text-slate-900 sm:text-3xl">Estimate your cleaning total in seconds</h3>
                            <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">
                                Set your package, property type, floor area, and add-ons. The estimate updates live so you can compare options without losing the total.
                            </p>

                            <div class="mt-6 space-y-5">
                                <div>
                                    <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.14em] text-slate-600">Choose Package</label>
                                    <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
                                        @foreach($instantQuotePackages as $package)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="iq_package" value="{{ $package['slug'] }}" class="peer sr-only" {{ $loop->first ? 'checked' : '' }}>
                                            <span class="block rounded-2xl border border-slate-200 bg-white/80 px-3 py-3 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md peer-checked:border-blue-400 peer-checked:bg-blue-50/80">
                                                <span class="block text-sm font-bold text-slate-900">{{ $package['label'] }}</span>
                                                <span class="mt-0.5 block text-xs text-slate-500">
                                                    @if(($package['pricing_unit'] ?? null) === 'sqm')
                                                    Per square meter
                                                    @elseif(($package['pricing_unit'] ?? null) === 'flat_range')
                                                    Flat rate
                                                    @else
                                                    &#8369;{{ number_format($package['base'], 0) }} base
                                                    @endif
                                                </span>
                                                <span class="mt-1 block text-xs font-semibold text-blue-700">
                                                    @if(($package['pricing_unit'] ?? null) === 'sqm')
                                                    &#8369;{{ number_format($package['area_rate'], 0) }}/sqm
                                                    @elseif(($package['pricing_unit'] ?? null) === 'flat_range')
                                                    &#8369;{{ number_format($package['base'], 0) }} - &#8369;{{ number_format($package['max_base'], 0) }}
                                                    @elseif($package['area_rate'] > 0)
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
                                    <p class="mb-3 text-xs text-slate-500">Tap to include extras and instantly update your quote. Each is charged once per booking.</p>
                                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                        @foreach($pricingAddOns as $key => $addOn)
                                        <label class="cursor-pointer">
                                            <input type="checkbox" name="iq_add_ons[]" value="{{ $key }}" class="peer sr-only">
                                            <span class="flex h-full items-start gap-3 rounded-xl border border-slate-200 bg-white px-3 py-3 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-md peer-checked:border-blue-500 peer-checked:bg-blue-50">
                                                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 peer-checked:bg-blue-100 peer-checked:text-blue-700">
                                                    <i class="fas {{ $instantQuoteAddOnIcons[$key] ?? 'fa-sparkles' }}"></i>
                                                </span>
                                                <span class="min-w-0">
                                                    <span class="block text-sm font-semibold text-slate-900">{{ $addOn['label'] }}</span>
                                                    <span class="mt-0.5 block text-xs text-blue-700">+&#8369;{{ number_format($addOn['price'], 0) }} <span class="text-slate-500">per booking</span></span>
                                                </span>
                                            </span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <aside class="instant-quote-summary hidden rounded-3xl border border-white/70 bg-white/78 p-5 shadow-lg backdrop-blur-md lg:block sm:p-6">
                            <div class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-700">Total Estimate</div>
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
                            <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-blue-700">Total Estimate</div>
                            <div id="iq_mobile_total" class="mt-2 truncate text-2xl font-extrabold tracking-tight text-slate-900">&#8369;{{ number_format($defaultInstantQuoteTotal, 0) }}</div>
                            <div class="mt-1 text-[11px] text-slate-500">Live estimate while you compare add-ons and home size.</div>
                        </div>
                        <div class="rounded-full bg-blue-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-blue-700">Sticky</div>
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

    <section class="home-cta-shell cta-section py-14 text-white md:py-16">
        <div class="container-pad mx-auto max-w-3xl px-6 text-center reveal-on-scroll">
            <h2 class="home-cta-title text-3xl font-extrabold lg:text-4xl">See your price. Book only when you're ready.</h2>
            <p class="home-cta-copy mx-auto mt-4 max-w-2xl text-base leading-7">
                NBI-cleared cleaners. Upfront pricing. No commitment until you confirm.
            </p>
            <div class="cta-buttons mt-7 flex flex-wrap justify-center gap-4">
                <a href="{{ $primaryCtaUrl }}" class="sales-primary-button rounded-2xl px-7 py-3 font-semibold text-white transition hover:-translate-y-0.5">{{ $primaryCtaLabel }}</a>
            </div>
            <div class="cta-note mt-6 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm text-white/80">
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

    const packageInputs = Array.from(document.querySelectorAll('input[name="iq_package"]'));
    const propertySelect = document.getElementById('iq_property');
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

    if (!packageInputs.length || !propertySelect || !floorAreaSlider || !floorAreaValue || !mobileTotalElement || !mobileBreakdownListElement || !mobileFormulaLineElement || !fabButton || !bookButton) {
        return;
    }

    const formatPeso = (amount) => `\u20B1${Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
    let displayedTotal = 0;
    let totalAnimationFrame = null;

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

    const buildBreakdown = ({ packageBase, propertyFee, sqmExcessCost, addOnsCost, areaLabel = 'Excess Area' }) => {
        const lineItems = [
            packageBase > 0 ? { label: 'Package Base', value: packageBase } : null,
            propertyFee > 0 ? { label: 'Property Fee', value: propertyFee } : null,
            sqmExcessCost > 0 ? { label: areaLabel, value: sqmExcessCost } : null,
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
        const selectedAddOns = addOnInputs.filter((input) => input.checked).map((input) => input.value);

        const packageBase = Number(selectedPackage?.base ?? 0);
        const propertyFee = Number(selectedProperty?.fee ?? 0);
        const areaRate = Number(selectedPackage?.area_rate ?? 0);

        const excessSqm = selectedPackage?.pricing_unit === 'flat_range'
            ? 0
            : selectedPackage?.pricing_unit === 'sqm' ? floorArea : Math.max(0, floorArea - includedArea);
        const sqmExcessCost = excessSqm * areaRate;

        const addOnsCost = selectedAddOns.reduce((total, key) => total + Number(addOnCatalog[key]?.price ?? 0), 0);

        const appliedPropertyFee = selectedPackage?.pricing_unit === 'flat_range' ? 0 : propertyFee;
        const totalEstimate = packageBase + appliedPropertyFee + sqmExcessCost + addOnsCost;

        floorAreaSlider.value = String(floorArea);
        floorAreaValue.textContent = `${floorArea} sqm`;
        animateTotalTo(totalEstimate, animateTotal);
        const areaLabel = selectedPackage?.pricing_unit === 'sqm' ? 'Floor Area' : 'Excess Area';
        buildBreakdown({ packageBase, propertyFee: appliedPropertyFee, sqmExcessCost, addOnsCost, areaLabel });

        const prefillParams = new URLSearchParams({
            service_type: selectedPackage?.slug ?? 'basic',
            property_type: selectedProperty?.key ?? 'house',
            floor_area: String(floorArea),
            rooms: '1',
            bathrooms: '1',
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

    packageInputs.forEach((input) => input.addEventListener('change', () => calculateInstantQuote()));
    propertySelect.addEventListener('change', () => calculateInstantQuote());
    floorAreaSlider.addEventListener('input', () => calculateInstantQuote());
    addOnInputs.forEach((input) => input.addEventListener('change', () => calculateInstantQuote({ animateTotal: true })));

    calculateInstantQuote();
})();
</script>
<script>
(() => {
    const slider = document.querySelector('[data-advertising-slider]');
    if (!slider) return;

    const slides = Array.from(slider.querySelectorAll('[data-ad-slide]'));
    const dots = Array.from(slider.querySelectorAll('[data-ad-dot]'));
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let current = 0;
    let timer;

    const show = (index) => {
        current = (index + slides.length) % slides.length;
        slides.forEach((slide, slideIndex) => {
            const active = slideIndex === current;
            slide.classList.toggle('hidden', !active);
            slide.classList.toggle('grid', active);
            slide.setAttribute('aria-hidden', active ? 'false' : 'true');
        });
        dots.forEach((dot, dotIndex) => {
            const active = dotIndex === current;
            dot.classList.toggle('is-active', active);
            dot.classList.toggle('w-7', active);
            dot.classList.toggle('w-2.5', !active);
            dot.classList.toggle('bg-white', active);
            dot.classList.toggle('bg-white/40', !active);
            dot.setAttribute('aria-selected', active ? 'true' : 'false');
        });
    };

    const start = () => {
        if (!reducedMotion) timer = window.setInterval(() => show(current + 1), 6500);
    };
    const restart = () => { window.clearInterval(timer); start(); };

    slider.querySelector('[data-ad-prev]')?.addEventListener('click', () => { show(current - 1); restart(); });
    slider.querySelector('[data-ad-next]')?.addEventListener('click', () => { show(current + 1); restart(); });
    dots.forEach((dot, index) => dot.addEventListener('click', () => { show(index); restart(); }));
    slider.addEventListener('mouseenter', () => window.clearInterval(timer));
    slider.addEventListener('mouseleave', restart);
    slider.addEventListener('focusin', () => window.clearInterval(timer));
    slider.addEventListener('focusout', (event) => { if (!slider.contains(event.relatedTarget)) restart(); });
    show(0);
    start();
})();
</script>
@endpush
@endsection
