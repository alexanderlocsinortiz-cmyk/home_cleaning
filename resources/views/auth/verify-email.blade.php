@extends('layouts.app')
@section('title', 'Verify Email - Home Cleaning Service')

@section('content')
<div class="min-h-screen bg-slate-950">
    <div class="relative mx-auto flex min-h-screen max-w-6xl items-center justify-center px-4 py-8 sm:px-6 lg:px-8">
        <div class="grid w-full max-w-5xl gap-5 lg:grid-cols-[minmax(0,1fr)_440px]">
            <aside class="hidden rounded-[32px] border border-white/15 bg-white/10 p-8 text-white shadow-[0_28px_80px_rgba(15,23,42,0.22)] backdrop-blur-md lg:flex lg:flex-col lg:justify-between">
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

                    <div class="mt-8 max-w-xl">
                        <h1 class="text-4xl font-bold leading-tight tracking-tight">One more step before your account is ready.</h1>
                        <p class="mt-4 max-w-lg text-sm leading-7 text-white/80">
                            Verify your email to unlock booking access, account updates, and a safer sign-in flow across the platform.
                        </p>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3.5">
                        <div class="text-sm font-semibold text-white">Secure email-based access</div>
                        <div class="mt-1 text-xs leading-5 text-white/75">Your account stays linked to a verified email before bookings can proceed.</div>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3.5">
                        <div class="text-sm font-semibold text-white">Code expires automatically</div>
                        <div class="mt-1 text-xs leading-5 text-white/75">Verification codes expire in {{ $codeExpiresInMinutes ?? config('auth.verification.expire', 15) }} minutes for safer account activation.</div>
                    </div>
                </div>
            </aside>

            <section class="flex items-center justify-center">
                <div class="w-full max-w-md rounded-[30px] border border-white/80 bg-white/95 p-6 shadow-[0_26px_70px_rgba(15,23,42,0.20)] ring-1 ring-black/5 backdrop-blur-sm sm:p-7">
                    <div class="flex justify-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-600 text-lg text-white shadow-sm">
                            <i class="fas fa-envelope-open-text"></i>
                        </div>
                    </div>

                    <div class="mt-5 text-center">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-primary-600">Email Verification</div>
                        <h2 class="mt-3 text-2xl font-bold text-slate-800">Verify your email address</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Enter the 6-digit code sent to
                            <strong class="text-slate-800">{{ auth()->user()->email }}</strong>.
                        </p>
                    </div>

                    @if(session('success'))
                        <div class="mt-5 flex items-center gap-2 rounded-2xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-600 shadow-sm">
                            <i class="fas fa-check-circle text-success-500"></i>
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mt-5 flex items-center gap-2 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600 shadow-sm">
                            <i class="fas fa-exclamation-circle text-red-500"></i>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('verification.verify') }}" class="mt-6 space-y-4 text-left">
                        @csrf
                        <div>
                            <label for="code" class="mb-1.5 block text-sm font-medium text-slate-700">Verification Code</label>
                            <input
                                id="code"
                                type="text"
                                name="code"
                                value="{{ old('code') }}"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                maxlength="6"
                                placeholder="Enter 6-digit code"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-center text-lg font-semibold tracking-[0.35em] text-slate-800 transition focus:border-primary-500 focus:outline-hidden focus:ring-2 focus:ring-primary-200"
                            >
                            <div class="mt-2 text-xs text-slate-500">
                                Codes expire in {{ $codeExpiresInMinutes ?? config('auth.verification.expire', 15) }} minutes.
                            </div>
                        </div>

                        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-primary-600 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
                            <i class="fas fa-shield-halved"></i>
                            <span>Verify Email</span>
                        </button>
                    </form>

                    <form method="POST" action="{{ route('verification.send') }}" class="mt-4">
                        @csrf
                        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-2xl border border-primary-200 bg-white py-3 text-sm font-semibold text-primary-700 transition hover:bg-primary-50">
                            <i class="fas fa-paper-plane"></i>
                            <span>Send New Verification Code</span>
                        </button>
                    </form>

                    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-2 text-sm text-slate-400 transition hover:text-slate-600">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
