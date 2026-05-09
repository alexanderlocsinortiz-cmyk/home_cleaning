@extends('layouts.email')

@section('email-tone', 'emerald')
@section('email-title', 'Verify Your Email')
@section('email-subtitle', 'Confirm your email address to finish activating your account.')

@section('content')
<p>Hi <strong>{{ $user->first_name }}</strong>,</p>
<p>Welcome to <strong>Home Cleaning Service</strong>. Use the verification code below to confirm your email address and activate your account.</p>

<div class="summary-card code-card">
    <div class="code-label">Verification Code</div>
    <div class="code-value">{{ $code }}</div>
</div>

<div class="callout callout--info">
    This code expires in <strong>{{ $expiresInMinutes }}</strong> minutes. If it expires, request a new one from the verification screen after signing in.
</div>

<p class="muted-note">If you did not create a Home Cleaning Service account, no further action is required.</p>
@endsection
