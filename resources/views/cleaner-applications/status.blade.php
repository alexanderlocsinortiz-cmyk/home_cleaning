@extends('layouts.app')

@section('title', 'Cleaner Application Status')

@section('content')
@php($applicationTimezone = config('cleanflow.attendance_timezone', 'Asia/Manila'))
<section class="bg-slate-50 px-5 py-10 sm:py-16">
    <div class="mx-auto max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="text-xs font-black uppercase tracking-[0.16em] text-blue-700">Cleaner application</div>
        <h1 class="mt-2 text-3xl font-black text-slate-950">Application status</h1>
        <p class="mt-3 text-sm leading-7 text-slate-600">This secure status link is valid until {{ optional($application->tracking_token_expires_at?->copy()->timezone($applicationTimezone))->format('F j, Y') }}.</p>

        <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-5">
            <div class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">Application #{{ $application->id }}</div>
            <div class="mt-2 text-xl font-black text-slate-900">{{ $application->business_name }}</div>
            <div class="mt-4 inline-flex rounded-full px-3 py-1.5 text-xs font-black uppercase tracking-wide
                {{ match($application->status) {
                    \App\Models\CleanerApplication::STATUS_APPROVED => 'bg-emerald-100 text-emerald-800',
                    \App\Models\CleanerApplication::STATUS_REJECTED => 'bg-rose-100 text-rose-800',
                    \App\Models\CleanerApplication::STATUS_NEEDS_CHANGES => 'bg-amber-100 text-amber-800',
                    default => 'bg-blue-100 text-blue-800',
                } }}">
                {{ str_replace('_', ' ', ucfirst($application->status)) }}
            </div>
        </div>

        @if($application->status === \App\Models\CleanerApplication::STATUS_PENDING)
            <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-900">
                Your application is waiting for CleanFlow admin review. We will verify your service details and uploaded documents before making a decision.
            </div>
        @elseif($application->status === \App\Models\CleanerApplication::STATUS_NEEDS_CHANGES)
            <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                CleanFlow needs more information before this application can be approved. Review the note below, then submit an updated application.
            </div>
        @elseif($application->status === \App\Models\CleanerApplication::STATUS_APPROVED)
            <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm leading-6 text-emerald-900">
                Your application was approved. The approval email contains the secure link to activate your provider account.
            </div>
        @else
            <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-700">
                This application was not approved at this time. You may submit a new application with updated information.
            </div>
        @endif

        @if($application->admin_notes)
            <div class="mt-6 rounded-xl border border-slate-200 p-4">
                <div class="text-xs font-black uppercase tracking-[0.14em] text-slate-500">CleanFlow note</div>
                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $application->admin_notes }}</p>
            </div>
        @endif

        @if(in_array($application->status, [\App\Models\CleanerApplication::STATUS_NEEDS_CHANGES, \App\Models\CleanerApplication::STATUS_REJECTED], true))
            <a href="{{ route('cleaner-applications.create') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-black text-white hover:bg-blue-700">
                <i class="fas fa-file-circle-plus"></i>
                Submit an updated application
            </a>
        @endif
    </div>
</section>
@endsection
