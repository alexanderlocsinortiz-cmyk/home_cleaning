@extends('layouts.email')

@section('email-tone', 'emerald')
@section('email-title', 'Booking Assignment')
@section('email-subtitle', 'CleanFlow has assigned your provider profile to a customer booking.')

@section('content')
@php
    $formattedBarangay = \Illuminate\Support\Str::of($booking->barangay)->replace('_', ' ')->title();
    $rows = [
        ['label' => 'Booking #', 'value' => 'CF-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT)],
        ['label' => 'Service', 'value' => e($booking->service_label)],
        ['label' => 'Service Area', 'value' => e($formattedBarangay)],
        ['label' => 'Address', 'value' => e($booking->street_address . ', ' . $formattedBarangay)],
        ['label' => 'Scheduled Date', 'value' => e(\Carbon\Carbon::parse($booking->scheduled_date)->format('F d, Y'))],
        ['label' => 'Scheduled Time', 'value' => e(\Carbon\Carbon::parse($booking->scheduled_time)->format('h:i A'))],
        ['label' => 'Duration', 'value' => e(number_format($booking->duration_minutes ?? \App\Models\Service::DEFAULT_DURATION_MINUTES) . ' minutes')],
        ['label' => 'Provider', 'value' => e($provider->business_name)],
    ];
@endphp

<p>Hi <strong>{{ $provider->contact_person }}</strong>,</p>
<p>CleanFlow has assigned <strong>{{ $provider->business_name }}</strong> as the marketplace provider for the booking below.</p>

@include('emails.partials.booking-summary', [
    'rows' => $rows,
    'statusLabel' => 'Assigned',
    'statusTone' => 'success',
])

<div class="callout callout--info">
    This email is an assignment notice, not a provider dashboard login. CleanFlow admin will continue coordinating booking details and customer communication.
</div>

<p class="muted-note">
    Please prepare for the scheduled service and contact CleanFlow if you cannot handle this assignment.
</p>
@endsection
