@extends('layouts.email')

@section('email-tone', 'emerald')
@section('email-title', 'Booking Submitted')
@section('email-subtitle', 'We received your request and will review it shortly.')

@section('content')
@php
    $formattedBarangay = \Illuminate\Support\Str::of($booking->barangay)->replace('_', ' ')->title();
    $rows = [
        ['label' => 'Booking #', 'value' => 'CF-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT)],
        ['label' => 'Service', 'value' => e($booking->service_label)],
        ['label' => 'Address', 'value' => e($booking->street_address . ', ' . $formattedBarangay)],
        ['label' => 'Scheduled Date', 'value' => e(\Carbon\Carbon::parse($booking->scheduled_date)->format('F d, Y'))],
        ['label' => 'Scheduled Time', 'value' => e(\Carbon\Carbon::parse($booking->scheduled_time)->format('h:i A'))],
        ['label' => 'Price', 'value' => '&#8369;' . number_format($booking->price, 2)],
        ['label' => 'Payment Method', 'value' => e(\App\Models\Booking::paymentMethodLabel($booking->payment_method))],
        ['label' => 'Payment Status', 'value' => e(\App\Models\Booking::paymentStatusLabel($booking->payment_status))],
        ['label' => 'Service Plan', 'value' => e(\App\Models\Booking::servicePlanLabel($booking->service_plan))],
    ];

    if ($booking->preferredStaff) {
        array_splice($rows, 5, 0, [[
            'label' => 'Preferred Cleaner',
            'value' => e($booking->preferredStaff->full_name),
        ]]);
    }

    if ($booking->payment_reference) {
        $rows[] = ['label' => 'Payment Reference', 'value' => e($booking->payment_reference)];
    }

    if ($booking->isSubscription()) {
        $rows[] = ['label' => 'Recurring Schedule', 'value' => e($booking->subscriptionSummary())];
    }
@endphp

<p>Hi <strong>{{ $booking->user->first_name }}</strong>,</p>
<p>Your booking request is now in the queue. The admin team will review your schedule, confirm staffing, and send a follow-up update once everything is finalized.</p>

@include('emails.partials.booking-summary', [
    'rows' => $rows,
    'statusLabel' => 'Pending',
    'statusTone' => 'pending',
])

@if($booking->preferredStaff && $booking->preferred_staff_status === 'requested')
    <div class="callout callout--info">
        We noted your preferred cleaner request for <strong>{{ $booking->preferredStaff->full_name }}</strong>. If that cleaner is still available at your selected time, we will prioritize the request during confirmation.
    </div>
@elseif($booking->preferredStaff && $booking->preferred_staff_status === 'unavailable')
    <div class="callout callout--warning">
        Your preferred cleaner <strong>{{ $booking->preferredStaff->full_name }}</strong> is already booked at that time, so we will assign another available cleaner during confirmation.
    </div>
@endif

@if($booking->isSubscription())
    <div class="callout callout--info">
        This booking is part of your <strong>{{ strtolower($booking->subscriptionSummary()) }}</strong> plan. Future visits will be grouped under the same subscription schedule.
    </div>
@endif

<p class="muted-note">
    @if($booking->payment_method === 'on_site_cash')
        Cash payment stays pending until the visit is completed and recorded by the admin team.
    @else
        Your digital payment has been recorded{{ $booking->payment_reference ? ' under reference ' . $booking->payment_reference : '' }}.
    @endif
</p>

@include('emails.partials.cta-button', [
    'url' => url('/bookings/' . $booking->id),
    'label' => 'Open Booking',
    'tone' => 'emerald',
])
@endsection
