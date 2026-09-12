@extends('layouts.app')

@section('title', 'Verification Link Unavailable')

@section('content')
<main class="min-h-screen bg-slate-50 px-4 py-16 sm:px-6">
    <div class="mx-auto max-w-xl rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-sm">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-100 text-2xl text-amber-700"><i class="fas fa-link-slash"></i></div>
        <h1 class="mt-5 text-2xl font-black text-slate-950">Verification link unavailable</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">This link may have expired, already been submitted, or been replaced. Ask your team contact to send you a new verification link.</p>
        <a href="{{ route('home') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-black text-white hover:bg-blue-700">Return to CleanFlow</a>
    </div>
</main>
@endsection
