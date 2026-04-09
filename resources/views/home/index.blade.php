@extends('layouts.app')
@section('title', 'Home Cleaning Service - Professional Cleaning in Valencia City')

@section('content')
@php
    $serviceIcons = [
        'basic' => 'fa-broom',
        'deep' => 'fa-soap',
        'moveinout' => 'fa-truck-moving',
    ];

    $serviceHighlights = [
        'basic' => 'A practical choice for regular home upkeep and routine cleaning support.',
        'deep' => 'Best for detailed cleaning, tougher buildup, and harder-to-reach areas.',
        'moveinout' => 'Ideal for move preparation, turnover cleaning, and full-space reset work.',
    ];

    $isAuthenticated = auth()->check();
    $userRole = $isAuthenticated ? auth()->user()->role : null;

    $primaryCtaUrl = match ($userRole) {
        'client' => route('bookings.create'),
        'staff', 'admin' => route($userRole . '.dashboard'),
        default => route('register'),
    };

    $primaryCtaLabel = 'Book Now';
    $secondaryCtaUrl = '#pricing';
    $secondaryCtaLabel = 'See Pricing';

    $finalSecondaryUrl = $isAuthenticated
        ? route($userRole . '.dashboard')
        : route('login');

    $finalSecondaryLabel = $isAuthenticated
        ? 'Dashboard'
        : 'Login';

    $serviceCardUrl = $isAuthenticated && $userRole === 'client'
        ? route('bookings.create')
        : route('register');

    $serviceCardLabel = 'Book This Service';

    $heroBenefits = [
        [
            'icon' => 'fa-shield-halved',
            'title' => 'Trusted & Vetted Staff',
            'text' => 'Every cleaner on our platform is reviewed and assigned by our operations team.',
        ],
        [
            'icon' => 'fa-calendar-check',
            'title' => 'Easy Online Booking',
            'text' => 'Pick your service, set your schedule, and submit your request in one simple form.',
        ],
        [
            'icon' => 'fa-star',
            'title' => 'Rated by Real Clients',
            'text' => 'We collect feedback after every completed job to keep our service quality high.',
        ],
    ];

    $heroTrustPoints = [
        'Transparent starting rates',
        'Valencia City service-area coverage',
        'Support from a managed operations team',
    ];

    $keyStats = [
        [
            'value' => number_format($stats['barangays']),
            'label' => 'Areas Covered',
            'accent' => 'text-emerald-600',
        ],
        [
            'value' => number_format($stats['customers']),
            'label' => 'Happy Clients',
            'accent' => 'text-blue-600',
        ],
        [
            'value' => number_format($stats['staff']),
            'label' => 'Professional Staff',
            'accent' => 'text-amber-500',
        ],
        [
            'value' => number_format($stats['completed_bookings']),
            'label' => 'Jobs Completed',
            'accent' => 'text-purple-600',
        ],
    ];

    $workflowSteps = [
        [
            'title' => 'Create account',
            'desc' => 'Sign up to access the booking form, manage requests, and review your service details in one place.',
            'icon' => 'fa-user-plus',
            'classes' => 'bg-blue-100 text-blue-600',
        ],
        [
            'title' => 'Submit booking',
            'desc' => 'Choose a package, set your preferred schedule, and send your request through the online booking form.',
            'icon' => 'fa-calendar-check',
            'classes' => 'bg-emerald-100 text-emerald-600',
        ],
        [
            'title' => 'Admin assigns staff',
            'desc' => 'Our operations team reviews the request, confirms details, and assigns the right staff member.',
            'icon' => 'fa-user-gear',
            'classes' => 'bg-amber-100 text-amber-600',
        ],
        [
            'title' => 'Staff completes service',
            'desc' => 'Your cleaner arrives as scheduled, completes the service, and the finished job can be rated afterward.',
            'icon' => 'fa-circle-check',
            'classes' => 'bg-purple-100 text-purple-600',
        ],
    ];

    $testimonials = [
        [
            'quote' => 'The team was on time and thorough. My house has never been this clean!',
            'name' => 'Maria S., Valencia City',
        ],
        [
            'quote' => 'Booking was easy and the staff was professional. Highly recommended.',
            'name' => 'Joel R., Valencia City',
        ],
        [
            'quote' => 'Great service for the price. Will definitely book again next month.',
            'name' => 'Anna L., Valencia City',
        ],
    ];

    $faqs = [
        [
            'question' => 'Which areas do you currently serve?',
            'answer' => 'Home Cleaning Service currently covers all ' . $stats['barangays'] . ' barangays of Valencia City based on the configured service-area setup.',
        ],
        [
            'question' => 'How is pricing computed?',
            'answer' => 'Starting rates are shown for each package, while the final amount is calculated during booking based on service details such as property type, rooms, and bathrooms.',
        ],
        [
            'question' => 'Can I cancel or reschedule a booking?',
            'answer' => 'Pending bookings can be managed from the client account before a staff member has been assigned to the request.',
        ],
        [
            'question' => 'What makes this platform different from a basic booking site?',
            'answer' => 'Aside from online booking, the platform also supports staff assignment, booking workflow management, reports, service-area coverage, and biometric attendance monitoring.',
        ],
        [
            'question' => 'How do I track my booking?',
            'answer' => 'Once your booking is confirmed and a staff member is assigned, you can track the status directly from your client dashboard.',
        ],
        [
            'question' => 'Is there a cancellation fee?',
            'answer' => 'Pending bookings can be cancelled from your account at no charge before a staff member has been assigned.',
        ],
    ];
@endphp

<main class="bg-slate-50">
    <section class="relative overflow-hidden text-white" style="background: linear-gradient(135deg, #0f6e56 0%, #16946d 48%, #0891b2 100%);">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(255,255,255,0.12),transparent_32%),radial-gradient(circle_at_bottom_right,rgba(255,255,255,0.08),transparent_30%)]"></div>
        <div class="hero-section hero-grid container-pad relative z-10 mx-auto grid max-w-7xl grid-cols-1 items-start gap-8 px-6 pt-10 pb-10 lg:grid-cols-[minmax(0,1fr)_minmax(350px,430px)] lg:gap-12 lg:pt-10 lg:pb-12">
            <div class="space-y-5 text-left reveal-on-scroll">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-[11px] font-semibold uppercase tracking-wide text-emerald-50">
                    <i class="fas fa-house text-white"></i>
                    Home Cleaning Service Platform for Valencia City
                </span>
                <h1 class="max-w-2xl text-3xl font-extrabold leading-[1.08] text-white sm:text-4xl lg:text-4xl">
                    Spotless Homes, Hassle-Free Booking &mdash; Valencia City's Trusted Cleaning Service
                </h1>
                <p class="max-w-2xl text-base leading-8 text-emerald-50/90 lg:text-lg">
                    Book a professional cleaning in minutes. We handle the scheduling, staffing, and follow-through so you don't have to.
                </p>
                <div class="hero-buttons flex flex-wrap items-center gap-3">
                    <a href="{{ $primaryCtaUrl }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-6 py-3 font-semibold text-emerald-700 shadow-lg transition hover:-translate-y-0.5 hover:bg-emerald-50">
                        <i class="fas fa-broom"></i>
                        <span>{{ $primaryCtaLabel }}</span>
                    </a>
                    <a href="{{ $secondaryCtaUrl }}" class="inline-flex items-center justify-center gap-2 rounded-full border border-white/50 px-6 py-3 font-medium text-white transition hover:bg-white/10">
                        <i class="fas fa-tags"></i>
                        <span>{{ $secondaryCtaLabel }}</span>
                    </a>
                </div>
                <div class="flex max-w-2xl flex-wrap gap-2 pt-1 text-xs text-emerald-50/90 lg:text-sm">
                    @foreach($heroTrustPoints as $point)
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-3 py-2">
                        <i class="fas fa-check-circle text-white"></i>
                        <span>{{ $point }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="hero-image space-y-2 reveal-on-scroll lg:justify-self-end">
                <div class="overflow-hidden rounded-[2rem] border border-white/15 bg-white/10 p-3 shadow-2xl backdrop-blur-sm">
                    <div class="overflow-hidden rounded-2xl">
                        <img
                            src="https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=800"
                            alt="Professional home cleaning service"
                            class="hero-photo h-[190px] w-full object-cover md:h-[220px] lg:h-[190px]"
                        >
                    </div>
                </div>
                <p class="mt-1 text-center text-xs text-white/70">
                    Professional cleaning service &mdash; Valencia City
                </p>
                <div class="hero-benefits space-y-2">
                    @foreach($heroBenefits as $benefit)
                    <div class="rounded-xl border border-white/15 bg-white/10 p-3 shadow-lg backdrop-blur-sm reveal-on-scroll">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/15 text-sm text-white">
                                <i class="fas {{ $benefit['icon'] }}"></i>
                            </div>
                            <h3 class="text-sm font-bold text-white lg:text-base">{{ $benefit['title'] }}</h3>
                        </div>
                        <p class="mt-2 text-[13px] leading-5 text-emerald-50/85 lg:text-sm">{{ $benefit['text'] }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="bg-gradient-to-b from-white to-emerald-50/70 py-14">
        <div class="container-pad mx-auto max-w-7xl px-6">
            <div class="stats-grid grid grid-cols-2 gap-6 text-center md:grid-cols-4">
                @foreach($keyStats as $stat)
                <div class="rounded-3xl border border-emerald-100 bg-white p-6 shadow-sm shadow-emerald-100/50 reveal-on-scroll">
                    <div class="stat-number text-4xl font-extrabold {{ $stat['accent'] }}">{{ $stat['value'] }}</div>
                    <div class="mt-3 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ $stat['label'] }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="services" class="section-padding bg-slate-50 py-20">
        <div class="container-pad mx-auto max-w-7xl px-6">
            <div class="mx-auto mb-12 max-w-3xl text-center reveal-on-scroll">
                <h2 class="section-title text-3xl font-bold text-slate-900 lg:text-5xl">Cleaning packages designed for common home needs</h2>
                <p class="section-subtitle mt-4 text-lg leading-8 text-slate-500">
                    Choose the package that fits your home, your schedule, and the level of cleaning support you need.
                </p>
            </div>
            <div class="services-grid grid grid-cols-1 gap-8 md:grid-cols-3">
                @foreach($services as $service)
                <article class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm transition duration-300 hover:-translate-y-2 hover:shadow-xl reveal-on-scroll">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
                        <i class="fas {{ $serviceIcons[$service->slug] ?? 'fa-broom' }} text-xl"></i>
                    </div>
                    <h3 class="mt-6 text-2xl font-bold text-slate-900">{{ $service->name }}</h3>
                    <p class="mt-4 text-sm leading-7 text-slate-500">{{ $service->description }}</p>
                    <div class="mt-5 text-base font-semibold text-emerald-600">
                        Starting at &#8369;{{ number_format($service->price, 0) }}
                    </div>
                    <div class="mt-5 rounded-2xl bg-slate-50 px-4 py-4 text-sm leading-6 text-slate-600">
                        {{ $serviceHighlights[$service->slug] ?? 'This package can be requested directly through the Home Cleaning Service platform.' }}
                    </div>
                    <a href="{{ $serviceCardUrl }}" class="mt-7 inline-flex w-full items-center justify-center rounded-2xl bg-emerald-600 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        {{ $serviceCardLabel }}
                    </a>
                </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="how-it-works" class="section-padding bg-white py-20">
        <div class="container-pad mx-auto max-w-7xl px-6">
            <div class="mx-auto mb-14 max-w-3xl text-center reveal-on-scroll">
                <h2 class="section-title text-3xl font-bold text-slate-900 lg:text-5xl">How it works</h2>
                <p class="section-subtitle mt-4 text-lg leading-8 text-slate-500">
                    The process is simple for clients, while the platform keeps the scheduling and service flow organized behind the scenes.
                </p>
            </div>
            <div class="relative">
                <div class="absolute left-[12%] right-[12%] top-10 hidden h-[2px] bg-gradient-to-r from-emerald-100 via-emerald-300 to-emerald-100 xl:block"></div>
                <div class="steps-grid relative grid grid-cols-1 gap-8 md:grid-cols-2 xl:grid-cols-4">
                    @foreach($workflowSteps as $index => $step)
                    <div class="relative reveal-on-scroll">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-7 text-center shadow-sm">
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
                        <div class="workflow-arrow absolute -right-6 top-1/2 z-10 hidden h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full border border-emerald-100 bg-white text-emerald-600 shadow-sm xl:flex">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding bg-slate-100 py-20">
        <div class="container-pad mx-auto max-w-7xl px-6">
            <div class="mx-auto mb-12 max-w-3xl text-center reveal-on-scroll">
                <h2 class="section-title text-3xl font-bold text-slate-900 lg:text-5xl">What our clients say</h2>
                <p class="section-subtitle mt-4 text-lg leading-8 text-slate-500">
                    Feedback from real bookings recorded in the platform
                </p>
            </div>
            <div class="testimonials-grid grid grid-cols-1 gap-8 md:grid-cols-3">
                @foreach($testimonials as $testimonial)
                <article class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm reveal-on-scroll">
                    <div class="flex gap-1 text-amber-400">
                        @for($i = 0; $i < 5; $i++)
                        <i class="fas fa-star"></i>
                        @endfor
                    </div>
                    <p class="mt-5 text-sm italic leading-7 text-slate-600">"{{ $testimonial['quote'] }}"</p>
                    <div class="mt-6 text-sm font-semibold text-slate-900">{{ $testimonial['name'] }}</div>
                </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="pricing" class="section-padding bg-white py-20">
        <div class="container-pad mx-auto max-w-7xl px-6">
            <div class="mx-auto mb-12 max-w-3xl text-center reveal-on-scroll">
                <h2 class="section-title text-3xl font-bold text-slate-900 lg:text-5xl">Straightforward pricing before booking</h2>
                <p class="section-subtitle mt-4 text-lg leading-8 text-slate-500">
                    Base rates below come from the active service catalog. Final totals are calculated during booking based on property details and service requirements.
                </p>
            </div>
            <div class="pricing-grid grid grid-cols-1 gap-8 md:grid-cols-3">
                @foreach($services as $service)
                @php
                    $serviceBookings = (int) ($serviceBookingCounts[$service->slug] ?? 0);
                    $isTopService = $topServiceSlug && $service->slug === $topServiceSlug && $serviceBookings > 0;
                @endphp
                <article class="rounded-3xl border border-slate-200 bg-slate-50 p-8 shadow-sm transition duration-300 hover:-translate-y-2 hover:shadow-xl reveal-on-scroll">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
                            <i class="fas fa-tags text-xl"></i>
                        </div>
                        @if($isTopService)
                        <span class="rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white shadow-sm">
                            Most Booked
                        </span>
                        @endif
                    </div>
                    <h3 class="mt-6 text-2xl font-bold text-slate-900">{{ $service->name }}</h3>
                    <p class="mt-4 text-sm leading-7 text-slate-500">{{ $serviceHighlights[$service->slug] ?? $service->description }}</p>
                    <div class="mt-8 text-5xl font-extrabold tracking-tight text-emerald-600">&#8369;{{ number_format($service->price, 0) }}</div>
                    <div class="mt-2 text-sm text-slate-400">starting rate for this cleaning package</div>
                    <a href="{{ $serviceCardUrl }}" class="mt-8 inline-flex w-full items-center justify-center rounded-2xl bg-emerald-600 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        {{ $serviceCardLabel }}
                    </a>
                </article>
                @endforeach
            </div>
            <p class="mt-8 text-center text-sm font-medium text-slate-500 reveal-on-scroll">
                No hidden fees. Final price is confirmed before your booking is processed.
            </p>
        </div>
    </section>

    <section id="faq" class="section-padding bg-slate-50 py-20">
        <div class="container-pad mx-auto max-w-7xl px-6">
            <div class="mx-auto mb-12 max-w-3xl text-center reveal-on-scroll">
                <h2 class="section-title text-3xl font-bold text-slate-900 lg:text-5xl">Frequently asked questions</h2>
                <p class="section-subtitle mt-4 text-lg leading-8 text-slate-500">
                    Here are the questions most visitors may ask before requesting a service through the platform.
                </p>
            </div>
            <div class="mx-auto max-w-4xl space-y-4">
                @foreach($faqs as $faq)
                <details class="faq-accordion group rounded-3xl border border-slate-200 bg-white px-6 py-5 shadow-sm reveal-on-scroll">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                        <span class="text-left text-lg font-semibold text-slate-900">{{ $faq['question'] }}</span>
                        <span class="faq-chevron flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition">
                            <i class="fas fa-chevron-down text-sm"></i>
                        </span>
                    </summary>
                    <div class="mt-4 border-t border-slate-100 pt-4 text-sm leading-7 text-slate-500">
                        {{ $faq['answer'] }}
                    </div>
                </details>
                @endforeach
            </div>
        </div>
    </section>

    <section class="cta-section py-20 text-white" style="background: linear-gradient(135deg, #0f6e56 0%, #16946d 48%, #0891b2 100%);">
        <div class="container-pad mx-auto max-w-5xl px-6 text-center reveal-on-scroll">
            <h2 class="text-3xl font-bold lg:text-5xl">Ready to book your first cleaning?</h2>
            <p class="mx-auto mt-5 max-w-3xl text-lg leading-8 text-emerald-50/90">
                Join clients across Valencia City who trust Home Cleaning Service for their home cleaning needs.
            </p>
            <div class="cta-buttons mt-8 flex flex-wrap justify-center gap-4">
                <a href="{{ $primaryCtaUrl }}" class="rounded-2xl bg-white px-8 py-3.5 font-semibold text-emerald-700 shadow-lg transition hover:-translate-y-0.5 hover:bg-emerald-50">{{ $primaryCtaLabel }}</a>
                <a href="{{ route('login') }}" class="rounded-2xl border border-white/60 px-8 py-3.5 font-medium text-white transition hover:bg-white hover:text-emerald-700">Login</a>
            </div>
            <div class="mt-6 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm text-emerald-50/90">
                <i class="fas fa-comment-dots text-emerald-100"></i>
                <span>Prefer to ask first? Message us during operating hours.</span>
            </div>
        </div>
    </section>
</main>
@endsection
