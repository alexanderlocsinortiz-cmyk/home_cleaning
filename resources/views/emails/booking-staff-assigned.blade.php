@extends('layouts.email')

@section('email-tone', 'cyan')
@section('email-title', 'Cleaner Assigned')
@section('email-subtitle', 'A cleaner is now assigned to your booking schedule.')

@section('content')
@php
    $rows = [
        ['label' => 'Booking #', 'value' => 'CF-' . str_pad($booking->id, 5, '0', STR_PAD_LEFT)],
    ];

    if ($booking->preferredStaff) {
        $rows[] = ['label' => 'Preferred Cleaner', 'value' => e($booking->preferredStaff->full_name)];
    }

    $rows[] = ['label' => 'Assigned Cleaner', 'value' => e($booking->staff->full_name)];
    $rows[] = ['label' => 'Cleaner Contact', 'value' => e($booking->staff->phone ?? 'Not available')];
    $rows[] = ['label' => 'Scheduled Date', 'value' => e(\Carbon\Carbon::parse($booking->scheduled_date)->format('F d, Y'))];
    $rows[] = ['label' => 'Scheduled Time', 'value' => e(\Carbon\Carbon::parse($booking->scheduled_time)->format('h:i A'))];
@endphp

<p>Hi <strong>{{ $booking->user->first_name }}</strong>,</p>
<p>A cleaner has been assigned to your booking. Please make sure someone is available at the location on the scheduled date and time.</p>

@include('emails.partials.booking-summary', [
    'rows' => $rows,
    'statusLabel' => 'Assigned',
    'statusTone' => 'neutral',
])

@if($booking->preferredStaff && $booking->preferred_staff_status === 'assigned')
    <div class="callout callout--success">
        Your preferred cleaner <strong>{{ $booking->preferredStaff->full_name }}</strong> is the assigned cleaner for this visit.
    </div>
@elseif($booking->preferredStaff && $booking->preferred_staff_status === 'alternate_assigned')
    <div class="callout callout--warning">
        Your preferred cleaner <strong>{{ $booking->preferredStaff->full_name }}</strong> was unavailable for this schedule, so <strong>{{ $booking->staff->full_name }}</strong> has been assigned instead.
    </div>
@endif

@include('emails.partials.cta-button', [
    'url' => url('/bookings/' . $booking->id),
    'label' => 'Open Booking',
    'tone' => 'cyan',
])
@endsection
