@extends('layouts.email')

@section('email-tone', 'slate')
@section('email-title', $subject ?? 'Notification')
@section('email-subtitle', 'A new system update is available for your account.')

@section('content')
<p>Hi <strong>{{ $recipient_name }}</strong>,</p>
<p>{{ $notificationMessage }}</p>

@if(($notification->link ?? null) || ($notification->booking_id ?? null))
    @php
        $destination = $notification->link ?: url('/bookings/' . $notification->booking_id);
        $label = match ($type) {
            'booking_status' => 'View Booking',
            'rating_request' => 'Leave a Rating',
            'staff_assignment' => 'Open Assignment',
            'payment_reminder' => 'Review Payment',
            default => 'Open Notification',
        };
    @endphp

    @include('emails.partials.cta-button', [
        'url' => $destination,
        'label' => $label,
        'tone' => 'slate',
    ])
@endif

<p class="muted-note">This notification was sent automatically from the Home Cleaning Service system.</p>
@endsection
