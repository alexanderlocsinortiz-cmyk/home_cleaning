@extends('layouts.app')

@section('title', 'Terms of Service')

@section('content')
<div style="background: #f8fafc; min-height: calc(100vh - 64px); padding: 2rem 1rem 3rem; font-family: DM Sans, sans-serif;">
    <div style="max-width: 860px; margin: 0 auto; background: white; border: 1px solid #e2e8f0; border-radius: 20px; box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08); padding: 2rem;">
        <div style="margin-bottom: 1.5rem;">
            <div style="font-size: 12px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #1D9E75; margin-bottom: 8px;">Legal</div>
            <h1 style="font-size: 30px; font-weight: 800; color: #0f172a; margin-bottom: 8px;">Terms of Service</h1>
            <p style="font-size: 14px; color: #64748b;">These terms govern bookings and use of the Home Cleaning Service platform.</p>
        </div>

        <div style="display: flex; flex-direction: column; gap: 1.25rem; color: #334155; font-size: 14px; line-height: 1.7;">
            <section>
                <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">1. Booking Requests</h2>
                <p>Submitting a booking creates a service request. A request is not final until it is reviewed and confirmed by the Home Cleaning Service team.</p>
            </section>
            <section>
                <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">2. Customer Responsibilities</h2>
                <p>You agree to provide accurate contact details, a valid service address, and safe access to the property at the scheduled time.</p>
            </section>
            <section>
                <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">3. Pricing and Payment</h2>
                <p>Displayed pricing is based on the information provided during booking. Final charges may reflect confirmed service details and approved add-ons. Payment is collected after service completion unless another arrangement is stated.</p>
            </section>
            <section>
                <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">4. Cancellations</h2>
                <p>Pending bookings may be cancelled through the platform. Once a booking has been confirmed or staff has been assigned, cancellation rules may be more limited.</p>
            </section>
            <section>
                <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">5. Platform Conduct</h2>
                <p>You agree not to misuse the website, attempt unauthorized access, or submit false or harmful information through any form or account.</p>
            </section>
        </div>
    </div>
</div>
@endsection
