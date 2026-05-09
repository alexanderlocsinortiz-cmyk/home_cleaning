<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.pwa-head')
    <title>@yield('title', 'Home Cleaning Service') - Home Cleaning Service</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome/css/all.min.css') }}">
    @include('partials.ui-theme')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <script>document.documentElement.classList.add('js');</script>
</head>
<body>
    @php
        $servicesLink = request()->routeIs('home') ? '#services' : route('home') . '#services';
        $howItWorksLink = request()->routeIs('home') ? '#how-it-works' : route('home') . '#how-it-works';
        $pricingLink = request()->routeIs('home') ? '#pricing' : route('home') . '#pricing';
        $instantQuoteLink = request()->routeIs('home') ? '#instant-quote' : route('home') . '#instant-quote';
        $faqLink = request()->routeIs('home') ? '#faq' : route('home') . '#faq';
        $bookNowLink = match (true) {
            auth()->check() && auth()->user()->role === 'client' => route('bookings.create'),
            auth()->check() && in_array(auth()->user()->role, ['staff', 'admin']) => $pricingLink,
            default => $instantQuoteLink,
        };
    @endphp
    @if(!request()->routeIs('login') && !request()->routeIs('register'))
    <nav class="sticky top-0 z-50 border-b border-slate-200 bg-white shadow-sm">
        <div class="container-pad mx-auto max-w-[1200px] px-5 md:px-6">
            <div class="flex h-16 items-center justify-between">

                <a href="{{ url('/') }}" class="flex items-center gap-2.5 no-underline">
                    <img src="{{ asset('images/logo.png') }}" alt="Home Cleaning Service" class="h-12 w-auto">
                    <div class="leading-tight">
                        <div class="text-[15px] font-extrabold text-slate-800">Home Cleaning</div>
                        <div class="text-xs font-semibold text-accent-700">Service</div>
                    </div>
                </a>

                <div class="hidden items-center gap-8 md:flex">
                    <a href="{{ $servicesLink }}" class="text-sm font-medium text-slate-600 transition hover:text-slate-900">Services</a>
                    <a href="{{ $howItWorksLink }}" class="text-sm font-medium text-slate-600 transition hover:text-slate-900">How It Works</a>
                    <a href="{{ $pricingLink }}" class="text-sm font-medium text-slate-600 transition hover:text-slate-900">Pricing</a>
                    <a href="{{ route('map') }}" class="text-sm font-medium text-slate-600 transition hover:text-slate-900">Service Areas</a>
                    <a href="{{ $faqLink }}" class="text-sm font-medium text-slate-600 transition hover:text-slate-900">FAQ</a>
                </div>

                <div class="hidden items-center gap-2.5 md:flex">
                    @auth
                    <a href="{{ route(auth()->user()->role . '.dashboard') }}" class="px-3.5 py-2 text-sm font-semibold text-slate-600 transition hover:text-accent-800">Dashboard</a>
                    <a href="{{ $bookNowLink }}" class="rounded-xl bg-primary-500 px-5 py-2 text-sm font-semibold text-white transition hover:bg-primary-600">Book Now</a>
                    @else
                    <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-semibold text-slate-600 transition hover:text-accent-800">Login</a>
                    <a href="{{ $bookNowLink }}" class="rounded-xl bg-primary-500 px-5 py-2 text-sm font-semibold text-white transition hover:bg-primary-600">Book Now</a>
                    @endauth
                </div>

                <div class="flex items-center gap-2 md:hidden">
                    <a href="{{ $bookNowLink }}" class="rounded-xl bg-primary-600 px-3.5 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-primary-700">Book Now</a>
                    <button class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50" id="nav-hamburger" type="button" aria-expanded="false" aria-controls="mobile-nav-menu" onclick="toggleMobileNav()">
                        <svg id='hamburger-open' xmlns='http://www.w3.org/2000/svg' width='20' height='20' fill='none' viewBox='0 0 24 24' stroke='#475569' stroke-width='2'>
                            <path stroke-linecap='round' stroke-linejoin='round' d='M4 6h16M4 12h16M4 18h16'/>
                        </svg>
                        <svg id='hamburger-close' xmlns='http://www.w3.org/2000/svg' width='20' height='20' fill='none' viewBox='0 0 24 24' stroke='#475569' stroke-width='2' class="hidden">
                            <path stroke-linecap='round' stroke-linejoin='round' d='M6 18L18 6M6 6l12 12'/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="hidden border-t border-slate-100 py-4 md:hidden" id="mobile-nav-menu">
                <a href="{{ $servicesLink }}" onclick="closeMobileNav()" class="block border-b border-slate-50 px-1 py-2.5 text-[15px] font-medium text-gray-700 transition hover:text-gray-900">Services</a>
                <a href="{{ $howItWorksLink }}" onclick="closeMobileNav()" class="block border-b border-slate-50 px-1 py-2.5 text-[15px] font-medium text-gray-700 transition hover:text-gray-900">How It Works</a>
                <a href="{{ $pricingLink }}" onclick="closeMobileNav()" class="block border-b border-slate-50 px-1 py-2.5 text-[15px] font-medium text-gray-700 transition hover:text-gray-900">Pricing</a>
                <a href="{{ route('map') }}" onclick="closeMobileNav()" class="block border-b border-slate-50 px-1 py-2.5 text-[15px] font-medium text-gray-700 transition hover:text-gray-900">Service Areas</a>
                <a href="{{ $faqLink }}" onclick="closeMobileNav()" class="block border-b border-slate-50 px-1 py-2.5 text-[15px] font-medium text-gray-700 transition hover:text-gray-900">FAQ</a>
                <div class="mt-4 flex flex-col gap-2">
                    @auth
                    <a href="{{ route(auth()->user()->role . '.dashboard') }}" class="rounded-xl bg-slate-100 px-3 py-3 text-center text-[15px] font-semibold text-gray-700 transition hover:bg-slate-200">Dashboard</a>
                    <a href="{{ $bookNowLink }}" class="rounded-xl bg-primary-600 px-3 py-3 text-center text-[15px] font-bold text-white transition hover:bg-primary-700">Book Now</a>
                    @else
                    <a href="{{ route('login') }}" class="rounded-xl bg-slate-100 px-3 py-3 text-center text-[15px] font-semibold text-gray-700 transition hover:bg-slate-200">Login</a>
                    <a href="{{ $bookNowLink }}" class="rounded-xl bg-primary-500 px-3 py-3 text-center text-[15px] font-bold text-white transition hover:bg-primary-600">Book Now</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>
    @endif

    <main>@yield('content')</main>

    @if(!request()->routeIs('login') && !request()->routeIs('register'))
    <footer class="app-footer bg-gray-900 text-gray-300 pt-12 pb-0">
        <div class="container-pad max-w-7xl mx-auto px-4 sm:px-6 lg:px-4">
            <div class="footer-grid grid grid-cols-1 md:grid-cols-3 gap-8 pb-8">
                <div class="md:col-span-2">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 text-xl font-bold text-white mb-2">
                        <i class="fas fa-broom"></i><span>Home Cleaning Service</span>
                    </a>
                    <p class="text-sm leading-relaxed">
                        Premium home cleaning for Valencia City households, with clear pricing, trusted staff, and a smoother booking experience from quote to clean.
                    </p>
                </div>
                <div>
                    <h4 class="text-white mb-4 text-sm font-semibold">Quick Links</h4>
                    <ul class="footer-links space-y-2">
                        <li><a href="{{ route('home') }}" class="hover:text-primary-400 transition-colors text-sm">Home</a></li>
                        <li><a href="{{ $servicesLink }}" class="hover:text-primary-400 transition-colors text-sm">Services</a></li>
                        <li><a href="{{ $pricingLink }}" class="hover:text-primary-400 transition-colors text-sm">Pricing</a></li>
                        <li><a href="{{ route('map') }}" class="hover:text-primary-400 transition-colors text-sm">Service Areas</a></li>
                        <li><a href="{{ $faqLink }}" class="hover:text-primary-400 transition-colors text-sm">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white mb-4 text-sm font-semibold">Support</h4>
                    <p class="text-sm mb-2 flex items-center gap-2">
                        <i class="fas fa-map-marker-alt text-primary-400 w-4"></i> Valencia City, Bukidnon, Philippines
                    </p>
                    <p class="text-sm mb-2 flex items-center gap-2">
                        <i class="fas fa-envelope text-primary-400 w-4"></i> support@homecleaningservice.local
                    </p>
                    <p class="text-sm mb-2 flex items-center gap-2">
                        <i class="fas fa-clock text-primary-400 w-4"></i> Monday - Saturday, 8:00 AM - 5:00 PM
                    </p>
                    <p class="text-sm mb-2 flex items-center gap-2">
                        <i class="fas fa-circle-check text-primary-400 w-4"></i> Quotes and bookings are handled in one place
                    </p>
                    <p class="text-sm flex items-center gap-2">
                        <i class="fas fa-users text-primary-400 w-4"></i> Your service is handled by a reviewed local cleaning team
                    </p>
                </div>
            </div>
            <div class="border-t border-gray-800 py-4 text-center text-xs">
                <p>&copy; {{ date('Y') }} Home Cleaning Service. All rights reserved.</p>
            </div>
        </div>
    </footer>
    @endif

    <script src="{{ asset('js/main.js') }}"></script>
    @include('partials.pwa-script')
    @stack('scripts')
    <script>
    function toggleMobileNav() {
        const menu = document.getElementById('mobile-nav-menu');
        const navToggle = document.getElementById('nav-hamburger');
        const openIcon = document.getElementById('hamburger-open');
        const closeIcon = document.getElementById('hamburger-close');
        const isOpen = !menu.classList.contains('hidden');

        menu.classList.toggle('hidden', isOpen);
        openIcon.classList.toggle('hidden', !isOpen);
        closeIcon.classList.toggle('hidden', isOpen);
        navToggle?.setAttribute('aria-expanded', String(!isOpen));
    }

    function closeMobileNav() {
        const menu = document.getElementById('mobile-nav-menu');
        const navToggle = document.getElementById('nav-hamburger');
        const openIcon = document.getElementById('hamburger-open');
        const closeIcon = document.getElementById('hamburger-close');

        if (!menu || menu.classList.contains('hidden')) {
            return;
        }

        menu.classList.add('hidden');
        openIcon?.classList.remove('hidden');
        closeIcon?.classList.add('hidden');
        navToggle?.setAttribute('aria-expanded', 'false');
    }

    document.addEventListener('click', function(e) {
        const nav = document.getElementById('nav-hamburger');
        const menu = document.getElementById('mobile-nav-menu');
        if (nav && menu && !nav.contains(e.target) && !menu.contains(e.target)) {
            closeMobileNav();
        }
    });

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function() {
            closeMobileNav();
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const revealItems = document.querySelectorAll('.reveal-on-scroll');

        if (!revealItems.length) {
            return;
        }

        if (!('IntersectionObserver' in window)) {
            revealItems.forEach((item) => item.classList.add('reveal-visible'));
            return;
        }

        document.documentElement.classList.add('reveal-enabled');

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('reveal-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -40px 0px',
        });

        revealItems.forEach((item) => observer.observe(item));
    });
    </script>
</body>
</html>

