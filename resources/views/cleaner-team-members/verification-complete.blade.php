@extends('layouts.app')

@section('title', 'Verification Complete')

@section('content')
<main class="min-h-screen bg-slate-50 px-4 py-16 sm:px-6">
    <div class="mx-auto max-w-xl rounded-3xl border border-emerald-200 bg-white p-8 text-center shadow-sm">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-100 text-2xl text-emerald-700"><i class="fas fa-circle-check"></i></div>
        <h1 class="mt-5 text-2xl font-black text-slate-950">You are already approved</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">Your verification for <strong>{{ $member->cleanerApplication->business_name }}</strong> has already been approved. Your team contact can assign you to eligible bookings.</p>
    </div>
</main>
@endsection
