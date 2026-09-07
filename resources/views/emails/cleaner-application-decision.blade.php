@extends('layouts.email')

@php
    $isApproved = $application->status === \App\Models\CleanerApplication::STATUS_APPROVED;
    $needsChanges = $application->status === \App\Models\CleanerApplication::STATUS_NEEDS_CHANGES;
@endphp

@section('email-tone', $isApproved ? 'emerald' : ($needsChanges ? 'amber' : 'slate'))
@section('email-title', $isApproved ? 'Application Approved' : ($needsChanges ? 'More Information Needed' : 'Application Update'))
@section('email-subtitle', $isApproved ? 'You have been approved as a CleanFlow service provider.' : ($needsChanges ? 'CleanFlow needs a few changes before your application can be approved.' : 'Your CleanFlow service provider application has been reviewed.'))

@section('content')
<p>Hi <strong>{{ $application->contact_person }}</strong>,</p>

@if($isApproved)
    <p>Your application for <strong>{{ $application->business_name }}</strong> has been <strong>approved</strong>. CleanFlow may now consider you for marketplace provider assignment when suitable bookings are available.</p>
@elseif($needsChanges)
    <p>Thank you for applying to become a CleanFlow service provider. Please review the note below and submit an updated application with the requested information.</p>
@else
    <p>Thank you for applying to become a CleanFlow service provider. After review, your application for <strong>{{ $application->business_name }}</strong> was <strong>not approved at this time</strong>.</p>
@endif

<div class="summary-card">
    <table class="summary-table">
        <tr>
            <td class="summary-label">Applicant Type</td>
            <td class="summary-value">{{ $application->isTeam() ? 'Cleaning Team / Business' : 'Individual Cleaner' }}</td>
        </tr>
        <tr>
            <td class="summary-label">Service Area</td>
            <td class="summary-value">{{ $application->service_area }}</td>
        </tr>
        <tr>
            <td class="summary-label">Status</td>
            <td class="summary-value">
                <span class="status-pill {{ $isApproved ? 'status-pill--success' : 'status-pill--neutral' }}">
                    {{ ucfirst($application->status) }}
                </span>
            </td>
        </tr>
        @if($application->admin_notes)
            <tr>
                <td class="summary-label">Admin Notes</td>
                <td class="summary-value">{{ $application->admin_notes }}</td>
            </tr>
        @endif
    </table>
</div>

@if($isApproved)
    <div class="callout callout--success">
        Approval does not guarantee immediate booking assignment. CleanFlow still reviews service area, schedule fit, and customer requirements before assigning a provider.
    </div>
    <div class="callout callout--info">
        Use the button below to activate your provider account. This secure link expires in 7 days.
    </div>
@elseif($needsChanges)
    <div class="callout callout--info">
        Use the button below to see your application status and the requested changes. Your private tracking link expires in 1 year.
    </div>
@else
    <div class="callout callout--info">
        You may contact CleanFlow for clarification or submit a stronger application in the future with updated verification details.
    </div>
@endif

@include('emails.partials.cta-button', [
    'url' => $isApproved && $activationUrl ? $activationUrl : ($trackingUrl ?: route('cleaner-applications.create')),
    'label' => $isApproved && $activationUrl ? 'Create Provider Account' : ($needsChanges ? 'Review Requested Changes' : ($trackingUrl ? 'View Application Status' : 'Submit Another Application')),
    'tone' => $isApproved ? 'emerald' : ($needsChanges ? 'amber' : 'slate'),
])
@endsection
