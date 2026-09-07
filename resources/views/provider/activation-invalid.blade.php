@extends('layouts.app')

@section('title', 'Invalid Activation Link')

@section('content')
<section class="min-h-screen bg-slate-50 px-5 py-8 sm:py-12">
    <div class="mx-auto max-w-xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10">
        <div class="bg-gradient-to-br from-blue-950 via-blue-900 to-blue-700 px-7 py-8 text-center text-white sm:px-10">
            <img src="{{ asset('images/logo.png') }}?v=20260510-logo4" alt="Home Cleaning Service" class="mx-auto h-16 w-auto brightness-0 invert">
            <div class="mx-auto mt-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-white/10 text-red-200 ring-1 ring-white/15">
                <i class="fas fa-link-slash text-2xl"></i>
            </div>
            <h1 class="mt-5 text-2xl font-black sm:text-3xl">Activation link unavailable</h1>
        </div>
        <div class="p-7 text-center sm:p-10">
            <p class="text-sm leading-7 text-slate-500">
                This cleaner activation link is invalid, expired, or already used. CleanFlow does this to protect approved provider accounts.
            </p>
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-left text-sm leading-6 text-amber-800">
                <div class="font-black"><i class="fas fa-circle-info mr-2"></i>What to do next</div>
                <div class="mt-1">Contact CleanFlow admin and request a new activation link. Do not reuse an old link from email history.</div>
            </div>
            <a href="{{ route('home') }}" class="mt-7 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-700 sm:w-auto">
                <i class="fas fa-house"></i>
                Back to Home
            </a>
        </div>
    </div>
</section>
@endsection
