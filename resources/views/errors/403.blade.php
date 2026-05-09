@extends('layouts.app')
@section('title', 'Access Denied')

@section('content')
<div class="min-h-screen bg-slate-950">
    <div class="relative mx-auto flex min-h-screen max-w-4xl items-center justify-center px-4 py-8 sm:px-6 lg:px-8">
        <div class="w-full max-w-xl rounded-[30px] border border-white/80 bg-white/95 p-6 text-center shadow-[0_26px_70px_rgba(15,23,42,0.20)] ring-1 ring-black/5 backdrop-blur-sm sm:p-8">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-red-600 text-2xl text-white shadow-sm">
                <i class="fas fa-ban"></i>
            </div>

            <div class="mt-5 text-[11px] font-semibold uppercase tracking-[0.18em] text-red-600">403 Error</div>
            <h1 class="mt-3 text-3xl font-bold text-slate-800">Access Denied</h1>
            <p class="mt-3 text-sm leading-7 text-slate-500">
                You do not have permission to open this page from your current account or workflow.
            </p>

            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-left text-sm leading-7 text-slate-600">
                If you expected to see this page, check that you are signed in with the correct role and that the route belongs to your current portal.
            </div>

            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
                <a href="{{ route('home') }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-primary-700">
                    <i class="fas fa-home"></i>
                    <span>Go Home</span>
                </a>
                <a href="{{ url()->previous() }}" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                    <i class="fas fa-arrow-left"></i>
                    <span>Go Back</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
