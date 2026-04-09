<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Home Cleaning Service') - Home Cleaning Service</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @stack('styles')
    <script>document.documentElement.classList.add('js');</script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: '#1D9E75',
                    'primary-dark': '#0F6E56',
                    secondary: '#E1F5EE',
                }
            }
        }
    }
    </script>
    <style>
    .nav-mobile-btn { display: none; }
    .nav-mobile-menu { display: none; }
    .nav-mobile-menu.open { display: flex; flex-direction: column; }
    html { scroll-behavior: smooth; }
    #services, #how-it-works, #pricing, #faq { scroll-margin-top: 96px; }
    .faq-accordion summary::-webkit-details-marker { display: none; }
    .faq-accordion[open] .faq-chevron { transform: rotate(180deg); }
    .js .reveal-on-scroll {
        opacity: 0;
        transform: translateY(28px);
        transition: opacity 0.7s ease, transform 0.7s ease;
    }
    .js .reveal-on-scroll.reveal-visible {
        opacity: 1;
        transform: translateY(0);
    }

    /* ===== MOBILE RESPONSIVE - LANDING PAGE ===== */
    @media (max-width: 767px) {

        /* NAVBAR */
        .nav-desktop-links { display: none !important; }
        .nav-mobile-btn { display: flex !important; }
        .nav-mobile-menu { display: none; }
        .nav-mobile-menu.open { display: flex !important; flex-direction: column; }

        /* HERO SECTION */
        .hero-section { padding: 3rem 1rem 2rem !important; text-align: left !important; }
        .hero-section h1 { font-size: 30px !important; line-height: 1.15 !important; }
        .hero-section p { font-size: 15px !important; }
        .hero-buttons { flex-direction: row !important; flex-wrap: wrap !important; align-items: flex-start !important; justify-content: flex-start !important; gap: 10px !important; }
        .hero-buttons a { width: auto !important; max-width: none !important; text-align: center; justify-content: center !important; }
        .hero-grid { grid-template-columns: 1fr !important; }
        .hero-image { display: block !important; }
        .hero-photo { height: 220px !important; }
        .hero-benefits { display: grid !important; grid-template-columns: 1fr !important; gap: 0.75rem !important; }

        /* SERVICES SECTION */
        .services-grid { grid-template-columns: 1fr !important; gap: 1rem !important; }
        .section-padding { padding: 2.5rem 1rem !important; }
        .section-title { font-size: 24px !important; }
        .section-subtitle { font-size: 14px !important; }

        /* STATS / NUMBERS SECTION */
        .stats-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 1rem !important; }
        .stat-number { font-size: 32px !important; }

        /* HOW IT WORKS */
        .steps-grid { grid-template-columns: 1fr !important; gap: 1.5rem !important; }
        .workflow-arrow { display: none !important; }

        /* MAP SECTION */
        .map-container { height: 300px !important; }
        .map-section { padding: 2rem 1rem !important; }

        /* TESTIMONIALS */
        .testimonials-grid { grid-template-columns: 1fr !important; gap: 1rem !important; }

        /* PRICING */
        .pricing-grid { grid-template-columns: 1fr !important; gap: 1rem !important; max-width: 380px !important; margin-left: auto !important; margin-right: auto !important; }

        /* FAQ */
        .faq-grid { grid-template-columns: 1fr !important; }
        .faq-item { padding: 1rem !important; }

        /* CTA SECTION */
        .cta-section { padding: 2.5rem 1rem !important; }
        .cta-section h2 { font-size: 24px !important; }
        .cta-buttons { flex-direction: column !important; align-items: center !important; gap: 10px !important; }
        .cta-buttons a { width: 100% !important; max-width: 300px; text-align: center; justify-content: center !important; }

        /* FOOTER */
        .footer-grid { grid-template-columns: 1fr !important; gap: 2rem !important; text-align: center !important; }
        .footer-links { justify-content: center !important; }
        .footer-social { justify-content: center !important; }

        /* GENERAL */
        .hide-mobile { display: none !important; }
        .container-pad { padding-left: 1rem !important; padding-right: 1rem !important; }
    }

    @media (min-width: 768px) and (max-width: 1023px) {
        /* TABLET */
        .services-grid { grid-template-columns: repeat(2, 1fr) !important; }
        .stats-grid { grid-template-columns: repeat(2, 1fr) !important; }
        .steps-grid { grid-template-columns: repeat(2, 1fr) !important; }
        .testimonials-grid { grid-template-columns: repeat(2, 1fr) !important; }
        .pricing-grid { grid-template-columns: repeat(2, 1fr) !important; }
        .footer-grid { grid-template-columns: repeat(2, 1fr) !important; }
        .hero-section h1 { font-size: 36px !important; line-height: 1.1 !important; }
        .section-padding { padding: 3rem 1.5rem !important; }
    }
    </style>
</head>
<body>
    @php
        $servicesLink = request()->routeIs('home') ? '#services' : route('home') . '#services';
        $howItWorksLink = request()->routeIs('home') ? '#how-it-works' : route('home') . '#how-it-works';
        $pricingLink = request()->routeIs('home') ? '#pricing' : route('home') . '#pricing';
        $faqLink = request()->routeIs('home') ? '#faq' : route('home') . '#faq';
        $bookNowLink = match (true) {
            auth()->check() && auth()->user()->role === 'client' => route('bookings.create'),
            auth()->check() && in_array(auth()->user()->role, ['staff', 'admin']) => $pricingLink,
            default => route('register'),
        };
    @endphp
    @if(!request()->routeIs('login') && !request()->routeIs('register'))
    <nav style='position: sticky; top: 0; z-index: 50; background: white; border-bottom: 1px solid #e2e8f0; box-shadow: 0 1px 4px rgba(0,0,0,0.06);'>
        <div class="container-pad" style='max-width: 1200px; margin: 0 auto; padding: 0 1.25rem;'>
            <div style='display: flex; align-items: center; justify-content: space-between; height: 64px;'>

                <a href="{{ url('/') }}" style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
                    <img src="{{ asset('images/logo.png') }}" alt="Home Cleaning Service" style="height: 48px; width: auto;">
                    <div style="line-height: 1.2;">
                        <div style="font-size: 15px; font-weight: 800; color: #1e293b;">Home Cleaning</div>
                        <div style="font-size: 12px; font-weight: 600; color: #1D9E75;">Service</div>
                    </div>
                </a>

                <div class='nav-desktop-links' style='display: flex; align-items: center; gap: 2rem;'>
                    <a href="{{ $servicesLink }}" style='font-size: 14px; font-weight: 500; color: #475569; text-decoration: none;'>Services</a>
                    <a href="{{ $howItWorksLink }}" style='font-size: 14px; font-weight: 500; color: #475569; text-decoration: none;'>How It Works</a>
                    <a href="{{ $pricingLink }}" style='font-size: 14px; font-weight: 500; color: #475569; text-decoration: none;'>Pricing</a>
                    <a href="{{ route('map') }}" style='font-size: 14px; font-weight: 500; color: #475569; text-decoration: none;'>Service Areas</a>
                    <a href="{{ $faqLink }}" style='font-size: 14px; font-weight: 500; color: #475569; text-decoration: none;'>FAQ</a>
                </div>

                <div class='nav-desktop-links' style='display: flex; align-items: center; gap: 10px;'>
                    @auth
                    <a href="{{ route(auth()->user()->role . '.dashboard') }}" style='font-size: 14px; font-weight: 600; color: #475569; text-decoration: none; padding: 8px 14px;'>Dashboard</a>
                    <a href="{{ $bookNowLink }}" class="rounded-xl bg-emerald-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">Book Now</a>
                    @else
                    <a href="{{ route('login') }}" style='font-size: 14px; font-weight: 600; color: #475569; text-decoration: none; padding: 8px 16px;'>Login</a>
                    <a href="{{ $bookNowLink }}" class="rounded-xl bg-emerald-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">Book Now</a>
                    @endauth
                </div>

                <button class='nav-mobile-btn' id='nav-hamburger' onclick='toggleMobileNav()' style='display: none; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #e2e8f0; border-radius: 8px; background: white; cursor: pointer;'>
                    <svg id='hamburger-open' xmlns='http://www.w3.org/2000/svg' width='20' height='20' fill='none' viewBox='0 0 24 24' stroke='#475569' stroke-width='2'>
                        <path stroke-linecap='round' stroke-linejoin='round' d='M4 6h16M4 12h16M4 18h16'/>
                    </svg>
                    <svg id='hamburger-close' xmlns='http://www.w3.org/2000/svg' width='20' height='20' fill='none' viewBox='0 0 24 24' stroke='#475569' stroke-width='2' style='display:none;'>
                        <path stroke-linecap='round' stroke-linejoin='round' d='M6 18L18 6M6 6l12 12'/>
                    </svg>
                </button>
            </div>

            <div class='nav-mobile-menu' id='mobile-nav-menu' style='display: none; border-top: 1px solid #f1f5f9; padding: 1rem 0 1.25rem;'>
                <a href='{{ $servicesLink }}' onclick='closeMobileNav()' style='display: block; padding: 10px 4px; font-size: 15px; font-weight: 500; color: #374151; text-decoration: none; border-bottom: 1px solid #f8fafc;'>Services</a>
                <a href='{{ $howItWorksLink }}' onclick='closeMobileNav()' style='display: block; padding: 10px 4px; font-size: 15px; font-weight: 500; color: #374151; text-decoration: none; border-bottom: 1px solid #f8fafc;'>How It Works</a>
                <a href='{{ $pricingLink }}' onclick='closeMobileNav()' style='display: block; padding: 10px 4px; font-size: 15px; font-weight: 500; color: #374151; text-decoration: none; border-bottom: 1px solid #f8fafc;'>Pricing</a>
                <a href='{{ route("map") }}' onclick='closeMobileNav()' style='display: block; padding: 10px 4px; font-size: 15px; font-weight: 500; color: #374151; text-decoration: none; border-bottom: 1px solid #f8fafc;'>Service Areas</a>
                <a href='{{ $faqLink }}' onclick='closeMobileNav()' style='display: block; padding: 10px 4px; font-size: 15px; font-weight: 500; color: #374151; text-decoration: none; border-bottom: 1px solid #f8fafc;'>FAQ</a>
                <div style='display: flex; flex-direction: column; gap: 8px; margin-top: 1rem;'>
                    @auth
                    <a href="{{ route(auth()->user()->role . '.dashboard') }}" style='background: #f1f5f9; color: #374151; border-radius: 10px; padding: 12px; font-size: 15px; font-weight: 600; text-decoration: none; text-align: center;'>Dashboard</a>
                    <a href="{{ $bookNowLink }}" style='background: #1D9E75; color: white; border-radius: 10px; padding: 12px; font-size: 15px; font-weight: 700; text-decoration: none; text-align: center;'>Book Now</a>
                    @else
                    <a href="{{ route('login') }}" style='background: #f1f5f9; color: #374151; border-radius: 10px; padding: 12px; font-size: 15px; font-weight: 600; text-decoration: none; text-align: center;'>Login</a>
                    <a href="{{ $bookNowLink }}" style='background: #1D9E75; color: white; border-radius: 10px; padding: 12px; font-size: 15px; font-weight: 700; text-decoration: none; text-align: center;'>Book Now</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>
    @endif

    <main>@yield('content')</main>

    @if(!request()->routeIs('login') && !request()->routeIs('register'))
    <footer class="bg-gray-900 text-gray-300 pt-12 pb-0">
        <div class="container-pad max-w-7xl mx-auto px-4 sm:px-6 lg:px-4">
            <div class="footer-grid grid grid-cols-1 md:grid-cols-3 gap-8 pb-8">
                <div class="md:col-span-2">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 text-xl font-bold text-white mb-2">
                        <i class="fas fa-broom"></i><span>Home Cleaning Service</span>
                    </a>
                    <p class="text-sm leading-relaxed">
                        Home Cleaning Service is a web-based home cleaning platform for Valencia City, combining simple service requests with organized booking and operations management.
                    </p>
                </div>
                <div>
                    <h4 class="text-white mb-4 text-sm font-semibold">Quick Links</h4>
                    <ul class="footer-links space-y-2">
                        <li><a href="{{ route('home') }}" class="hover:text-emerald-400 transition-colors text-sm">Home</a></li>
                        <li><a href="{{ $servicesLink }}" class="hover:text-emerald-400 transition-colors text-sm">Services</a></li>
                        <li><a href="{{ $pricingLink }}" class="hover:text-emerald-400 transition-colors text-sm">Pricing</a></li>
                        <li><a href="{{ route('map') }}" class="hover:text-emerald-400 transition-colors text-sm">Service Areas</a></li>
                        <li><a href="{{ $faqLink }}" class="hover:text-emerald-400 transition-colors text-sm">FAQ</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white mb-4 text-sm font-semibold">Support</h4>
                    <p class="text-sm mb-2 flex items-center gap-2">
                        <i class="fas fa-map-marker-alt text-emerald-400 w-4"></i> Valencia City, Bukidnon, Philippines
                    </p>
                    <p class="text-sm mb-2 flex items-center gap-2">
                        <i class="fas fa-envelope text-emerald-400 w-4"></i> support@homecleaningservice.local
                    </p>
                    <p class="text-sm mb-2 flex items-center gap-2">
                        <i class="fas fa-clock text-emerald-400 w-4"></i> Monday - Saturday, 8:00 AM - 5:00 PM
                    </p>
                    <p class="text-sm mb-2 flex items-center gap-2">
                        <i class="fas fa-circle-check text-emerald-400 w-4"></i> Booking requests are handled through the platform
                    </p>
                    <p class="text-sm flex items-center gap-2">
                        <i class="fas fa-users text-emerald-400 w-4"></i> Service coordination is managed by the Home Cleaning Service team
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
    @stack('scripts')
    <script>
    function toggleMobileNav() {
        const menu = document.getElementById('mobile-nav-menu');
        const openIcon = document.getElementById('hamburger-open');
        const closeIcon = document.getElementById('hamburger-close');
        const isOpen = menu.classList.contains('open');

        if (isOpen) {
            menu.classList.remove('open');
            menu.style.display = 'none';
            openIcon.style.display = 'block';
            closeIcon.style.display = 'none';
        } else {
            menu.classList.add('open');
            menu.style.display = 'flex';
            menu.style.flexDirection = 'column';
            openIcon.style.display = 'none';
            closeIcon.style.display = 'block';
        }
    }

    function closeMobileNav() {
        const menu = document.getElementById('mobile-nav-menu');
        const openIcon = document.getElementById('hamburger-open');
        const closeIcon = document.getElementById('hamburger-close');
        menu.classList.remove('open');
        menu.style.display = 'none';
        openIcon.style.display = 'block';
        closeIcon.style.display = 'none';
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

