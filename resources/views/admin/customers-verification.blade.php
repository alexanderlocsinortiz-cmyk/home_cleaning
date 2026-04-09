@extends('layouts.admin')
@section('title', 'Customer Verification')
@section('page-title', 'Verification Status')
@section('page-subtitle', 'Review and update customer email verification records')

@section('content')
@php
    $isVerified = !is_null($customer->email_verified_at);
    $latestBookingDate = $latestBooking ? \Carbon\Carbon::parse($latestBooking->scheduled_date) : null;
    $latestBookingStatus = $latestBooking ? ucwords(str_replace('_', ' ', $latestBooking->status)) : 'No bookings yet';
    $currentVerificationStatus = old('verification_status', $isVerified ? 'verified' : 'pending');
    $statusBadgeStyle = $isVerified
        ? 'background:#dcfce7;color:#166534;border:1px solid #86efac;'
        : 'background:#fef3c7;color:#92400e;border:1px solid #fcd34d;';
@endphp

<div class="space-y-6" style="font-family: 'DM Sans', sans-serif;">

    @if(session('success'))
        <div style="background:#dcfce7;border:1px solid #86efac;color:#166534;border-radius:14px;padding:14px 16px;display:flex;align-items:flex-start;gap:10px;">
            <i class="fas fa-check-circle" style="margin-top:2px;"></i>
            <div>
                <div style="font-size:14px;font-weight:700;">Verification updated</div>
                <div style="font-size:13px;margin-top:2px;">{{ session('success') }}</div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:14px;padding:14px 16px;">
            <div style="font-size:14px;font-weight:700;">Please review the verification form.</div>
            <div style="font-size:13px;margin-top:4px;">The verification status could not be saved because of validation errors.</div>
        </div>
    @endif

    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
        <div>
            <a href="{{ route('admin.customers') }}" style="display:inline-flex;align-items:center;gap:8px;color:#185FA5;text-decoration:none;font-size:13px;font-weight:700;margin-bottom:10px;">
                <i class="fas fa-arrow-left"></i>
                Back to Customers
            </a>
            <div style="font-size:26px;font-weight:800;color:#1e293b;">{{ $customer->full_name }}</div>
            <div style="font-size:13px;color:#64748b;margin-top:4px;">Manage email verification only. Customer profile details remain read-only in this workflow.</div>
        </div>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <div style="min-width:180px;background:white;border:1px solid #e2e8f0;border-radius:16px;padding:14px 16px;">
                <div style="font-size:11px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;color:#94a3b8;">Current Status</div>
                <div style="margin-top:10px;">
                    <span style="display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 10px;font-size:11px;font-weight:800;{{ $statusBadgeStyle }}">
                        <i class="fas {{ $isVerified ? 'fa-circle-check' : 'fa-clock' }}"></i>
                        {{ $isVerified ? 'Verified' : 'Pending verification' }}
                    </span>
                </div>
                <div style="font-size:12px;color:#64748b;margin-top:10px;">
                    {{ $isVerified ? 'Verified on ' . $customer->email_verified_at->format('M d, Y h:i A') : 'Customer still needs email verification.' }}
                </div>
            </div>
            <div style="min-width:180px;background:white;border:1px solid #e2e8f0;border-radius:16px;padding:14px 16px;">
                <div style="font-size:11px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;color:#94a3b8;">Booking History</div>
                <div style="font-size:18px;font-weight:800;color:#1e293b;margin-top:8px;">{{ $customer->bookings_count }} booking{{ $customer->bookings_count === 1 ? '' : 's' }}</div>
                <div style="font-size:12px;color:#64748b;margin-top:4px;">{{ $latestBookingDate ? 'Latest on ' . $latestBookingDate->format('M d, Y') : 'No bookings yet.' }}</div>
            </div>
        </div>
    </div>

    <div style="background:{{ $isVerified ? '#eff6ff' : '#fff7ed' }};border:1px solid {{ $isVerified ? '#bfdbfe' : '#fed7aa' }};color:{{ $isVerified ? '#1d4ed8' : '#9a3412' }};border-radius:16px;padding:14px 16px;display:flex;gap:10px;align-items:flex-start;">
        <i class="fas {{ $isVerified ? 'fa-shield-halved' : 'fa-triangle-exclamation' }}" style="margin-top:2px;"></i>
        <div style="font-size:13px;line-height:1.6;">
            {{ $isVerified
                ? 'This customer currently has verified-client access. Marking the account as pending verification will remove that verified status until the email is confirmed again.'
                : 'This customer is still pending verification. Mark the account as verified only when the verification state is confirmed by the admin team.' }}
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
        <div style="background:white;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 1px 4px rgba(0,0,0,0.05);overflow:hidden;">
            <div style="padding:20px 22px;border-bottom:1px solid #f1f5f9;">
                <div style="font-size:18px;font-weight:800;color:#1e293b;">Verification Management</div>
                <div style="font-size:13px;color:#64748b;margin-top:4px;">Update the customer's verification status without editing personal profile fields from this screen.</div>
            </div>

            <form method="POST" action="{{ route('admin.customers.verification.update', $customer) }}" style="padding:20px 22px;">
                @csrf
                @method('PUT')

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px;">Customer Email</label>
                        <div style="width:100%;border:1px solid #dbe3ed;border-radius:12px;padding:12px 14px;font-size:14px;background:#f8fafc;color:#1e293b;">
                            {{ $customer->email }}
                        </div>
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px;">Login Method</label>
                        <div style="width:100%;border:1px solid #dbe3ed;border-radius:12px;padding:12px 14px;font-size:14px;background:#f8fafc;color:#1e293b;">
                            {{ $customer->username ? '@' . $customer->username : 'Email only' }}
                        </div>
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px;">Joined</label>
                        <div style="width:100%;border:1px solid #dbe3ed;border-radius:12px;padding:12px 14px;font-size:14px;background:#f8fafc;color:#1e293b;">
                            {{ $customer->created_at->format('F d, Y') }}
                        </div>
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px;">Verified At</label>
                        <div style="width:100%;border:1px solid #dbe3ed;border-radius:12px;padding:12px 14px;font-size:14px;background:#f8fafc;color:#1e293b;">
                            {{ $customer->email_verified_at ? $customer->email_verified_at->format('F d, Y h:i A') : 'Not yet verified' }}
                        </div>
                    </div>
                </div>

                <div style="margin-top:22px;padding:18px;border:1px solid #e2e8f0;border-radius:16px;background:#fbfdff;">
                    <label for="verification_status" style="display:block;font-size:13px;font-weight:700;color:#374151;margin-bottom:6px;">Verification Status</label>
                    <select id="verification_status" name="verification_status" style="width:100%;border:1px solid #dbe3ed;border-radius:12px;padding:12px 14px;font-size:14px;outline:none;background:white;">
                        <option value="verified" {{ $currentVerificationStatus === 'verified' ? 'selected' : '' }}>Verified</option>
                        <option value="pending" {{ $currentVerificationStatus === 'pending' ? 'selected' : '' }}>Pending verification</option>
                    </select>
                    @error('verification_status')<div style="font-size:12px;color:#dc2626;margin-top:6px;">{{ $message }}</div>@enderror

                    <div style="display:grid;gap:10px;margin-top:14px;">
                        <div style="border:1px solid #dcfce7;background:#f0fdf4;border-radius:14px;padding:12px 14px;">
                            <div style="font-size:12px;font-weight:800;color:#166534;">Verified</div>
                            <div style="font-size:12px;line-height:1.6;color:#166534;margin-top:4px;">Use when the account should be treated as email-verified and ready for verified-client access.</div>
                        </div>
                        <div style="border:1px solid #fed7aa;background:#fff7ed;border-radius:14px;padding:12px 14px;">
                            <div style="font-size:12px;font-weight:800;color:#9a3412;">Pending verification</div>
                            <div style="font-size:12px;line-height:1.6;color:#9a3412;margin-top:4px;">Use when the verification state should be cleared and the customer should return to a pending email verification status.</div>
                        </div>
                    </div>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;margin-top:24px;padding-top:18px;border-top:1px solid #f1f5f9;">
                    <a href="{{ route('admin.customers') }}" style="display:inline-flex;align-items:center;gap:8px;background:#f8fafc;border:1px solid #dbe3ed;color:#475569;border-radius:12px;padding:11px 16px;font-size:13px;font-weight:700;text-decoration:none;">
                        <i class="fas fa-xmark"></i>
                        Cancel
                    </a>
                    <button type="submit" style="display:inline-flex;align-items:center;gap:8px;border:none;background:#1D9E75;color:white;border-radius:12px;padding:11px 18px;font-size:13px;font-weight:700;cursor:pointer;">
                        <i class="fas fa-shield-halved"></i>
                        Save Verification
                    </button>
                </div>
            </form>
        </div>

        <div class="space-y-4">
            <div style="background:white;border:1px solid #e2e8f0;border-radius:18px;padding:18px 20px;box-shadow:0 1px 4px rgba(0,0,0,0.05);">
                <div style="font-size:15px;font-weight:800;color:#1e293b;">Verification Summary</div>
                <div style="font-size:13px;color:#64748b;margin-top:4px;">Read-only account context for verification decisions.</div>
                <div style="display:grid;gap:12px;margin-top:16px;">
                    <div style="padding-top:12px;border-top:1px solid #f8fafc;">
                        <div style="font-size:11px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;color:#94a3b8;">Customer Name</div>
                        <div style="font-size:14px;color:#1e293b;font-weight:700;margin-top:4px;">{{ $customer->full_name }}</div>
                    </div>
                    <div style="padding-top:12px;border-top:1px solid #f8fafc;">
                        <div style="font-size:11px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;color:#94a3b8;">Registration Month</div>
                        <div style="font-size:14px;color:#1e293b;font-weight:700;margin-top:4px;">{{ $customer->created_at->format('F Y') }}</div>
                    </div>
                    <div style="padding-top:12px;border-top:1px solid #f8fafc;">
                        <div style="font-size:11px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;color:#94a3b8;">Latest Booking Status</div>
                        <div style="font-size:14px;color:#1e293b;font-weight:700;margin-top:4px;">{{ $latestBookingStatus }}</div>
                    </div>
                    <div style="padding-top:12px;border-top:1px solid #f8fafc;">
                        <div style="font-size:11px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;color:#94a3b8;">Operational Note</div>
                        <div style="font-size:13px;line-height:1.6;color:#64748b;margin-top:4px;">Profiles with booking history should remain in the system to preserve operations, reports, and defense-ready records.</div>
                    </div>
                </div>
            </div>

            @if($latestBooking)
                <div style="background:white;border:1px solid #e2e8f0;border-radius:18px;padding:18px 20px;box-shadow:0 1px 4px rgba(0,0,0,0.05);">
                    <div style="font-size:15px;font-weight:800;color:#1e293b;">Latest Booking</div>
                    <div style="font-size:13px;color:#64748b;margin-top:4px;">Most recent service request linked to this customer.</div>
                    <div style="margin-top:16px;padding:14px;border-radius:14px;background:#f8fafc;border:1px solid #e2e8f0;">
                        <div style="font-size:13px;font-weight:800;color:#1e293b;">CF-{{ str_pad($latestBooking->id, 5, '0', STR_PAD_LEFT) }}</div>
                        <div style="font-size:12px;color:#64748b;margin-top:4px;">{{ $latestBooking->service_label }}</div>
                        <div style="font-size:12px;color:#64748b;margin-top:4px;">{{ $latestBookingDate?->format('M d, Y') }} at {{ \Carbon\Carbon::parse($latestBooking->scheduled_time)->format('h:i A') }}</div>
                        <a href="{{ route('bookings.show', $latestBooking->id) }}" style="display:inline-flex;align-items:center;gap:8px;background:#eff6ff;color:#185FA5;border:1px solid #bfdbfe;border-radius:12px;padding:10px 14px;font-size:13px;font-weight:700;text-decoration:none;margin-top:12px;">
                            <i class="fas fa-arrow-up-right-from-square"></i>
                            Open Booking
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
