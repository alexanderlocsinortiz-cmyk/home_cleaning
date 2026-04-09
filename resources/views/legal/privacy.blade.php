@extends('layouts.app')

@section('title', 'Privacy Policy')

@section('content')
<div style="background: #f8fafc; min-height: calc(100vh - 64px); padding: 2rem 1rem 3rem; font-family: DM Sans, sans-serif;">
    <div style="max-width: 860px; margin: 0 auto; background: white; border: 1px solid #e2e8f0; border-radius: 20px; box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08); padding: 2rem;">
        <div style="margin-bottom: 1.5rem;">
            <div style="font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #1D9E75; margin-bottom: 8px;">Legal</div>
            <h1 style="font-size: 30px; font-weight: 800; color: #0f172a; margin-bottom: 8px;">Privacy Policy</h1>
            <p style="font-size: 14px; color: #64748b;">This page explains how account, booking, and location-related data is handled in the app.</p>
        </div>

        <div style="display: flex; flex-direction: column; gap: 1.25rem; color: #334155; font-size: 14px; line-height: 1.7;">
            <section>
                <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">1. Information Collected</h2>
                <p>The platform stores account details, contact information, booking data, and optional rating submissions needed to operate the service.</p>
            </section>
            <section>
                <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">2. How Information Is Used</h2>
                <p>Your information is used to create accounts, manage bookings, assign staff, send service updates, and improve service quality.</p>
            </section>
            <section>
                <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">3. Location Data</h2>
                <p>During active bookings, assigned staff may share live location data so clients and admins can monitor service progress. Location sharing is limited to active service use cases.</p>
            </section>
            <section>
                <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">4. Data Access</h2>
                <p>Access to booking and account information is restricted by role-based permissions inside the application.</p>
            </section>
            <section>
                <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">5. Contact</h2>
                <p>If you need changes to your account information or have privacy questions, please contact the service administrator.</p>
            </section>
        </div>
    </div>
</div>
@endsection
