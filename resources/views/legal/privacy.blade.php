@extends('layouts.app')

@section('title', 'Privacy Policy')

@section('content')
<div class="cleanflow-page-shell px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-5xl space-y-6">
        <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8">
            <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl">
                    <span class="cleanflow-kicker">
                        <i class="fas fa-user-shield"></i>
                        Legal
                    </span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Privacy Policy</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-white/82 sm:text-base">
                        This page explains how account, booking, location, payment, and proof-of-service data are handled inside the platform.
                    </p>
                </div>
                <div class="rounded-3xl border border-white/18 bg-white/10 px-5 py-4 shadow-[0_18px_40px_rgba(15,23,42,0.15)] backdrop-blur xl:min-w-[260px]">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Privacy Scope</div>
                    <div class="mt-2 text-sm leading-7 text-white/82">Covers what data is stored, how it is used, who can access it, and how service-related records are handled.</div>
                </div>
            </div>
        </section>

        <section class="cleanflow-panel overflow-hidden">
            <div class="space-y-6 px-6 py-6 text-sm leading-7 text-slate-600 sm:px-8">
                <section>
                    <h2 class="text-lg font-extrabold text-slate-900">1. Information Collected</h2>
                    <p class="mt-2">The platform stores account details, contact information, booking data, preferred cleaner requests, payment and subscription details, proof-of-service uploads, and optional rating submissions needed to operate the service.</p>
                </section>

                <section class="border-t border-slate-100 pt-6">
                    <h2 class="text-lg font-extrabold text-slate-900">2. How Information Is Used</h2>
                    <p class="mt-2">Your information is used to create accounts, manage one-time and recurring bookings, assign staff, track payment status, send service updates, and improve service quality.</p>
                </section>

                <section class="border-t border-slate-100 pt-6">
                    <h2 class="text-lg font-extrabold text-slate-900">3. Location Data</h2>
                    <p class="mt-2">During active bookings, assigned staff may share live location data so clients and admins can monitor service progress. Location sharing is limited to active service use cases.</p>
                </section>

                <section class="border-t border-slate-100 pt-6">
                    <h2 class="text-lg font-extrabold text-slate-900">4. Proof of Service Records</h2>
                    <p class="mt-2">During assigned jobs, staff may upload before-service photos, after-service photos, and an optional completion video as part of the booking history shared with clients and admins.</p>
                </section>

                <section class="border-t border-slate-100 pt-6">
                    <h2 class="text-lg font-extrabold text-slate-900">5. Data Access</h2>
                    <p class="mt-2">Access to booking and account information is restricted by role-based permissions inside the application.</p>
                </section>

                <section class="border-t border-slate-100 pt-6">
                    <h2 class="text-lg font-extrabold text-slate-900">6. Contact</h2>
                    <p class="mt-2">If you need changes to your account information or have privacy questions, please contact the service administrator.</p>
                </section>
            </div>
        </section>
    </div>
</div>
@endsection
