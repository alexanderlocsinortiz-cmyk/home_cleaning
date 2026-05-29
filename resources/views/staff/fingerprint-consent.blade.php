@extends('layouts.staff')
@section('title', 'Fingerprint Consent - Home Cleaning Service')
@section('page-title', 'Fingerprint Consent')
@section('page-subtitle', 'Review biometric attendance terms before enrollment')

@section('content')
@php
    $alreadyAccepted = $enrollmentRequest->status !== 'awaiting_consent';
@endphp

<div class="cleanflow-page-shell min-h-[calc(100vh-81px)] px-4 py-6 sm:px-6 sm:py-8">
    <div class="mx-auto max-w-4xl space-y-6">
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
            <div class="border-b border-slate-100 px-6 py-5">
                <div class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.14em] text-blue-700">
                    <i class="fas fa-fingerprint"></i>
                    Biometric enrollment
                </div>
                <h1 class="mt-4 text-2xl font-black text-slate-900">Fingerprint Attendance Terms and Agreement</h1>
                <p class="mt-2 text-sm leading-7 text-slate-500">
                    Admin requested to enroll your fingerprint for attendance verification. Review these terms before the enrollment can continue.
                </p>
            </div>

            <div class="grid gap-5 px-6 py-6 lg:grid-cols-[1fr_0.8fr]">
                <div class="space-y-4 text-sm leading-7 text-slate-600">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <h2 class="text-base font-bold text-slate-900">What you are agreeing to</h2>
                        <ul class="mt-3 list-disc space-y-2 pl-5">
                            <li>Your fingerprint template will be used only for staff attendance time-in and time-out verification.</li>
                            <li>The system stores the assigned fingerprint slot number, not a photo of your finger.</li>
                            <li>The fingerprint device may match your scan against the enrolled template when recording attendance.</li>
                            <li>Admin can continue enrollment only after you accept this agreement.</li>
                            <li>You may ask management how your attendance data is used, corrected, or removed when employment policies allow it.</li>
                        </ul>
                    </div>

                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-amber-900">
                        <h2 class="text-base font-bold">Important</h2>
                        <p class="mt-2">
                            Do not accept if you do not understand the purpose of enrollment. Contact admin first before continuing.
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
                            <span class="text-slate-500">Status</span>
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $alreadyAccepted ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                {{ $alreadyAccepted ? 'Accepted' : 'Waiting for consent' }}
                            </span>
                        </div>
                    </div>

                    @if (! $alreadyAccepted)
                        <form method="POST" action="{{ route('staff.fingerprint-consent.accept', $enrollmentRequest) }}" class="mt-5 space-y-4">
                            @csrf
                            <label class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                <input type="checkbox" name="accept_terms" value="1" class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500" required>
                                <span>I have read and accept the fingerprint attendance terms and agreement.</span>
                            </label>
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-blue-700">
                                <i class="fas fa-check-circle"></i>
                                Accept Terms
                            </button>
                        </form>
                    @else
                        <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                            <i class="fas fa-circle-check mr-1"></i>
                            Terms accepted. Wait for admin to continue enrollment.
                        </div>
                    @endif
                </aside>
            </div>
        </section>
    </div>
</div>
@endsection
