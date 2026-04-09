@extends('layouts.client')
@section('title','Book a Service')

@section('content')
<div style="background:#fff;border:1px solid var(--border);border-radius:12px;box-shadow:var(--shadow);padding:1.5rem;">
    <h2 style="margin-top:0;">Book a Service</h2>
    <p style="color:var(--text-gray);">Booking form coming soon. In the meantime, explore service areas.</p>
    <a href="{{ route('map') }}" class="btn btn-primary"><i class="fas fa-map-marked-alt"></i> View Service Areas</a>
</div>
@endsection

