@extends('layouts.email')

@section('email-tone', 'emerald')
@section('email-title', 'Provider Payout Paid')
@section('email-subtitle', 'CleanFlow has marked your provider payout as paid.')

@section('content')
@php
    $rows = [
        ['label' => 'Booking #', 'value' => 'CF-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT)],
        ['label' => 'Service', 'value' => e($booking->service_label)],
        ['label' => 'Provider', 'value' => e($provider->business_name)],
        ['label' => 'Payout Amount', 'value' => 'PHP ' . number_format((float) $booking->provider_payout_amount, 2)],
        ['label' => 'Reference', 'value' => e($booking->provider_payout_reference ?: 'Not provided')],
        ['label' => 'Paid Date', 'value' => e($booking->provider_payout_paid_at?->format('F d, Y h:i A') ?: 'Not provided')],
    ];
@endphp

<p>Hi <strong>{{ $provider->contact_person }}</strong>,</p>
<p>CleanFlow has marked the provider payout for this completed booking as <strong>paid</strong>.</p>

@include('emails.partials.booking-summary', [
    'rows' => $rows,
    'statusLabel' => 'Paid',
    'statusTone' => 'success',
])

<div class="callout callout--success">
    Keep the payout reference for your records. If the amount or reference does not match what you received, contact CleanFlow admin before accepting more payout updates for this booking.
</div>

<p style="margin: 24px 0;">
    <a href="{{ route('provider.payouts') }}" class="button button--primary">View Payout History</a>
</p>

<p class="muted-note">
    This notice only confirms CleanFlow's payout record. Your bank, GCash, or Maya account may still show its own processing timestamp.
</p>
@endsection
