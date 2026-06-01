@extends('layouts.staff')
@section('title', 'Fingerprint Enrollment Consent - Home Cleaning Service')
@section('page-title', 'Fingerprint Enrollment Consent')
@section('page-subtitle', 'Review biometric consent before fingerprint enrollment')

@section('content')
@php
    $isAwaitingConsent = $enrollmentRequest->status === 'awaiting_consent';
    $isApproved = $enrollmentRequest->status === 'consent_accepted';
    $isDeclined = $enrollmentRequest->status === 'declined';
@endphp

<div class="cleanflow-page-shell min-h-[calc(100vh-81px)] px-4 py-6 sm:px-6 sm:py-8">
    <div class="mx-auto max-w-5xl space-y-6">
        @if (session('success'))
            <div class="cleanflow-alert cleanflow-alert--success flex items-start gap-3">
                <i class="fas fa-circle-check mt-0.5 text-base"></i>
                <div>
                    <p class="text-sm font-semibold">Consent updated.</p>
                    <p class="mt-1 text-sm text-emerald-800/80">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="cleanflow-alert cleanflow-alert--danger flex items-start gap-3">
                <i class="fas fa-triangle-exclamation mt-0.5 text-base"></i>
                <div>
                    <p class="text-sm font-semibold">Please review the consent form.</p>
                    <ul class="mt-1 list-disc pl-5 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <section class="cleanflow-panel overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-6">
                <div class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.14em] text-blue-700">
                    <i class="fas fa-fingerprint"></i>
                    Staff Receives Consent
                </div>
                <h1 class="mt-4 text-3xl font-black text-slate-900">Fingerprint Enrollment Consent</h1>
                <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-500">
                    Your administrator requested fingerprint enrollment for attendance verification. Review each section before choosing whether to continue.
                </p>
            </div>

            <div class="grid gap-6 px-6 py-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <h2 class="flex items-center gap-2 text-base font-black text-slate-900">
                            <i class="fas fa-database text-blue-600"></i>
                            Data to be collected
                        </h2>
                        <p class="mt-2 text-sm leading-7 text-slate-600">
                            The system records your staff account, assigned fingerprint slot number, attendance punch time, device source, and enrollment status.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <h2 class="flex items-center gap-2 text-base font-black text-slate-900">
                            <i class="fas fa-bullseye text-blue-600"></i>
                            Purpose of collection
                        </h2>
                        <p class="mt-2 text-sm leading-7 text-slate-600">
                            Fingerprint enrollment is used only to verify staff time-in and time-out activity for attendance monitoring.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <h2 class="flex items-center gap-2 text-base font-black text-slate-900">
                            <i class="fas fa-shield-halved text-blue-600"></i>
                            Privacy policy
                        </h2>
                        <p class="mt-2 text-sm leading-7 text-slate-600">
                            Your biometric enrollment is tied to attendance operations. Ask management if you need access, correction, or removal under company policy.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <h2 class="flex items-center gap-2 text-base font-black text-slate-900">
                            <i class="fas fa-microchip text-blue-600"></i>
                            Device usage
                        </h2>
                        <p class="mt-2 text-sm leading-7 text-slate-600">
                            The fingerprint device may compare your scan with the enrolled slot when recording attendance. The admin queue stays locked until you approve.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-4 md:col-span-2">
                        <h2 class="flex items-center gap-2 text-base font-black text-blue-950">
                            <i class="fas fa-file-signature text-blue-600"></i>
                            Consent agreement
                        </h2>
                        <p class="mt-2 text-sm leading-7 text-blue-900">
                            By selecting Accept & Continue, you confirm that you understand the collection and use of fingerprint enrollment data for attendance verification.
                        </p>
                    </div>
                </div>

                <aside class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm">
                    <h2 class="text-sm font-bold uppercase tracking-[0.14em] text-slate-400">Enrollment request</h2>
                    <div class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">Device</span>
                            <span class="font-semibold text-slate-900">{{ $enrollmentRequest->device?->name ?? 'Device' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">Fingerprint slot</span>
                            <span class="font-semibold text-slate-900">#{{ $enrollmentRequest->template_id }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">Requested by</span>
                            <span class="font-semibold text-slate-900">{{ $enrollmentRequest->requestedBy?->display_name ?? 'Admin' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500">Enrollment Status</span>
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $isApproved ? 'bg-emerald-50 text-emerald-700' : ($isDeclined ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">
                                {{ $isApproved ? 'Approved' : ($isDeclined ? 'Declined' : 'Awaiting Approval') }}
                            </span>
                        </div>
                    </div>

                    @if ($isAwaitingConsent)
                        <div class="mt-5 grid gap-3">
                            <form method="POST" action="{{ route('staff.fingerprint-consent.accept', $enrollmentRequest) }}">
                                @csrf
                                <input type="hidden" name="accept_terms" value="1">
                                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-blue-700">
                                    <i class="fas fa-check-circle"></i>
                                    Accept &amp; Continue
                                </button>
                            </form>
                            <form method="POST" action="{{ route('staff.fingerprint-consent.decline', $enrollmentRequest) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                                    <i class="fas fa-xmark"></i>
                                    Decline
                                </button>
                            </form>
                        </div>
                    @elseif ($isApproved)
                        <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                            <i class="fas fa-circle-check mr-1"></i>
                            Enrollment Status: Approved. Admin can now enroll your fingerprint on the device.
                        </div>
                    @elseif ($isDeclined)
                        <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                            <i class="fas fa-circle-xmark mr-1"></i>
                            You declined fingerprint enrollment consent.
                        </div>
                    @else
                        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-600">
                            Current status: {{ ucfirst(str_replace('_', ' ', $enrollmentRequest->status)) }}.
                        </div>
                    @endif
                </aside>
            </div>
        </section>
    </div>
</div>
@endsection
