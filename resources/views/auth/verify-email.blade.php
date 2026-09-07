@extends('layouts.app')
@section('title', 'Verify Email - Home Cleaning Service')

@section('content')
@php
    $benefits = [
        [
            'icon' => 'fa-shield-halved',
            'title' => 'Secure Account Access',
            'text' => 'Verification protects your booking and profile details',
        ],
        [
            'icon' => 'fa-envelope-circle-check',
            'title' => 'Email Updates',
            'text' => 'Receive booking confirmations and service notifications',
        ],
        [
            'icon' => 'fa-clock',
            'title' => 'Quick Activation',
            'text' => 'Codes expire in '.($codeExpiresInMinutes ?? config('auth.verification.expire', 15)).' minutes',
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
                        Verify Your <span class="text-blue-600">Email</span> Address
                    </h1>
                    <p class="mt-7 max-w-lg text-xl leading-9 text-slate-600">
                        One final check keeps your account secure before you manage bookings and service updates.
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
                        <h2 class="mt-6 text-3xl font-black tracking-tight text-slate-950">Verify Email</h2>
                        <p class="mt-3 text-base leading-7 text-slate-500">
                            Enter the 6-digit code sent to
                            <strong class="font-bold text-slate-800">{{ auth()->user()->email }}</strong>.
                        </p>
                    </div>

                    @if(session('success'))
                        <div class="mt-7 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            <i class="fas fa-circle-check mt-0.5 text-emerald-600"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mt-7 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <i class="fas fa-circle-exclamation mt-0.5 text-red-500"></i>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('verification.verify') }}" class="mt-8 space-y-6">
                        @csrf

                        <div>
                            <label for="code" class="mb-3 block text-sm font-bold text-slate-800">
                                Verification Code <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-5 text-blue-600">
                                    <i class="fas fa-key"></i>
                                </span>
                                <input
                                    id="code"
                                    type="text"
                                    name="code"
                                    value="{{ old('code') }}"
                                    inputmode="numeric"
                                    autocomplete="one-time-code"
                                    maxlength="6"
                                    pattern="[0-9]{6}"
                                    placeholder="Enter 6-digit code"
                                    required
                                    autofocus
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6)"
                                    class="h-16 w-full rounded-2xl border border-slate-200 bg-white pl-14 pr-5 text-center text-lg font-bold tracking-[0.28em] text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                                >
                            </div>
                            <div class="mt-2 text-sm text-slate-500">
                                Codes expire in {{ $codeExpiresInMinutes ?? config('auth.verification.expire', 15) }} minutes.
                            </div>
                        </div>

                        <button type="submit" class="flex h-16 w-full items-center justify-center gap-3 rounded-2xl bg-blue-600 text-base font-bold text-white shadow-[0_14px_28px_rgba(37,99,235,0.28)] transition hover:bg-blue-700">
                            <i class="fas fa-shield-halved"></i>
                            <span>Verify Email</span>
                        </button>
                    </form>

                    <form method="POST" action="{{ route('verification.send') }}" class="mt-5">
                        @csrf
                        <button type="submit" class="flex h-14 w-full items-center justify-center gap-3 rounded-2xl border border-blue-200 bg-blue-50 text-base font-bold text-blue-700 transition hover:border-blue-300 hover:bg-blue-100">
                            <i class="fas fa-paper-plane"></i>
                            <span>Send New Verification Code</span>
                        </button>
                    </form>

                    <div class="mt-8 flex items-center gap-4 text-sm text-slate-400">
                        <div class="h-px flex-1 bg-slate-200"></div>
                        <span>Need to leave?</span>
                        <div class="h-px flex-1 bg-slate-200"></div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="mt-7 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-center text-sm text-slate-600">
                        @csrf
                        <button type="submit" class="font-bold text-blue-600 transition hover:text-blue-700">
                            Logout
                        </button>
                    </form>

                    <div class="mt-7 flex items-center justify-center gap-2 text-sm text-slate-400">
                        <i class="fas fa-lock"></i>
                        <span>Your information is secure and encrypted</span>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
