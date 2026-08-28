@extends('layouts.app')

@section('title', 'Activate Cleaner Account')

@section('content')
<section class="min-h-screen bg-slate-50 px-5 py-12">
    <div class="mx-auto max-w-xl">
        <div class="rounded-2xl border border-slate-200 bg-white p-7 shadow-sm sm:p-9">
            <div class="text-center">
                <img src="{{ asset('images/logo.png') }}?v=20260510-logo4" alt="Home Cleaning Service" class="mx-auto h-20 w-auto object-contain">
                <h1 class="mt-6 text-3xl font-black text-slate-950">Create Cleaner Account</h1>
                <p class="mt-3 text-sm leading-7 text-slate-500">
                    Activate access for <strong class="text-slate-800">{{ $application->business_name }}</strong>.
                </p>
            </div>

            @if($errors->any())
                <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="mt-6 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm">
                <div class="font-bold text-blue-800">Approved cleaner</div>
                <div class="mt-2 grid gap-2 text-slate-700">
                    <div><span class="font-semibold">Email:</span> {{ $application->email }}</div>
                    <div><span class="font-semibold">Type:</span> {{ $application->isTeam() ? 'Cleaning Team / Business' : 'Individual Cleaner' }}</div>
                    <div><span class="font-semibold">Service Area:</span> {{ $application->service_area }}</div>
                </div>
            </div>

            <form method="POST" action="{{ route('provider.activate.store', ['token' => $token]) }}" class="mt-7 space-y-5">
                @csrf

                <div>
                    <label for="username" class="block text-sm font-bold text-slate-800">Username</label>
                    <input id="username" name="username" value="{{ old('username') }}" minlength="5" maxlength="20" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" placeholder="Optional">
                    <div class="mt-1 text-xs text-slate-500">Optional, but useful if the cleaner logs in often.</div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-bold text-slate-800">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-bold text-slate-800">Confirm Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                </div>

                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-700">
                    <i class="fas fa-user-check"></i>
                    Activate Cleaner Account
                </button>
            </form>
        </div>
    </div>
</section>
@endsection
