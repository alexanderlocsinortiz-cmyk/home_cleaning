@extends('layouts.app')
@section('title', 'Sign In')

@section('content')
@php
    $benefits = [
        [
            'icon' => 'fa-shield-halved',
            'title' => 'Trusted & Reliable',
            'text' => 'Background-checked cleaning professionals',
        ],
        [
            'icon' => 'fa-calendar-check',
            'title' => 'High Quality Cleaning',
            'text' => 'Safe, effective, and premium products',
        ],
        [
            'icon' => 'fa-clock',
            'title' => 'On-Time Service',
            'text' => 'Punctual, dependable, and hassle-free',
        ],
    ];
    $loginLockout = session('login_lockout');
@endphp

<div class="auth-shell min-h-screen bg-slate-50 text-slate-950">
    <div class="grid min-h-screen lg:grid-cols-[1.16fr_0.84fr]">
        <aside class="relative hidden overflow-hidden lg:block">
            <img
                src="{{ asset('images/landing-cleaning-hero.png') }}?v=20260510"
                alt=""
                aria-hidden="true"
                class="absolute inset-0 h-full w-full object-cover object-right-bottom"
            >
            <div class="absolute inset-0 bg-white/70"></div>
            <div class="absolute inset-y-0 right-0 w-52 bg-gradient-to-r from-transparent via-slate-50/70 to-slate-50"></div>

            <div class="relative z-10 flex min-h-screen flex-col px-16 py-14">
                <a href="{{ url('/') }}" class="flex w-fit items-center gap-4 no-underline">
                    <img src="{{ asset('images/logo.png') }}?v=20260510-logo4" alt="Home Cleaning Service" class="h-20 w-auto object-contain">
                </a>

                <div class="mt-28 max-w-xl">
                    <div class="text-base font-black uppercase tracking-wide text-blue-600">A clean home, a happy home</div>
                    <h1 class="mt-6 text-6xl font-black leading-[1.04] text-slate-950">
                        Professional <span class="text-blue-600">Home Cleaning</span> Services
                    </h1>
                    <p class="mt-7 max-w-lg text-xl leading-9 text-slate-600">
                        We provide top-quality cleaning services to make your home spotless, fresh, and comfortable.
                    </p>
                </div>

                <div class="mt-12 grid max-w-lg gap-7">
                    @foreach($benefits as $benefit)
                        <div class="flex items-center gap-5">
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full border border-blue-100 bg-blue-50 text-blue-600 shadow-sm">
                                <i class="fas {{ $benefit['icon'] }}"></i>
                            </div>
                            <div>
                                <div class="text-base font-black text-slate-950">{{ $benefit['title'] }}</div>
                                <div class="mt-1 text-sm text-slate-600">{{ $benefit['text'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>

        <section class="flex min-h-screen items-center justify-center bg-slate-50 px-5 py-8 sm:px-8 lg:px-12">
            <div class="w-full max-w-[520px]">
                <div class="mb-7 flex justify-center lg:hidden">
                    <a href="{{ url('/') }}" class="flex items-center gap-3 no-underline">
                        <img src="{{ asset('images/logo.png') }}?v=20260510-logo4" alt="Home Cleaning Service" class="h-16 w-auto object-contain">
                    </a>
                </div>

                <div class="relative rounded-[28px] border border-white bg-white/95 px-7 py-9 shadow-[0_24px_70px_rgba(37,99,235,0.12)] sm:px-12">
                    <a href="{{ url('/') }}" class="absolute right-5 top-5 flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-blue-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50" title="Back to Home" aria-label="Back to Home">
                        <i class="fas fa-house"></i>
                    </a>

                    <div class="text-center">
                        <img src="{{ asset('images/logo.png') }}?v=20260510-logo4" alt="Home Cleaning Service" class="mx-auto h-24 w-auto object-contain">
                        <h2 class="mt-6 text-3xl font-black tracking-tight text-slate-950">Welcome Back</h2>
                        <p class="mt-3 text-base text-slate-500">Sign in to manage your bookings and account</p>
                    </div>

                    @if ($errors->any())
                        <div class="mt-7 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <i class="fas fa-circle-exclamation mt-0.5 text-red-500"></i>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="mt-7 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            <i class="fas fa-circle-check mt-0.5 text-emerald-600"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    <div id="login-lockout-countdown" class="mt-7 hidden items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        <i class="fas fa-hourglass-half mt-0.5 text-amber-600"></i>
                        <span>
                            Too many incorrect login attempts. Try again in
                            <strong id="login-lockout-time">1:00</strong>.
                        </span>
                    </div>

                    <form action="{{ route('login.store') }}" method="POST" class="mt-8 space-y-6">
                        @csrf

                        <div>
                            <label for="email" class="mb-3 block text-sm font-bold text-slate-800">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-5 text-blue-600">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    autocomplete="email"
                                    placeholder="Enter your email"
                                    required
                                    autofocus
                                    class="h-16 w-full rounded-2xl border border-slate-200 bg-white pl-14 pr-5 text-base text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                            </div>
                            @error('email')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="mb-3 block text-sm font-bold text-slate-800">
                                Password <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-5 text-blue-600">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    autocomplete="current-password"
                                    placeholder="Enter your password"
                                    required
                                    class="h-16 w-full rounded-2xl border border-slate-200 bg-white pl-14 pr-14 text-base text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                                <button type="button" onclick="togglePw('password', this)" class="absolute inset-y-0 right-0 flex items-center pr-5 text-slate-400 transition hover:text-slate-700" aria-label="Toggle password visibility">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-between gap-4">
                            <label class="inline-flex cursor-pointer items-center gap-3 text-sm text-slate-600">
                                <input type="checkbox" name="remember" class="h-5 w-5 rounded border-slate-300 text-blue-600" @checked(old('remember'))>
                                <span>Remember me</span>
                            </label>
                            <a href="#" class="text-sm font-semibold text-blue-600 transition hover:text-blue-700">Forgot password?</a>
                        </div>

                        <button id="login-submit-button" type="submit" class="flex h-16 w-full items-center justify-center gap-3 rounded-2xl bg-blue-600 text-base font-bold text-white shadow-[0_14px_28px_rgba(37,99,235,0.28)] transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-400 disabled:shadow-none">
                            <i class="fas fa-arrow-right"></i>
                            <span>Sign In</span>
                        </button>
                    </form>

                    <div class="mt-8 flex items-center gap-4 text-sm text-slate-400">
                        <div class="h-px flex-1 bg-slate-200"></div>
                        <span>New here?</span>
                        <div class="h-px flex-1 bg-slate-200"></div>
                    </div>

                    <div class="mt-7 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-center text-sm text-slate-600">
                        Don't have an account?
                        <a href="{{ route('register') }}" class="font-bold text-blue-600 transition hover:text-blue-700">Create one here</a>
                    </div>

                    <div class="mt-7 flex items-center justify-center gap-2 text-sm text-slate-400">
                        <i class="fas fa-lock"></i>
                        <span>Your information is secure and encrypted</span>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
const loginLockoutFromServer = @json($loginLockout);
const loginLockoutStorageKey = 'cleanflow.login.lockout';
let loginLockoutTimer = null;

function togglePw(id, btn) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
    btn.innerHTML = input.type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
}

function getStoredLoginLockout() {
    try {
        return JSON.parse(localStorage.getItem(loginLockoutStorageKey));
    } catch (error) {
        localStorage.removeItem(loginLockoutStorageKey);
        return null;
    }
}

function saveLoginLockout(lockout) {
    if (!lockout || !lockout.ends_at || lockout.ends_at <= Math.floor(Date.now() / 1000)) {
        return;
    }

    localStorage.setItem(loginLockoutStorageKey, JSON.stringify(lockout));
}

function formatLockoutTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;

    return `${minutes}:${String(remainingSeconds).padStart(2, '0')}`;
}

function setLoginLocked(isLocked) {
    const submitButton = document.getElementById('login-submit-button');

    if (submitButton) {
        submitButton.disabled = isLocked;
    }
}

function hideLoginLockout() {
    const countdown = document.getElementById('login-lockout-countdown');

    if (countdown) {
        countdown.classList.add('hidden');
        countdown.classList.remove('flex');
    }

    setLoginLocked(false);
}

function showLoginLockout(lockout) {
    const countdown = document.getElementById('login-lockout-countdown');
    const time = document.getElementById('login-lockout-time');
    const email = document.getElementById('email');

    if (!countdown || !time || !email || !lockout || !lockout.ends_at) {
        return;
    }

    if (!email.value && lockout.email) {
        email.value = lockout.email;
    }

    const updateCountdown = () => {
        const activeEmail = email.value.trim().toLowerCase();
        const lockoutEmail = String(lockout.email || '').trim().toLowerCase();
        const lockoutMatchesInput = !activeEmail || !lockoutEmail || activeEmail === lockoutEmail;
        const secondsLeft = Math.max(0, lockout.ends_at - Math.floor(Date.now() / 1000));

        if (secondsLeft <= 0) {
            localStorage.removeItem(loginLockoutStorageKey);
            clearInterval(loginLockoutTimer);
            hideLoginLockout();
            return;
        }

        if (!lockoutMatchesInput) {
            hideLoginLockout();
            return;
        }

        time.textContent = formatLockoutTime(secondsLeft);
        countdown.classList.remove('hidden');
        countdown.classList.add('flex');
        setLoginLocked(true);
    };

    clearInterval(loginLockoutTimer);
    updateCountdown();
    loginLockoutTimer = setInterval(updateCountdown, 1000);
}

document.addEventListener('DOMContentLoaded', () => {
    if (loginLockoutFromServer) {
        saveLoginLockout(loginLockoutFromServer);
    }

    const lockout = loginLockoutFromServer || getStoredLoginLockout();
    const email = document.getElementById('email');

    if (lockout) {
        showLoginLockout(lockout);
    }

    if (email) {
        email.addEventListener('input', () => {
            const storedLockout = getStoredLoginLockout();

            if (storedLockout) {
                showLoginLockout(storedLockout);
            }
        });
    }
});
</script>
@endsection
