@extends('layouts.email')

@section('email-tone', 'amber')
@section('email-title', 'Service Completed')
@section('email-subtitle', 'Your cleaning visit is complete and ready for review.')

@section('content')
@php
    $rows = [
        ['label' => 'Booking #', 'value' => 'CF-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT)],
        ['label' => 'Service', 'value' => e($booking->service_label)],
        ['label' => 'Assigned Cleaner', 'value' => e($booking->staff?->full_name ?? 'Not assigned')],
        ['label' => 'Amount Paid', 'value' => '&#8369;' . number_format($booking->price, 2)],
    ];
@endphp

<p>Hi <strong>{{ $booking->user->first_name }}</strong>,</p>
<p>Your cleaning service has been <strong>completed</strong>. If everything looks good, please leave a rating so the team can track service quality.</p>

@include('emails.partials.booking-summary', [
    'rows' => $rows,
    'statusLabel' => 'Completed',
    'statusTone' => 'success',
])

<div class="callout callout--info">
    Your feedback helps the admin team monitor service quality, cleaner performance, and repeat-booking readiness.
</div>

@include('emails.partials.cta-button', [
    'url' => url('/bookings/' . $booking->id),
    'label' => 'Rate This Service',
    'tone' => 'amber',
])

<p class="muted-note">Thank you for choosing Home Cleaning Service. We look forward to serving you again.</p>
@endsection
