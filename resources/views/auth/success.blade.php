@extends('layouts.app')
@section('title', 'Registration Successful')

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
                        <h1 class="text-4xl font-bold leading-tight tracking-tight">Your account is ready for the next step.</h1>
                        <p class="mt-4 max-w-lg text-sm leading-7 text-white/80">
                            Registration is complete. You can now explore service areas, sign in, and continue into the booking flow with a cleaner client experience.
                        </p>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3.5">
                        <div class="text-sm font-semibold text-white">Account created successfully</div>
                        <div class="mt-1 text-xs leading-5 text-white/75">Your client profile is now part of the platform and ready for email verification and booking access.</div>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3.5">
                        <div class="text-sm font-semibold text-white">Next: sign in and verify</div>
                        <div class="mt-1 text-xs leading-5 text-white/75">Once your email is verified, you can continue into the live client portal and booking features.</div>
                    </div>
                </div>
            </aside>

            <section class="flex items-center justify-center">
                <div class="w-full max-w-md rounded-[30px] border border-white/80 bg-white/95 p-6 shadow-[0_26px_70px_rgba(15,23,42,0.20)] ring-1 ring-black/5 backdrop-blur-sm sm:p-7">
                    <div class="flex justify-center">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary-600 text-2xl text-white shadow-sm">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>

                    <div class="mt-5 text-center">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-primary-600">Registration Complete</div>
                        <h2 class="mt-3 text-2xl font-bold text-slate-800">Welcome to Home Cleaning Service</h2>
                        <p class="mt-3 text-sm leading-6 text-slate-500">
                            Your account has been created successfully. You can now review service coverage or head back to the main site and continue into the client journey.
                        </p>
                    </div>

                    <div class="mt-6 grid gap-3">
                        <a href="{{ route('home') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-600 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
                            <i class="fas fa-home"></i>
                            <span>Go to Homepage</span>
                        </a>
                        <a href="{{ route('map') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-primary-200 bg-white py-3 text-sm font-semibold text-primary-700 transition hover:bg-primary-50">
                            <i class="fas fa-map-marked-alt"></i>
                            <span>View Service Areas</span>
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                            <i class="fas fa-right-to-bracket"></i>
                            <span>Sign In</span>
                        </a>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
