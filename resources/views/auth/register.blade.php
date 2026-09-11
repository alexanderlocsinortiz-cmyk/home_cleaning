@extends('layouts.app')
@section('title', 'Register')

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
@endphp

<div class="auth-shell min-h-screen bg-slate-50 text-slate-950">
    <div class="grid min-h-screen lg:grid-cols-[1.16fr_0.84fr]">
        <aside class="relative hidden overflow-hidden lg:block">
            <img
                src="{{ asset('images/optimized/landing-cleaning-hero.jpg') }}?v=20260907"
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
                        Create your account to book cleaning services and manage every request in one place.
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
            <div class="w-full max-w-[560px]">
                <div class="mb-7 flex justify-center lg:hidden">
                    <a href="{{ url('/') }}" class="flex items-center gap-3 no-underline">
                        <img src="{{ asset('images/logo.png') }}?v=20260510-logo4" alt="Home Cleaning Service" class="h-16 w-auto object-contain">
                    </a>
                </div>

                <div class="relative rounded-[28px] border border-white bg-white/95 px-7 py-8 shadow-[0_24px_70px_rgba(37,99,235,0.12)] sm:px-10">
                    <a href="{{ url('/') }}" class="absolute right-5 top-5 flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-blue-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50" title="Back to Home" aria-label="Back to Home">
                        <i class="fas fa-house"></i>
                    </a>

                    <div class="text-center">
                        <img src="{{ asset('images/logo.png') }}?v=20260510-logo4" alt="Home Cleaning Service" class="mx-auto h-24 w-auto object-contain">
                        <h2 class="mt-5 text-3xl font-black tracking-tight text-slate-950">Create Account</h2>
                        <p class="mt-3 text-base text-slate-500">Start booking professional home cleaning services</p>
                    </div>

                    @if ($errors->any())
                        <div class="mt-6 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <i class="fas fa-circle-exclamation mt-0.5 text-red-500"></i>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <form action="{{ route('register.store') }}" method="POST" class="mt-7 space-y-5">
                        @csrf

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="first_name" class="mb-3 block text-sm font-bold text-slate-800">First Name <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-5 text-blue-600"><i class="fas fa-user"></i></span>
                                    <input id="first_name" type="text" name="first_name" value="{{ old('first_name') }}" placeholder="First name" maxlength="100" required autocomplete="given-name" class="h-14 w-full rounded-2xl border border-slate-200 bg-white pl-14 pr-4 text-base text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                                </div>
                                @error('first_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="last_name" class="mb-3 block text-sm font-bold text-slate-800">Last Name <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-5 text-blue-600"><i class="fas fa-user"></i></span>
                                    <input id="last_name" type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Last name" maxlength="100" required autocomplete="family-name" class="h-14 w-full rounded-2xl border border-slate-200 bg-white pl-14 pr-4 text-base text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                                </div>
                                @error('last_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label for="email" class="mb-3 block text-sm font-bold text-slate-800">Email Address <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-5 text-blue-600"><i class="fas fa-envelope"></i></span>
                                <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Enter your email" maxlength="255" required autocomplete="email" class="h-14 w-full rounded-2xl border border-slate-200 bg-white pl-14 pr-4 text-base text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                            </div>
                            @error('email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="phone" class="mb-3 block text-sm font-bold text-slate-800">Phone Number <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-5 text-blue-600"><i class="fas fa-phone"></i></span>
                                    <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" placeholder="09XXXXXXXXX" required autocomplete="tel" inputmode="numeric" pattern="09[0-9]{9}" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)" title="Enter an 11-digit Philippine mobile number starting with 09" class="h-14 w-full rounded-2xl border border-slate-200 bg-white pl-14 pr-4 text-base text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                                </div>
                                @error('phone')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="date_of_birth" class="mb-3 block text-sm font-bold text-slate-800">Date of Birth <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-5 text-blue-600"><i class="fas fa-calendar"></i></span>
                                    <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" max="{{ now(config('cleanflow.attendance_timezone', config('app.timezone')))->subYears(18)->toDateString() }}" required autocomplete="bday" class="h-14 w-full rounded-2xl border border-slate-200 bg-white pl-14 pr-4 text-base text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                                </div>
                                @error('date_of_birth')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="password" class="mb-3 block text-sm font-bold text-slate-800">Password <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-5 text-blue-600"><i class="fas fa-lock"></i></span>
                                    <input id="password" type="password" name="password" placeholder="8+ characters, upper/lowercase, number and symbol" minlength="8" required autocomplete="new-password" aria-describedby="password-help" class="h-14 w-full rounded-2xl border border-slate-200 bg-white pl-14 pr-12 text-base text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                                    <button type="button" onclick="togglePw('password', this)" class="absolute inset-y-0 right-0 flex items-center pr-5 text-slate-400 transition hover:text-slate-700" aria-label="Toggle password visibility"><i class="fas fa-eye"></i></button>
                                </div>
                                @error('password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                                <p id="password-help" class="mt-2 text-xs text-slate-500">Use at least 8 characters with uppercase, lowercase, a number, and a symbol.</p>
                            </div>
                            <div>
                                <label for="password_confirmation" class="mb-3 block text-sm font-bold text-slate-800">Confirm Password <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-5 text-blue-600"><i class="fas fa-check"></i></span>
                                    <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Repeat password" minlength="8" required autocomplete="new-password" class="h-14 w-full rounded-2xl border border-slate-200 bg-white pl-14 pr-12 text-base text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                                    <button type="button" onclick="togglePw('password_confirmation', this)" class="absolute inset-y-0 right-0 flex items-center pr-5 text-slate-400 transition hover:text-slate-700" aria-label="Toggle password visibility"><i class="fas fa-eye"></i></button>
                                </div>
                            </div>
                        </div>

                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            <input type="checkbox" required class="mt-0.5 h-5 w-5 rounded border-slate-300 text-blue-600">
                            <span>I confirm I am at least 18 years old and agree to the Terms of Service and Privacy Policy.</span>
                        </label>

                        <button type="submit" class="flex h-14 w-full items-center justify-center gap-3 rounded-2xl bg-blue-600 text-base font-bold text-white shadow-[0_14px_28px_rgba(37,99,235,0.28)] transition hover:bg-blue-700">
                            <i class="fas fa-check"></i>
                            <span>Create Account</span>
                        </button>
                    </form>

                    <div class="mt-7 flex items-center gap-4 text-sm text-slate-400">
                        <div class="h-px flex-1 bg-slate-200"></div>
                        <span>Already registered?</span>
                        <div class="h-px flex-1 bg-slate-200"></div>
                    </div>

                    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-center text-sm text-slate-600">
                        Already have an account?
                        <a href="{{ route('login') }}" class="font-bold text-blue-600 transition hover:text-blue-700">Sign in here</a>
                    </div>

                    <div class="mt-6 flex items-center justify-center gap-2 text-sm text-slate-400">
                        <i class="fas fa-lock"></i>
                        <span>Your information is secure and encrypted</span>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
function togglePw(id, btn) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
    btn.innerHTML = input.type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
}
</script>
@endsection
