@extends('layouts.app')
@section('title', 'Registration Successful')
@section('content')
<section class="form-section">
    <div class="success-container">
        <div class="success-icon"><i class="fas fa-check-circle"></i></div>
        <h2>Registration Successful!</h2>
        <p>Welcome to Home Cleaning Service! Your account has been created successfully.</p>
        <p>You can now book professional cleaning services in Valencia City, Bukidnon.</p>
        <div class="success-btns">
            <a href="{{ route('home') }}" class="btn btn-primary"><i class="fas fa-home"></i> Go to Homepage</a>
            <a href="{{ route('map') }}" class="btn btn-outline"><i class="fas fa-map-marked-alt"></i> View Service Areas</a>
        </div>
    </div>
</section>
@endsection

