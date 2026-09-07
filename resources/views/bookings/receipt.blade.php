@extends('layouts.app')

@section('title', 'Payment Receipt')

@push('styles')
<style>
    @media print {
        nav, footer, .receipt-actions { display: none !important; }
        body { background: #fff !important; }
        .receipt-shell { margin: 0 !important; max-width: none !important; }
        .receipt-card { border: 0 !important; box-shadow: none !important; }
    }
</style>
@endpush

@php
    $payment = $booking->payment;
    $receiptNumber = $payment?->method === 'on_site_cash'
        ? $payment->receipt_number
        : $payment?->reference;
    $paidAt = $payment?->method === 'on_site_cash'
        ? $payment->collected_at
        : $payment?->paid_at;
@endphp

@section('content')
<main class="receipt-shell mx-auto max-w-3xl px-4 py-10 sm:px-6">
    <div class="receipt-actions mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('bookings.show', $booking->id) }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            <i class="fas fa-arrow-left"></i>
            Back to booking
        </a>
        <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-xl bg-blue-700 px-4 py-2 text-sm font-bold text-white hover:bg-blue-800">
            <i class="fas fa-print"></i>
            Print / Save as PDF
        </button>
    </div>

    <section class="receipt-card overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl">
        <div class="border-b border-slate-200 bg-slate-50 px-6 py-8 text-center sm:px-10">
            <img src="{{ $siteSettings->logo_url }}" alt="{{ $siteSettings->website_name }}" class="mx-auto mb-4 h-16 w-auto">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Payment receipt</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">{{ $siteSettings->website_name }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $siteSettings->contact_address ?: 'Valencia City, Bukidnon, Philippines' }}</p>
        </div>

        <div class="space-y-7 px-6 py-8 sm:px-10">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Receipt number</p>
                    <p class="mt-1 font-mono text-lg font-bold text-slate-900">{{ $receiptNumber }}</p>
                </div>
                <div class="text-left sm:text-right">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Booking</p>
                    <p class="mt-1 text-lg font-bold text-blue-700">CF-{{ str_pad($booking->id, 5, '0', STR_PAD_LEFT) }}</p>
                </div>
            </div>

            <div class="grid gap-5 border-y border-slate-100 py-6 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Customer</p>
                    <p class="mt-1 font-semibold text-slate-900">{{ $booking->user->full_name }}</p>
                    <p class="text-sm text-slate-500">{{ $booking->user->email }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Payment date</p>
                    <p class="mt-1 font-semibold text-slate-900">{{ $paidAt?->format('F d, Y h:i A') }}</p>
                    <p class="text-sm text-slate-500">{{ \App\Models\Booking::paymentMethodLabel($payment?->method ?? 'on_site_cash') }}</p>
                </div>
            </div>

            <div>
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-3">
                    <div>
                        <p class="font-bold text-slate-900">{{ $booking->service?->name ?: \App\Models\Service::displayNameForSlug($booking->service_type) }}</p>
                        <p class="mt-1 text-sm text-slate-500">Service scheduled for {{ $booking->scheduled_date->format('F d, Y') }} at {{ \Carbon\Carbon::parse($booking->scheduled_time)->format('h:i A') }}</p>
                    </div>
                    <p class="font-semibold text-slate-900">PHP {{ number_format((float) $booking->price, 2) }}</p>
                </div>
                <div class="flex items-center justify-between pt-4 text-lg font-bold text-slate-900">
                    <span>Total paid</span>
                    <span>PHP {{ number_format((float) $booking->price, 2) }}</span>
                </div>
            </div>

            @if($payment?->method === 'on_site_cash')
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
                    <p class="font-bold">Cash collection recorded</p>
                    <p class="mt-1">Collected by {{ $booking->payment?->collector?->full_name ?: 'CleanFlow admin' }} for the exact booking total.</p>
                    @if($booking->payment_receipt_notes)
                        <p class="mt-2"><span class="font-semibold">Notes:</span> {{ $booking->payment_receipt_notes }}</p>
                    @endif
                </div>
            @else
                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                    This receipt confirms the digital payment reference recorded for this booking.
                </div>
            @endif

            <p class="text-center text-xs leading-5 text-slate-400">Keep this digital receipt for your records. This receipt is generated from the CleanFlow booking record.</p>
        </div>
    </section>
</main>
@endsection
