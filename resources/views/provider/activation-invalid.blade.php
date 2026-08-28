@extends('layouts.app')

@section('title', 'Invalid Activation Link')

@section('content')
<section class="min-h-screen bg-slate-50 px-5 py-12">
    <div class="mx-auto max-w-lg rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-red-50 text-red-600">
            <i class="fas fa-link-slash text-2xl"></i>
        </div>
        <h1 class="mt-6 text-2xl font-black text-slate-950">Activation Link Unavailable</h1>
        <p class="mt-3 text-sm leading-7 text-slate-500">
            This cleaner activation link is invalid, expired, or already used. Contact CleanFlow admin if you need a new activation link.
        </p>
        <a href="{{ route('home') }}" class="mt-6 inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-700">
            Back to Home
        </a>
    </div>
</section>
@endsection
