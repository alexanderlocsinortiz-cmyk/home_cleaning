@extends('layouts.app')
@section('title', 'Sign In')

@section('content')
@php
    $features = [
        [
            'icon' => 'fa-calendar-check',
            'title' => 'Easy Online Booking',
            'text' => 'Schedule cleaning services in a few guided steps.',
        ],
        [
            'icon' => 'fa-shield-halved',
            'title' => 'Secure Client Access',
            'text' => 'Sign in safely with verified email-based access.',
        ],
        [
            'icon' => 'fa-route',
            'title' => 'Booking Progress Tracking',
            'text' => 'Review service updates and booking activity in one place.',
        ],
    ];
@endphp

<div class="min-h-screen bg-slate-950">
    <div class="relative mx-auto flex min-h-screen max-w-7xl items-center px-4 py-5 sm:px-6 lg:overflow-hidden lg:px-8 lg:py-4">
        <div class="grid w-full gap-4 lg:h-[calc(100vh-2rem)] lg:grid-cols-[minmax(0,1.08fr)_480px]">
            <!-- Left Panel: Branding & Features -->
            <aside class="hidden h-full min-h-0 flex-col justify-between overflow-hidden rounded-[32px] border border-white/15 bg-white/10 p-8 text-white shadow-[0_28px_80px_rgba(15,23,42,0.22)] backdrop-blur-md lg:flex">
                <div>
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/20">
                            <img src="{{ asset('images/logo.png') }}" alt="Home Cleaning Service" class="h-11 w-11 object-contain">
                        </div>
                        <div>
                            <div class="text-xl font-bold tracking-tight">Home Cleaning Service</div>
                            <div class="mt-1 text-sm text-white/75">Valencia City, Bukidnon</div>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-2 text-xs font-semibold text-white/85">
                        <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5">Client Portal</span>
                        <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5">Professional Services</span>
                    </div>

                    <div class="mt-8 max-w-xl">
                        <h1 class="text-4xl font-bold leading-tight tracking-tight">Professional home cleaning, organized online.</h1>
                        <p class="mt-4 max-w-lg text-sm leading-7 text-white/80">
                            Manage your bookings, track service updates, and access your account through a cleaner, more professional client experience.
                        </p>
                    </div>
                </div>

                <div class="grid gap-3">
                    @foreach($features as $feature)
                        <div class="group flex items-start gap-3 rounded-2xl border border-white/15 bg-white/10 px-4 py-3.5 transition hover:bg-white/15">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-500/20 text-primary-300 transition group-hover:bg-primary-500/30">
                                <i class="fas {{ $feature['icon'] }}"></i>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-white">{{ $feature['title'] }}</div>
                                <div class="mt-1 text-xs leading-5 text-white/75">{{ $feature['text'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="rounded-2xl border border-white/15 bg-slate-900/30 p-4 backdrop-blur-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-white/60">Why Choose Us</div>
                            <div class="mt-2 text-lg font-semibold text-white">Trusted by local families.</div>
                            <div class="mt-1 text-xs leading-5 text-white/70">Experience professional cleaning services with verified staff and real-time booking tracking.</div>
                        </div>
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary-500/20 text-primary-300">
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Right Panel: Forms with Tabs -->
            <section class="flex flex-col min-h-0 overflow-hidden">
                <div class="w-full max-w-md flex flex-col min-h-0 overflow-y-auto px-4">
                    <!-- Mobile Header (Hidden on Large Screens) -->
                    <div class="mb-6 mt-6 text-center lg:hidden">
                        <a href="{{ url('/') }}" class="inline-flex flex-col items-center gap-3 text-center no-underline">
                            <div class="inline-flex rounded-full bg-white/20 p-2">
                                <img src="{{ asset('images/logo.png') }}" alt="Home Cleaning Service" class="h-14 w-14 object-contain drop-shadow-[0_8px_20px_rgba(0,0,0,0.22)]">
                            </div>
                            <div>
                                <div class="text-lg font-bold text-white">Home Cleaning Service</div>
                                <div class="mt-0.5 text-sm text-white/70">Valencia City, Bukidnon</div>
                            </div>
                        </a>
                    </div>

                    <!-- Form Container -->
                    <div class="rounded-[30px] border border-white/80 bg-white/95 p-6 shadow-[0_26px_70px_rgba(15,23,42,0.20)] ring-1 ring-black/5 backdrop-blur-sm sm:p-8 relative">
                        
                        <!-- Home Button (Top Right) -->
                        <a href="{{ url('/') }}" class="absolute top-4 right-4 inline-flex items-center justify-center h-10 w-10 rounded-full bg-primary-100 text-primary-600 hover:bg-primary-200 transition" title="Back to Home">
                            <i class="fas fa-home text-sm"></i>
                        </a>
                        
                        <!-- Tabs Navigation -->
                        <div class="mb-8 flex gap-2" role="tablist">
                            <button
                                id="signin-tab"
                                type="button"
                                role="tab"
                                aria-selected="true"
                                aria-controls="signin-panel"
                                class="flex-1 rounded-2xl border-2 border-primary-600 bg-primary-600 py-2.5 px-4 text-sm font-semibold text-white transition duration-200 hover:bg-primary-700 active:scale-95"
                                onclick="switchTab('signin')"
                            >
                                <i class="fas fa-sign-in-alt mr-1.5"></i>
                                Sign In
                            </button>
                            <button
                                id="signup-tab"
                                type="button"
                                role="tab"
                                aria-selected="false"
                                aria-controls="signup-panel"
                                class="flex-1 rounded-2xl border-2 border-slate-300 bg-slate-100 py-2.5 px-4 text-sm font-semibold text-slate-700 transition duration-200 hover:border-slate-400 hover:bg-slate-200 active:scale-95"
                                onclick="switchTab('signup')"
                            >
                                <i class="fas fa-user-plus mr-1.5"></i>
                                Register
                            </button>
                        </div>

                        <!-- Sign In Form -->
                        <div
                            id="signin-panel"
                            role="tabpanel"
                            aria-labelledby="signin-tab"
                            class="signin-tab-content transition duration-300"
                        >
                            <div class="mb-6 text-center">
                                <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-100">
                                    <i class="fas fa-lock text-lg text-primary-600"></i>
                                </div>
                                <h2 class="mt-4 text-2xl font-bold text-slate-900">Welcome Back</h2>
                                <p class="mt-2 text-sm text-slate-600">Sign in to manage your bookings and account</p>
                            </div>

                            @if ($errors->any())
                                <div class="mb-4 flex items-start gap-2 rounded-2xl border border-red-200 bg-red-50 px-4 py-3">
                                    <i class="fas fa-exclamation-circle mt-0.5 text-red-500"></i>
                                    <div class="text-sm text-red-700">
                                        {{ $errors->first() }}
                                    </div>
                                </div>
                            @endif

                            @if(session('success'))
                                <div class="mb-4 flex items-start gap-2 rounded-2xl border border-green-200 bg-green-50 px-4 py-3">
                                    <i class="fas fa-check-circle mt-0.5 text-green-600"></i>
                                    <div class="text-sm text-green-700">
                                        {{ session('success') }}
                                    </div>
                                </div>
                            @endif

                            <form action="{{ route('login.store') }}" method="POST" class="space-y-5">
                                @csrf

                                <!-- Email Field -->
                                <div>
                                    <label for="signin-email" class="mb-2.5 block text-sm font-semibold text-slate-800">
                                        Email Address <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative group">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 transition group-focus-within:text-primary-600">
                                            <i class="fas fa-envelope text-sm"></i>
                                        </span>
                                        <input
                                            id="signin-email"
                                            type="email"
                                            name="email"
                                            autocomplete="email"
                                            value="{{ old('email') }}"
                                            placeholder="your@email.com"
                                            required
                                            autofocus
                                            class="w-full rounded-2xl border-2 border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm text-slate-900 placeholder-slate-500 transition focus:border-primary-500 focus:outline-hidden focus:ring-0"
                                        >
                                    </div>
                                    @error('email')
                                        <p class="mt-2 text-xs text-red-600 flex items-center gap-1"><i class="fas fa-info-circle"></i>{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Password Field -->
                                <div>
                                    <label for="signin-password" class="mb-2.5 block text-sm font-semibold text-slate-800">
                                        Password <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative group">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 transition group-focus-within:text-primary-600">
                                            <i class="fas fa-lock text-sm"></i>
                                        </span>
                                        <input
                                            type="password"
                                            name="password"
                                            id="signin-password"
                                            autocomplete="current-password"
                                            placeholder="••••••••"
                                            required
                                            class="w-full rounded-2xl border-2 border-slate-200 bg-slate-50 py-3 pl-11 pr-12 text-sm text-slate-900 placeholder-slate-500 transition focus:border-primary-500 focus:outline-hidden focus:ring-0"
                                        >
                                        <button
                                            type="button"
                                            onclick="togglePw('signin-password'", this)"
                                            class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 transition hover:text-slate-600"
                                            aria-label="Toggle password visibility"
                                        >
                                            <i class="fas fa-eye text-sm"></i>
                                        </button>
                                    </div>
                                    @error('password')
                                        <p class="mt-2 text-xs text-red-600 flex items-center gap-1"><i class="fas fa-info-circle"></i>{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Remember & Forgot -->
                                <div class="flex items-center justify-between gap-2 pt-1">
                                    <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-700 transition hover:text-slate-900">
                                        <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-primary-600 transition" @checked(old('remember'))>
                                        <span class="select-none">Remember me</span>
                                    </label>
                                    <a href="#" class="text-sm font-medium text-slate-600 transition hover:text-primary-600 hover:underline">Forgot password?</a>
                                </div>

                                <!-- Submit Button -->
                                <button
                                    type="submit"
                                    class="w-full rounded-2xl bg-primary-600 py-3 text-sm font-semibold text-white shadow-md transition duration-200 hover:bg-primary-700 active:scale-95 flex items-center justify-center gap-2"
                                >
                                    <i class="fas fa-arrow-right"></i>
                                    <span>Sign In</span>
                                </button>

                                <!-- Switch to Register -->
                                <p class="text-center text-sm text-slate-600">
                                    Don't have an account?
                                    <button type="button" onclick="switchTab('signup')" class="font-semibold text-primary-600 transition hover:text-primary-700 hover:underline">
                                        Create one here
                                    </button>
                                </p>
                            </form>
                        </div>

                        <!-- Register Form -->
                        <div
                            id="signup-panel"
                            role="tabpanel"
                            aria-labelledby="signup-tab"
                            class="signup-tab-content hidden transition duration-300"
                        >
                            <div class="mb-6 text-center">
                                <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-100">
                                    <i class="fas fa-user-plus text-lg text-primary-600"></i>
                                </div>
                                <h2 class="mt-4 text-2xl font-bold text-slate-900">Create Account</h2>
                                <p class="mt-2 text-sm text-slate-600">Join us and start booking cleaning services</p>
                            </div>

                            @if ($errors->any() && Route::current()->getName() === 'register')
                                <div class="mb-4 flex items-start gap-2 rounded-2xl border border-red-200 bg-red-50 px-4 py-3">
                                    <i class="fas fa-exclamation-circle mt-0.5 text-red-500"></i>
                                    <div class="text-sm text-red-700">
                                        {{ $errors->first() }}
                                    </div>
                                </div>
                            @endif

                            <form action="{{ route('register.store') }}" method="POST" class="space-y-5">
                                @csrf

                                <!-- Full Name Row -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label for="signup-fname" class="mb-2.5 block text-sm font-semibold text-slate-800">
                                            First Name <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative group">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 transition group-focus-within:text-primary-600">
                                                <i class="fas fa-user text-sm"></i>
                                            </span>
                                            <input
                                                id="signup-fname"
                                                type="text"
                                                name="first_name"
                                                value="{{ old('first_name') }}"
                                                placeholder="John"
                                                required
                                                autocomplete="given-name"
                                                class="w-full rounded-2xl border-2 border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm text-slate-900 placeholder-slate-500 transition focus:border-primary-500 focus:outline-hidden focus:ring-0"
                                            >
                                        </div>
                                        @error('first_name')<p class="mt-2 text-xs text-red-600"><i class="fas fa-info-circle"></i> {{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="signup-lname" class="mb-2.5 block text-sm font-semibold text-slate-800">
                                            Last Name <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative group">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 transition group-focus-within:text-primary-600">
                                                <i class="fas fa-user text-sm"></i>
                                            </span>
                                            <input
                                                id="signup-lname"
                                                type="text"
                                                name="last_name"
                                                value="{{ old('last_name') }}"
                                                placeholder="Doe"
                                                required
                                                autocomplete="family-name"
                                                class="w-full rounded-2xl border-2 border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm text-slate-900 placeholder-slate-500 transition focus:border-primary-500 focus:outline-hidden focus:ring-0"
                                            >
                                        </div>
                                        @error('last_name')<p class="mt-2 text-xs text-red-600"><i class="fas fa-info-circle"></i> {{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <!-- Email -->
                                <div>
                                    <label for="signup-email" class="mb-2.5 block text-sm font-semibold text-slate-800">
                                        Email Address <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative group">
                                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 transition group-focus-within:text-primary-600">
                                            <i class="fas fa-envelope text-sm"></i>
                                        </span>
                                        <input
                                            id="signup-email"
                                            type="email"
                                            name="email"
                                            value="{{ old('email') }}"
                                            placeholder="your@email.com"
                                            required
                                            autocomplete="email"
                                            class="w-full rounded-2xl border-2 border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm text-slate-900 placeholder-slate-500 transition focus:border-primary-500 focus:outline-hidden focus:ring-0"
                                        >
                                    </div>
                                    @error('email')<p class="mt-2 text-xs text-red-600"><i class="fas fa-info-circle"></i> {{ $message }}</p>@enderror
                                </div>

                                <!-- Phone & DOB Row -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label for="signup-phone" class="mb-2.5 block text-sm font-semibold text-slate-800">
                                            Phone Number <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative group">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 transition group-focus-within:text-primary-600">
                                                <i class="fas fa-phone text-sm"></i>
                                            </span>
                                            <input
                                                id="signup-phone"
                                                type="text"
                                                name="phone"
                                                value="{{ old('phone') }}"
                                                placeholder="09XXXXXXXXX"
                                                required
                                                autocomplete="tel"
                                                class="w-full rounded-2xl border-2 border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm text-slate-900 placeholder-slate-500 transition focus:border-primary-500 focus:outline-hidden focus:ring-0"
                                            >
                                        </div>
                                        @error('phone')<p class="mt-2 text-xs text-red-600"><i class="fas fa-info-circle"></i> {{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="signup-dob" class="mb-2.5 block text-sm font-semibold text-slate-800">
                                            Date of Birth <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative group">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 transition group-focus-within:text-primary-600">
                                                <i class="fas fa-calendar text-sm"></i>
                                            </span>
                                            <input
                                                id="signup-dob"
                                                type="date"
                                                name="date_of_birth"
                                                value="{{ old('date_of_birth') }}"
                                                required
                                                autocomplete="bday"
                                                class="w-full rounded-2xl border-2 border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm text-slate-900 placeholder-slate-500 transition focus:border-primary-500 focus:outline-hidden focus:ring-0"
                                            >
                                        </div>
                                        @error('date_of_birth')<p class="mt-2 text-xs text-red-600"><i class="fas fa-info-circle"></i> {{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <!-- Password Row -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label for="signup-password" class="mb-2.5 block text-sm font-semibold text-slate-800">
                                            Password <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative group">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 transition group-focus-within:text-primary-600">
                                                <i class="fas fa-lock text-sm"></i>
                                            </span>
                                            <input
                                                id="signup-password"
                                                type="password"
                                                name="password"
                                                placeholder="••••••••"
                                                required
                                                autocomplete="new-password"
                                                class="w-full rounded-2xl border-2 border-slate-200 bg-slate-50 py-3 pl-11 pr-12 text-sm text-slate-900 placeholder-slate-500 transition focus:border-primary-500 focus:outline-hidden focus:ring-0"
                                            >
                                            <button
                                                type="button"
                                                onclick="togglePw('signup-password'", this)"
                                                class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 transition hover:text-slate-600"
                                                aria-label="Toggle password visibility"
                                            >
                                                <i class="fas fa-eye text-sm"></i>
                                            </button>
                                        </div>
                                        @error('password')<p class="mt-2 text-xs text-red-600"><i class="fas fa-info-circle"></i> {{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="signup-confirm" class="mb-2.5 block text-sm font-semibold text-slate-800">
                                            Confirm Password <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative group">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 transition group-focus-within:text-primary-600">
                                                <i class="fas fa-check text-sm"></i>
                                            </span>
                                            <input
                                                id="signup-confirm"
                                                type="password"
                                                name="password_confirmation"
                                                placeholder="••••••••"
                                                required
                                                autocomplete="new-password"
                                                class="w-full rounded-2xl border-2 border-slate-200 bg-slate-50 py-3 pl-11 pr-12 text-sm text-slate-900 placeholder-slate-500 transition focus:border-primary-500 focus:outline-hidden focus:ring-0"
                                            >
                                            <button
                                                type="button"
                                                onclick="togglePw('signup-confirm'", this)"
                                                class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 transition hover:text-slate-600"
                                                aria-label="Toggle password visibility"
                                            >
                                                <i class="fas fa-eye text-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Password Requirements -->
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <p class="text-xs font-semibold text-slate-700 mb-2">Password Requirements:</p>
                                    <ul class="text-xs text-slate-600 space-y-1">
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-check text-green-500"></i>
                                            <span>Minimum 8 characters</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-info text-slate-400"></i>
                                            <span>Mix of uppercase and lowercase letters</span>
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <i class="fas fa-info text-slate-400"></i>
                                            <span>Include numbers or special characters</span>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Age Confirmation -->
                                <label class="flex items-start gap-2.5 cursor-pointer group">
                                    <input type="checkbox" required class="h-4 w-4 rounded border-slate-300 text-primary-600 mt-1 transition">
                                    <span class="text-xs text-slate-600 group-hover:text-slate-800">I confirm I am at least 18 years old and agree to the Terms of Service and Privacy Policy</span>
                                </label>

                                <!-- Submit Button -->
                                <button
                                    type="submit"
                                    class="w-full rounded-2xl bg-primary-600 py-3 text-sm font-semibold text-white shadow-md transition duration-200 hover:bg-primary-700 active:scale-95 flex items-center justify-center gap-2"
                                >
                                    <i class="fas fa-check"></i>
                                    <span>Create Account</span>
                                </button>

                                <!-- Switch to Sign In -->
                                <p class="text-center text-sm text-slate-600">
                                    Already have an account?
                                    <button type="button" onclick="switchTab('signin')" class="font-semibold text-primary-600 transition hover:text-primary-700 hover:underline">
                                        Sign in here
                                    </button>
                                </p>
                            </form>
                        </div>
                    </div>

                    <!-- Footer Link -->
                    <div class="mt-6 text-center">
                        <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-sm text-white/70 transition hover:text-white">
                            <i class="fas fa-arrow-left text-xs"></i>
                            <span>Back to Home</span>
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- Tab Switching Script -->
<script>
function switchTab(tab) {
    // Hide all panels
    document.querySelectorAll('[role="tabpanel"]').forEach(panel => {
        panel.classList.add('hidden');
        panel.classList.remove('block');
    });

    // Update tab buttons
    document.querySelectorAll('[role="tab"]').forEach(btn => {
        btn.setAttribute('aria-selected', 'false');
        btn.classList.remove('border-primary-600', 'bg-primary-600', 'text-white', 'hover:bg-primary-700');
        btn.classList.add('border-slate-300', 'bg-slate-100', 'text-slate-700', 'hover:border-slate-400', 'hover:bg-slate-200');
    });

    // Show selected panel with animation
    if (tab === 'signin') {
        document.getElementById('signin-panel').classList.remove('hidden');
        document.getElementById('signin-panel').classList.add('block');
        document.getElementById('signin-tab').setAttribute('aria-selected', 'true');
        document.getElementById('signin-tab').classList.remove('border-slate-300', 'bg-slate-100', 'text-slate-700', 'hover:border-slate-400', 'hover:bg-slate-200');
        document.getElementById('signin-tab').classList.add('border-primary-600', 'bg-primary-600', 'text-white', 'hover:bg-primary-700');
        document.getElementById('signin-email').focus();
    } else {
        document.getElementById('signup-panel').classList.remove('hidden');
        document.getElementById('signup-panel').classList.add('block');
        document.getElementById('signup-tab').setAttribute('aria-selected', 'true');
        document.getElementById('signup-tab').classList.remove('border-slate-300', 'bg-slate-100', 'text-slate-700', 'hover:border-slate-400', 'hover:bg-slate-200');
        document.getElementById('signup-tab').classList.add('border-primary-600', 'bg-primary-600', 'text-white', 'hover:bg-primary-700');
        document.getElementById('signup-fname').focus();
    }
}

// Password visibility toggle
function togglePw(id, btn) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
    btn.innerHTML = input.type === 'password' 
        ? '<i class="fas fa-eye text-sm"></i>' 
        : '<i class="fas fa-eye-slash text-sm"></i>';
}

// Check URL parameters on page load to switch tabs if needed
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    
    if (tab === 'signup') {
        switchTab('signup');
    }
});

// Keyboard navigation for tabs
document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
        const currentTab = document.querySelector('[role="tab"][aria-selected="true"]');
        const tabs = Array.from(document.querySelectorAll('[role="tab"]'));
        const currentIndex = tabs.indexOf(currentTab);
        
        if (e.key === 'ArrowLeft') {
            const prevTab = tabs[currentIndex > 0 ? currentIndex - 1 : tabs.length - 1];
            prevTab.click();
        } else {
            const nextTab = tabs[currentIndex < tabs.length - 1 ? currentIndex + 1 : 0];
            nextTab.click();
        }
    }
});
</script>
@endsection
