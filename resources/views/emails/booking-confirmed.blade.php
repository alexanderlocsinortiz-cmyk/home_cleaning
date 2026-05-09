@extends('layouts.email')

@section('email-tone', 'emerald')
@section('email-title', 'Booking Confirmed')
@section('email-subtitle', 'Your cleaning service is confirmed and ready for the scheduled visit.')

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
        $rows[] = ['label' => 'Preferred Cleaner', 'value' => e($booking->preferredStaff->full_name)];
    }

    if ($booking->staff) {
        $rows[] = ['label' => 'Assigned Cleaner', 'value' => e($booking->staff->full_name)];
    }

    if ($booking->payment_reference) {
        $rows[] = ['label' => 'Payment Reference', 'value' => e($booking->payment_reference)];
    }

    if ($booking->isSubscription()) {
        $rows[] = ['label' => 'Recurring Schedule', 'value' => e($booking->subscriptionSummary())];
    }
@endphp

<p>Hi <strong>{{ $booking->user->first_name }}</strong>,</p>
<p>Your booking has been <strong>confirmed</strong>. Please make sure the location is accessible on the scheduled date and time below.</p>

@include('emails.partials.booking-summary', [
    'rows' => $rows,
    'statusLabel' => 'Confirmed',
    'statusTone' => 'confirmed',
])

@if($booking->preferredStaff && $booking->preferred_staff_status === 'assigned')
    <div class="callout callout--success">
        Your preferred cleaner <strong>{{ $booking->preferredStaff->full_name }}</strong> has been assigned to this booking.
    </div>
@elseif($booking->preferredStaff && $booking->preferred_staff_status === 'alternate_assigned' && $booking->staff)
    <div class="callout callout--warning">
        Your preferred cleaner <strong>{{ $booking->preferredStaff->full_name }}</strong> was unavailable, so <strong>{{ $booking->staff->full_name }}</strong> has been assigned instead.
    </div>
@elseif($booking->preferredStaff && $booking->preferred_staff_status === 'requested')
    <div class="callout callout--info">
        We received your preferred cleaner request for <strong>{{ $booking->preferredStaff->full_name }}</strong> and will continue to prioritize it whenever staffing adjustments are needed.
    </div>
@endif

@if($booking->isSubscription())
    <div class="callout callout--info">
        This visit belongs to your <strong>{{ strtolower($booking->subscriptionSummary()) }}</strong> plan, so follow-up bookings have already been scheduled using the same recurring pattern.
    </div>
@endif

<p class="muted-note">
    @if($booking->payment_method === 'on_site_cash')
        Cash payment remains pending until the service is completed and recorded by the admin team.
    @else
        Your digital payment has already been recorded{{ $booking->payment_reference ? ' under reference ' . $booking->payment_reference : '' }}.
    @endif
</p>

@include('emails.partials.cta-button', [
    'url' => url('/bookings/' . $booking->id),
    'label' => 'View Booking',
    'tone' => 'emerald',
])
@endsection
