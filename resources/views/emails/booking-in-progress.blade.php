@extends('layouts.email')

@section('email-tone', 'purple')
@section('email-title', 'Service In Progress')
@section('email-subtitle', 'Your cleaner has arrived and the booking is now active.')

@section('content')
@php
    $formattedBarangay = \Illuminate\Support\Str::of($booking->barangay)->replace('_', ' ')->title();
    $rows = [
        ['label' => 'Booking #', 'value' => 'CF-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT)],
        ['label' => 'Service', 'value' => e($booking->service_label)],
        ['label' => 'Assigned Cleaner', 'value' => e(trim(($booking->staff->first_name ?? '') . ' ' . ($booking->staff->last_name ?? '')) ?: 'Not assigned')],
        ['label' => 'Address', 'value' => e($booking->street_address . ', ' . $formattedBarangay)],
    ];
@endphp

<p>Hi <strong>{{ $booking->user->first_name }}</strong>,</p>
<p>Your cleaning service is now <strong>in progress</strong>. The assigned cleaner is currently on site and working on your booking.</p>

@include('emails.partials.booking-summary', [
    'rows' => $rows,
    'statusLabel' => 'In Progress',
    'statusTone' => 'progress',
])

<p class="muted-note">You will receive another update once the service is completed and ready for review.</p>

@include('emails.partials.cta-button', [
    'url' => url('/bookings/' . $booking->id),
    'label' => 'Track Booking',
    'tone' => 'purple',
])
@endsection
