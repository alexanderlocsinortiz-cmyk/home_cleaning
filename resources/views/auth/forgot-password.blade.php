@extends('layouts.app')
@section('title', 'Forgot Password')

@section('content')
<div class="auth-shell min-h-screen bg-slate-50 text-slate-950">
    <div class="flex min-h-screen items-center justify-center px-5 py-8">
        <div class="w-full max-w-[520px]">
            <div class="relative rounded-[28px] border border-white bg-white/95 px-7 py-9 shadow-[0_24px_70px_rgba(37,99,235,0.12)] sm:px-12">
                <a href="{{ route('login') }}" class="absolute right-5 top-5 flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-blue-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50" title="Back to login" aria-label="Back to login">
                    <i class="fas fa-arrow-left"></i>
                </a>

                <div class="text-center">
                    <img src="{{ asset('images/logo.png') }}?v=20260510-logo4" alt="Home Cleaning Service" class="mx-auto h-24 w-auto object-contain">
                    <h2 class="mt-6 text-3xl font-black tracking-tight text-slate-950">Forgot Password</h2>
                    <p class="mt-3 text-base leading-7 text-slate-500">
                        Enter your email and we will send a 6-digit reset code.
                    </p>
                </div>

                @if($errors->any())
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

                <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-6">
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
                        <div class="mt-2 text-sm text-slate-500">
                            Reset codes expire in {{ $codeExpiresInMinutes }} minutes.
                        </div>
                    </div>

                    <button type="submit" class="flex h-16 w-full items-center justify-center gap-3 rounded-2xl bg-blue-600 text-base font-bold text-white shadow-[0_14px_28px_rgba(37,99,235,0.28)] transition hover:bg-blue-700">
                        <i class="fas fa-paper-plane"></i>
                        <span>Send Reset Code</span>
                    </button>
                </form>

                <div class="mt-7 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-center text-sm text-slate-600">
                    Remembered your password?
                    <a href="{{ route('login') }}" class="font-bold text-blue-600 transition hover:text-blue-700">Sign in</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
