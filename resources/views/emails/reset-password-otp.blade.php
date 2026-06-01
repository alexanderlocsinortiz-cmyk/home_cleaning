@extends('layouts.email')

@section('email-tone', 'slate')
@section('email-title', 'Reset Your Password')
@section('email-subtitle', 'Use this one-time code to set a new password.')

@section('content')
<p>Hi <strong>{{ $user->first_name }}</strong>,</p>
<p>We received a request to reset your <strong>Home Cleaning Service</strong> password. Use the code below to continue.</p>

<div class="summary-card code-card">
    <div class="code-label">Password Reset Code</div>
    <div class="code-value">{{ $code }}</div>
</div>

<div class="callout callout--info">
    This code expires in <strong>{{ $expiresInMinutes }}</strong> minutes. If it expires, request a new reset code from the forgot password screen.
</div>

<p class="muted-note">If you did not request a password reset, ignore this email and keep your current password.</p>
@endsection
