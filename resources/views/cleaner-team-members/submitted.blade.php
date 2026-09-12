@extends('layouts.app')

@section('title', 'Verification Submitted')

@section('content')
<main class="min-h-screen bg-slate-50 px-4 py-16 sm:px-6">
    <div class="mx-auto max-w-xl rounded-3xl border border-blue-200 bg-white p-8 text-center shadow-sm">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-100 text-2xl text-blue-700"><i class="fas fa-hourglass-half"></i></div>
        <h1 class="mt-5 text-2xl font-black text-slate-950">Verification submitted</h1>
        <p class="mt-3 text-sm leading-6 text-slate-600">Thank you, {{ $member->full_name }}. CleanFlow will review your documents. You cannot be assigned to a customer booking until your status becomes Approved.</p>
    </div>
</main>
@endsection
