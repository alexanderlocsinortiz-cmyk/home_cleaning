@extends('layouts.app')

@section('title', 'Terms of Service')

@section('content')
<div class="cleanflow-page-shell px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-5xl space-y-6">
        <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8">
            <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl">
                    <span class="cleanflow-kicker">
                        <i class="fas fa-scale-balanced"></i>
                        Legal
                    </span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Terms of Service</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-white/82 sm:text-base">
                        These terms explain how booking requests, payment handling, cancellations, and platform use are governed inside Home Cleaning Service.
                    </p>
                </div>
                <div class="rounded-3xl border border-white/18 bg-white/10 px-5 py-4 shadow-[0_18px_40px_rgba(15,23,42,0.15)] backdrop-blur xl:min-w-[260px]">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Document Scope</div>
                    <div class="mt-2 text-sm leading-7 text-white/82">Covers booking requests, customer responsibilities, pricing, cancellations, and acceptable platform conduct.</div>
                </div>
            </div>
        </section>

        <section class="cleanflow-panel overflow-hidden">
            <div class="space-y-6 px-6 py-6 text-sm leading-7 text-slate-600 sm:px-8">
                <section>
                    <h2 class="text-lg font-extrabold text-slate-900">1. Booking Requests</h2>
                    <p class="mt-2">Submitting a booking creates a service request. One-time and subscription bookings are not final until they are reviewed and confirmed by the Home Cleaning Service team, and any preferred cleaner request is treated as a preference based on availability.</p>
                </section>

                <section class="border-t border-slate-100 pt-6">
                    <h2 class="text-lg font-extrabold text-slate-900">2. Customer Responsibilities</h2>
                    <p class="mt-2">You agree to provide accurate contact details, a valid service address, and safe access to the property at the scheduled time.</p>
                </section>

                <section class="border-t border-slate-100 pt-6">
                    <h2 class="text-lg font-extrabold text-slate-900">3. Pricing and Payment</h2>
                    <p class="mt-2">Displayed pricing is based on the information provided during booking. Final charges may reflect confirmed service details and approved add-ons. Supported payment options may include cash on service day, GCash, Maya, or bank transfer, and payment status is tracked through the platform.</p>
                </section>

                <section class="border-t border-slate-100 pt-6">
                    <h2 class="text-lg font-extrabold text-slate-900">4. Cancellations</h2>
                    <p class="mt-2">Pending bookings may be cancelled through the platform. Once a booking has been confirmed, a staff member has been assigned, or a recurring schedule has been created, cancellation or rescheduling options may be more limited.</p>
                </section>

                <section class="border-t border-slate-100 pt-6">
                    <h2 class="text-lg font-extrabold text-slate-900">5. Platform Conduct</h2>
                    <p class="mt-2">You agree not to misuse the website, attempt unauthorized access, or submit false or harmful information through any form or account.</p>
                </section>
            </div>
        </section>
    </div>
</div>
@endsection
