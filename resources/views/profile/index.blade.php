@extends('layouts.app')
@section('title', 'My Profile')

@section('content')
@php
    $birthday = optional($user->date_of_birth)->format('M d, Y') ?: 'Not set';
    $gender = $user->gender ? ucfirst(str_replace('_', ' ', $user->gender)) : 'Not set';
    $address = $user->street && $user->barangay && $user->zip_code
        ? $user->street . ', ' . ($barangays[$user->barangay] ?? $user->barangay) . ', ' . $user->city . ' ' . $user->zip_code
        : 'Not set';
@endphp
<section class="form-section" style="padding-top:2rem;">
    <div class="form-container" style="max-width:960px;">
        <div class="form-header" style="align-items:flex-start;">
            <div class="form-header-icon"><i class="fas fa-user-circle"></i></div>
            <div>
                <h2>My Profile</h2>
                <p>Personal details and account information</p>
            </div>
            <div style="margin-left:auto;">
                <a href="{{ route('profile.edit') }}" class="btn btn-primary"><i class="fas fa-pen"></i> Edit Profile</a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
        @endif

        <div style="background:#fff;border:1px solid var(--border);border-radius:12px;box-shadow:var(--shadow);padding:1.25rem;">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem;color:var(--text-gray);">
                <div><strong>Full Name:</strong> {{ $user->first_name }} {{ $user->last_name }}</div>
                <div><strong>Email:</strong> {{ $user->email }}</div>
                <div><strong>Phone:</strong> {{ $user->phone ?? 'Not set' }}</div>
                <div><strong>Birthday:</strong> {{ $birthday }}</div>
                <div><strong>Gender:</strong> {{ $gender }}</div>
                <div style="grid-column:1 / -1;"><strong>Address:</strong> {{ $address }}</div>
                <div><strong>Member Since:</strong> {{ optional($user->created_at)->format('M d, Y') }}</div>
            </div>
        </div>
    </div>
</section>
@endsection

