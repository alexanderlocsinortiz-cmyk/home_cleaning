@extends('layouts.admin')
@section('title', 'Customers')
@section('page-title', 'Customer Management')
@section('page-subtitle', 'Review account readiness, booking activity, and customer records')

@section('content')
@php
    $hasActiveFilters = $search !== '' || collect($filters)->contains(fn ($value) => $value !== '');
    $verificationStyles = [
        'verified' => 'background:#dcfce7;color:#166534;border:1px solid #86efac;',
        'unverified' => 'background:#fef3c7;color:#92400e;border:1px solid #fcd34d;',
    ];
    $bookingStatusStyles = [
        'pending' => 'background:#fef3c7;color:#b45309;',
        'confirmed' => 'background:#E1F5EE;color:#1D9E75;',
        'in_progress' => 'background:#f3e8ff;color:#9333ea;',
        'completed' => 'background:#dcfce7;color:#16a34a;',
        'cancelled' => 'background:#fee2e2;color:#dc2626;',
    ];
    $customerDirectory = $customers->getCollection()->mapWithKeys(function ($customer) use ($genderOptions) {
        return [
            $customer->id => [
                'id' => $customer->id,
                'name' => $customer->full_name,
                'email' => $customer->email,
                'phone' => $customer->phone ?: 'Not provided',
                'gender' => $genderOptions[$customer->gender] ?? 'Not specified',
                'login_identifier' => $customer->username ? '@' . $customer->username : 'Email only',
                'barangay' => $customer->barangay_name,
                'street' => $customer->street,
                'city' => $customer->city,
                'zip_code' => $customer->zip_code,
                'joined_date' => optional($customer->created_at)->format('M d, Y'),
                'joined_relative' => optional($customer->created_at)->diffForHumans(),
                'verification_label' => $customer->email_verified_at ? 'Verified' : 'Pending verification',
                'verification_date' => $customer->email_verified_at
                    ? $customer->email_verified_at->format('M d, Y h:i A')
                    : 'Email not yet verified',
                'bookings_count' => $customer->bookings_count,
                'last_booking_date' => $customer->latest_booking_date
                    ? \Carbon\Carbon::parse($customer->latest_booking_date)->format('M d, Y')
                    : 'No bookings yet',
                'last_booking_status' => $customer->latest_booking_status
                    ? ucwords(str_replace('_', ' ', $customer->latest_booking_status))
                    : 'No booking activity',
                'last_booking_url' => $customer->latest_booking_id
                    ? route('bookings.show', $customer->latest_booking_id)
                    : null,
                'verification_url' => route('admin.customers.verification.edit', $customer),
                'delete_url' => route('admin.customers.destroy', $customer),
                'can_delete' => $customer->bookings_count === 0,
            ],
        ];
    })->all();
@endphp

<div class="space-y-6" style="font-family: 'DM Sans', sans-serif;">

    @if(session('success'))
        <div style="background:#dcfce7;border:1px solid #86efac;color:#166534;border-radius:14px;padding:14px 16px;display:flex;align-items:flex-start;gap:10px;">
            <i class="fas fa-check-circle" style="margin-top:2px;"></i>
            <div>
                <div style="font-size:14px;font-weight:700;">Action completed</div>
                <div style="font-size:13px;margin-top:2px;">{{ session('success') }}</div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:14px;padding:14px 16px;display:flex;align-items:flex-start;gap:10px;">
            <i class="fas fa-exclamation-triangle" style="margin-top:2px;"></i>
            <div>
                <div style="font-size:14px;font-weight:700;">Action blocked</div>
                <div style="font-size:13px;margin-top:2px;">{{ session('error') }}</div>
            </div>
        </div>
    @endif

    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
        <div style="background:white;border:1px solid #e2e8f0;border-radius:18px;padding:18px 20px;box-shadow:0 1px 4px rgba(0,0,0,0.05);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                <div>
                    <div style="font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#94a3b8;">Total Customers</div>
                    <div style="font-size:30px;font-weight:800;line-height:1;color:#1e293b;margin-top:8px;">{{ number_format($stats['total']) }}</div>
                </div>
                <div style="width:48px;height:48px;border-radius:14px;background:#E1F5EE;color:#1D9E75;display:flex;align-items:center;justify-content:center;font-size:20px;">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div style="font-size:12px;color:#64748b;margin-top:10px;">Registered client accounts in the system.</div>
        </div>

        <div style="background:white;border:1px solid #e2e8f0;border-radius:18px;padding:18px 20px;box-shadow:0 1px 4px rgba(0,0,0,0.05);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                <div>
                    <div style="font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#94a3b8;">Verified Customers</div>
                    <div style="font-size:30px;font-weight:800;line-height:1;color:#1e293b;margin-top:8px;">{{ number_format($stats['verified']) }}</div>
                </div>
                <div style="width:48px;height:48px;border-radius:14px;background:#dcfce7;color:#16a34a;display:flex;align-items:center;justify-content:center;font-size:20px;">
                    <i class="fas fa-circle-check"></i>
                </div>
            </div>
            <div style="font-size:12px;color:#64748b;margin-top:10px;">Accounts ready for verified client access.</div>
        </div>

        <div style="background:white;border:1px solid #e2e8f0;border-radius:18px;padding:18px 20px;box-shadow:0 1px 4px rgba(0,0,0,0.05);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                <div>
                    <div style="font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#94a3b8;">Customers With Bookings</div>
                    <div style="font-size:30px;font-weight:800;line-height:1;color:#1e293b;margin-top:8px;">{{ number_format($stats['with_bookings']) }}</div>
                </div>
                <div style="width:48px;height:48px;border-radius:14px;background:#eff6ff;color:#185FA5;display:flex;align-items:center;justify-content:center;font-size:20px;">
                    <i class="fas fa-calendar-check"></i>
                </div>
            </div>
            <div style="font-size:12px;color:#64748b;margin-top:10px;">Customers with recorded service requests.</div>
        </div>

        <div style="background:white;border:1px solid #e2e8f0;border-radius:18px;padding:18px 20px;box-shadow:0 1px 4px rgba(0,0,0,0.05);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                <div>
                    <div style="font-size:11px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#94a3b8;">New This Month</div>
                    <div style="font-size:30px;font-weight:800;line-height:1;color:#1e293b;margin-top:8px;">{{ number_format($stats['new_this_month']) }}</div>
                </div>
                <div style="width:48px;height:48px;border-radius:14px;background:#fef3c7;color:#b45309;display:flex;align-items:center;justify-content:center;font-size:20px;">
                    <i class="fas fa-user-plus"></i>
                </div>
            </div>
            <div style="font-size:12px;color:#64748b;margin-top:10px;">New registrations since {{ now()->startOfMonth()->format('M d') }}.</div>
        </div>
    </div>

    <div style="background:white;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 1px 4px rgba(0,0,0,0.05);overflow:hidden;">
        <div style="padding:20px 22px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;align-items:flex-start;">
            <div>
                <div style="font-size:18px;font-weight:800;color:#1e293b;">Search and Filter Customers</div>
                <div style="font-size:13px;color:#64748b;margin-top:4px;">Search customer records and refine the list by barangay, booking activity, verification status, and registration month.</div>
            </div>
            <div style="font-size:12px;color:#94a3b8;">
                {{ number_format($filteredCount) }} result{{ $filteredCount === 1 ? '' : 's' }}
                @if($stats['total'])
                    of {{ number_format($stats['total']) }}
                @endif
            </div>
        </div>

        <form method="GET" action="{{ route('admin.customers') }}" style="padding:20px 22px;">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-[minmax(0,1.8fr)_repeat(4,minmax(0,1fr))]">
                <div>
                    <label for="customer-search" style="display:block;font-size:12px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:#64748b;">Search Customers</label>
                    <div style="position:relative;margin-top:8px;">
                        <i class="fas fa-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#94a3b8;"></i>
                        <input
                            id="customer-search"
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Name, email, phone, or barangay"
                            style="width:100%;border:1px solid #dbe3ed;border-radius:12px;padding:12px 14px 12px 40px;font-size:14px;outline:none;background:#fff;"
                        >
                    </div>
                </div>

                <div>
                    <label for="customer-barangay" style="display:block;font-size:12px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:#64748b;">Barangay</label>
                    <select id="customer-barangay" name="barangay" style="width:100%;margin-top:8px;border:1px solid #dbe3ed;border-radius:12px;padding:12px 14px;font-size:14px;outline:none;background:#fff;">
                        <option value="">All barangays</option>
                        @foreach($barangays as $value => $label)
                            <option value="{{ $value }}" {{ $filters['barangay'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="customer-booking-activity" style="display:block;font-size:12px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:#64748b;">Booking Activity</label>
                    <select id="customer-booking-activity" name="booking_activity" style="width:100%;margin-top:8px;border:1px solid #dbe3ed;border-radius:12px;padding:12px 14px;font-size:14px;outline:none;background:#fff;">
                        <option value="">Any booking activity</option>
                        <option value="with_bookings" {{ $filters['booking_activity'] === 'with_bookings' ? 'selected' : '' }}>With bookings</option>
                        <option value="without_bookings" {{ $filters['booking_activity'] === 'without_bookings' ? 'selected' : '' }}>Without bookings</option>
                    </select>
                </div>

                <div>
                    <label for="customer-verification" style="display:block;font-size:12px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:#64748b;">Verification Status</label>
                    <select id="customer-verification" name="verification" style="width:100%;margin-top:8px;border:1px solid #dbe3ed;border-radius:12px;padding:12px 14px;font-size:14px;outline:none;background:#fff;">
                        <option value="">Any verification status</option>
                        <option value="verified" {{ $filters['verification'] === 'verified' ? 'selected' : '' }}>Verified</option>
                        <option value="pending" {{ $filters['verification'] === 'pending' ? 'selected' : '' }}>Pending verification</option>
                    </select>
                </div>

                <div>
                    <label for="customer-registration-month" style="display:block;font-size:12px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:#64748b;">Registration Month</label>
                    <select id="customer-registration-month" name="registration_month" style="width:100%;margin-top:8px;border:1px solid #dbe3ed;border-radius:12px;padding:12px 14px;font-size:14px;outline:none;background:#fff;">
                        <option value="">Any registration month</option>
                        @foreach($registrationMonthOptions as $value => $label)
                            <option value="{{ $value }}" {{ $filters['registration_month'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;margin-top:16px;">
                <div style="font-size:12px;color:#64748b;">
                    Use these filters to surface customers by location, verification readiness, booking activity, and registration month.
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    @if($hasActiveFilters)
                        <a href="{{ route('admin.customers') }}" style="display:inline-flex;align-items:center;gap:8px;border:1px solid #dbe3ed;background:#f8fafc;color:#475569;border-radius:12px;padding:10px 14px;font-size:13px;font-weight:700;text-decoration:none;">
                            <i class="fas fa-rotate-left"></i>
                            Clear Filters
                        </a>
                    @endif
                    <button type="submit" style="display:inline-flex;align-items:center;gap:8px;border:none;background:#1D9E75;color:white;border-radius:12px;padding:10px 16px;font-size:13px;font-weight:700;cursor:pointer;">
                        <i class="fas fa-filter"></i>
                        Apply Filters
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div style="background:white;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 1px 4px rgba(0,0,0,0.05);overflow:hidden;">
        <div style="padding:20px 22px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;align-items:flex-start;">
            <div>
                <div style="font-size:18px;font-weight:800;color:#1e293b;">Registered Customers</div>
                <div style="font-size:13px;color:#64748b;margin-top:4px;">A clearer operational view of customer identity, verification readiness, and booking history.</div>
            </div>
            <div style="font-size:12px;color:#94a3b8;">
                @if($customers->count())
                    Showing {{ number_format($customers->firstItem()) }}-{{ number_format($customers->lastItem()) }} of {{ number_format($customers->total()) }}
                @else
                    No records to display
                @endif
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:separate;border-spacing:0;font-size:13px;min-width:1120px;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:12px 18px;text-align:left;font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Customer</th>
                        <th style="padding:12px 18px;text-align:left;font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Contact</th>
                        <th style="padding:12px 18px;text-align:left;font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Location</th>
                        <th style="padding:12px 18px;text-align:left;font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Verification</th>
                        <th style="padding:12px 18px;text-align:left;font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Bookings</th>
                        <th style="padding:12px 18px;text-align:left;font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Last Booking</th>
                        <th style="padding:12px 18px;text-align:left;font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Joined</th>
                        <th style="padding:12px 18px;text-align:right;font-size:11px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        @php
                            $isVerified = !is_null($customer->email_verified_at);
                            $verificationStyle = $verificationStyles[$isVerified ? 'verified' : 'unverified'];
                            $latestBookingDate = $customer->latest_booking_date ? \Carbon\Carbon::parse($customer->latest_booking_date) : null;
                            $latestBookingStatusStyle = $customer->latest_booking_status
                                ? ($bookingStatusStyles[$customer->latest_booking_status] ?? 'background:#f1f5f9;color:#64748b;')
                                : 'background:#f8fafc;color:#94a3b8;';
                            $fullInitials = strtoupper(substr($customer->first_name, 0, 1) . substr($customer->last_name, 0, 1));
                            $streetPreview = $customer->street
                                ? \Illuminate\Support\Str::limit($customer->street, 26)
                                : '--';
                        @endphp
                        <tr style="border-top:1px solid #f8fafc;transition:background 0.15s ease;" onmouseover="this.style.background='#fbfdff'" onmouseout="this.style.background='white'">
                            <td style="padding:12px 18px;vertical-align:middle;border-top:1px solid #f8fafc;">
                                <div style="display:flex;gap:10px;align-items:center;">
                                    <div style="width:34px;height:34px;border-radius:12px;background:linear-gradient(135deg,#0F6E56,#1D9E75);color:white;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0;">
                                        {{ $fullInitials }}
                                    </div>
                                    <div style="min-width:0;">
                                        <div style="font-size:13px;font-weight:800;color:#1e293b;line-height:1.25;">{{ $customer->full_name }}</div>
                                        <div style="font-size:11px;color:#64748b;margin-top:3px;">
                                            {{ $customer->username ? '@' . $customer->username : 'Email-only login' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding:12px 18px;vertical-align:middle;border-top:1px solid #f8fafc;">
                                <div style="font-size:12px;font-weight:700;color:#1e293b;line-height:1.3;">{{ $customer->email }}</div>
                                <div style="font-size:11px;color:#64748b;margin-top:3px;">{{ $customer->phone ?: '--' }}</div>
                            </td>
                            <td style="padding:12px 18px;vertical-align:middle;border-top:1px solid #f8fafc;">
                                <div style="font-size:12px;font-weight:700;color:#1e293b;line-height:1.3;">{{ $customer->barangay_name }}</div>
                                <div style="font-size:11px;color:#94a3b8;margin-top:3px;">{{ $streetPreview }}</div>
                            </td>
                            <td style="padding:12px 18px;vertical-align:middle;border-top:1px solid #f8fafc;">
                                <span style="display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 9px;font-size:10.5px;font-weight:800;white-space:nowrap;{{ $verificationStyle }}">
                                    <i class="fas {{ $isVerified ? 'fa-check-circle' : 'fa-clock' }}"></i>
                                    {{ $isVerified ? 'Verified' : 'Pending' }}
                                </span>
                            </td>
                            <td style="padding:12px 18px;vertical-align:middle;border-top:1px solid #f8fafc;">
                                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                    <span title="Total bookings" style="display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 9px;background:#f8fafc;color:#1e293b;font-size:10.5px;font-weight:800;border:1px solid #e2e8f0;white-space:nowrap;">
                                        <i class="fas fa-calendar-days" style="color:#1D9E75;"></i>
                                        {{ $customer->bookings_count }}
                                    </span>
                                    @if($customer->latest_booking_status)
                                        <span style="display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 9px;font-size:10.5px;font-weight:800;white-space:nowrap;{{ $latestBookingStatusStyle }}">
                                            {{ ucwords(str_replace('_', ' ', $customer->latest_booking_status)) }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td style="padding:12px 18px;vertical-align:middle;border-top:1px solid #f8fafc;">
                                @if($latestBookingDate)
                                    <div style="font-size:12px;font-weight:700;color:#1e293b;">{{ $latestBookingDate->format('M d, Y') }}</div>
                                @else
                                    <div style="font-size:12px;color:#94a3b8;">--</div>
                                @endif
                            </td>
                            <td style="padding:12px 18px;vertical-align:middle;border-top:1px solid #f8fafc;">
                                <div style="font-size:12px;font-weight:700;color:#1e293b;">{{ $customer->created_at->format('M d, Y') }}</div>
                            </td>
                            <td style="padding:12px 18px;vertical-align:middle;border-top:1px solid #f8fafc;text-align:right;">
                                <div data-customer-action-wrap style="display:inline-flex;justify-content:flex-end;gap:6px;align-items:center;position:relative;">
                                    <button type="button" onclick="openCustomerModal({{ $customer->id }})" style="display:inline-flex;align-items:center;gap:6px;background:#f8fafc;color:#475569;border:1px solid #e2e8f0;border-radius:9px;padding:7px 10px;font-size:11.5px;font-weight:700;cursor:pointer;">
                                        <i class="fas fa-eye"></i>
                                        View
                                    </button>
                                    <button type="button" onclick="toggleCustomerActions({{ $customer->id }}, event)" title="More actions" style="width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;background:white;color:#475569;border:1px solid #e2e8f0;border-radius:9px;cursor:pointer;">
                                        <i class="fas fa-ellipsis"></i>
                                    </button>
                                    <div id="customer-actions-{{ $customer->id }}" data-customer-actions style="display:none;position:absolute;top:calc(100% + 6px);right:0;min-width:196px;background:white;border:1px solid #e2e8f0;border-radius:12px;padding:6px;box-shadow:0 14px 30px rgba(15,23,42,0.12);z-index:6;text-align:left;">
                                        <a href="{{ route('admin.customers.verification.edit', $customer) }}" onclick="closeCustomerActionMenus()" style="display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:9px;color:#0F6E56;text-decoration:none;font-size:12px;font-weight:700;background:#E1F5EE;">
                                            <i class="fas fa-shield-halved"></i>
                                            Manage Verification
                                        </a>
                                        @if($customer->bookings_count === 0)
                                            <button type="button" onclick="openDeleteModal({{ $customer->id }})" style="width:100%;margin-top:6px;display:flex;align-items:center;gap:8px;padding:9px 10px;border:none;border-radius:9px;color:#dc2626;background:#fff5f5;font-size:12px;font-weight:700;cursor:pointer;">
                                                <i class="fas fa-trash"></i>
                                                Delete account
                                            </button>
                                        @else
                                            <div style="margin-top:6px;display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:9px;color:#185FA5;background:#eff6ff;font-size:12px;font-weight:700;">
                                                <i class="fas fa-shield-halved"></i>
                                                Protected record
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="padding:52px 24px;text-align:center;border-top:1px solid #f8fafc;">
                                <div style="width:72px;height:72px;border-radius:18px;background:#f8fafc;color:#94a3b8;display:flex;align-items:center;justify-content:center;font-size:28px;margin:0 auto 18px;">
                                    <i class="fas fa-user-slash"></i>
                                </div>
                                @if($hasActiveFilters)
                                    <div style="font-size:20px;font-weight:800;color:#1e293b;">No customers match the current filters</div>
                                    <div style="font-size:13px;color:#64748b;max-width:460px;margin:8px auto 0;">
                                        Try broadening the search terms or clearing one or more filters to see more customer records.
                                    </div>
                                    <a href="{{ route('admin.customers') }}" style="display:inline-flex;align-items:center;gap:8px;background:#1D9E75;color:white;border-radius:12px;padding:11px 16px;font-size:13px;font-weight:700;text-decoration:none;margin-top:18px;">
                                        <i class="fas fa-rotate-left"></i>
                                        Clear Filters
                                    </a>
                                @else
                                    <div style="font-size:20px;font-weight:800;color:#1e293b;">No customers have registered yet</div>
                                    <div style="font-size:13px;color:#64748b;max-width:460px;margin:8px auto 0;">
                                        Customer records will appear here after users create client accounts through the registration page.
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="padding:16px 22px;border-top:1px solid #f1f5f9;display:flex;justify-content:space-between;gap:16px;align-items:center;flex-wrap:wrap;">
            <div style="font-size:12px;color:#64748b;">
                {{ $customers->total() }} customer record{{ $customers->total() === 1 ? '' : 's' }} available.
            </div>
            @if($customers->hasPages())
                <div>
                    {{ $customers->links('pagination::tailwind') }}
                </div>
            @endif
        </div>
    </div>
</div>

<div id="customer-detail-modal" class="hidden" style="position:fixed;inset:0;z-index:80;">
    <div onclick="closeCustomerModal()" style="position:absolute;inset:0;background:rgba(15,23,42,0.58);backdrop-filter:blur(2px);"></div>
    <div style="position:relative;max-width:720px;margin:40px auto;background:white;border-radius:20px;box-shadow:0 20px 50px rgba(15,23,42,0.28);overflow:hidden;">
        <div style="padding:20px 22px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
            <div>
                <div style="font-size:12px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;color:#94a3b8;">Customer Overview</div>
                <div id="detail-name" style="font-size:24px;font-weight:800;color:#1e293b;margin-top:4px;">Customer Name</div>
                <div id="detail-email" style="font-size:13px;color:#64748b;margin-top:4px;">email@example.com</div>
            </div>
            <button type="button" onclick="closeCustomerModal()" style="border:none;background:#f8fafc;color:#475569;border-radius:12px;width:40px;height:40px;cursor:pointer;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div style="padding:20px 22px;display:grid;gap:18px;">
            <div class="grid gap-4 md:grid-cols-3">
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:14px 16px;">
                    <div style="font-size:11px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;color:#94a3b8;">Verification</div>
                    <div id="detail-verification-label" style="font-size:16px;font-weight:800;color:#1e293b;margin-top:8px;">Verified</div>
                    <div id="detail-verification-date" style="font-size:12px;color:#64748b;margin-top:4px;">Verified date</div>
                </div>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:14px 16px;">
                    <div style="font-size:11px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;color:#94a3b8;">Bookings</div>
                    <div id="detail-bookings-count" style="font-size:16px;font-weight:800;color:#1e293b;margin-top:8px;">0 bookings</div>
                    <div id="detail-last-booking-date" style="font-size:12px;color:#64748b;margin-top:4px;">No bookings yet</div>
                </div>
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:14px 16px;">
                    <div style="font-size:11px;font-weight:800;letter-spacing:0.08em;text-transform:uppercase;color:#94a3b8;">Joined</div>
                    <div id="detail-joined-date" style="font-size:16px;font-weight:800;color:#1e293b;margin-top:8px;">M d, Y</div>
                    <div id="detail-joined-relative" style="font-size:12px;color:#64748b;margin-top:4px;">recently</div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div style="border:1px solid #f1f5f9;border-radius:16px;padding:16px;">
                    <div style="font-size:14px;font-weight:800;color:#1e293b;margin-bottom:12px;">Profile Details</div>
                    <div class="grid gap-3">
                        <div>
                            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;">Login Method</div>
                            <div id="detail-username" style="font-size:14px;color:#1e293b;margin-top:4px;">Email only</div>
                        </div>
                        <div>
                            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;">Phone</div>
                            <div id="detail-phone" style="font-size:14px;color:#1e293b;margin-top:4px;">phone</div>
                        </div>
                        <div>
                            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;">Gender</div>
                            <div id="detail-gender" style="font-size:14px;color:#1e293b;margin-top:4px;">gender</div>
                        </div>
                    </div>
                </div>
                <div style="border:1px solid #f1f5f9;border-radius:16px;padding:16px;">
                    <div style="font-size:14px;font-weight:800;color:#1e293b;margin-bottom:12px;">Address Details</div>
                    <div class="grid gap-3">
                        <div>
                            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;">Barangay</div>
                            <div id="detail-barangay" style="font-size:14px;color:#1e293b;margin-top:4px;">barangay</div>
                        </div>
                        <div>
                            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;">Street</div>
                            <div id="detail-street" style="font-size:14px;color:#1e293b;margin-top:4px;">street</div>
                        </div>
                        <div>
                            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#94a3b8;">City and ZIP Code</div>
                            <div id="detail-city-zip" style="font-size:14px;color:#1e293b;margin-top:4px;">city</div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;">
                <a id="detail-last-booking-link" href="#" class="hidden" style="display:none;align-items:center;gap:8px;background:#eff6ff;color:#185FA5;border:1px solid #bfdbfe;border-radius:12px;padding:10px 14px;font-size:13px;font-weight:700;text-decoration:none;">
                    <i class="fas fa-arrow-up-right-from-square"></i>
                    Open Latest Booking
                </a>
                <a id="detail-verification-link" href="#" style="display:inline-flex;align-items:center;gap:8px;background:#1D9E75;color:white;border-radius:12px;padding:10px 14px;font-size:13px;font-weight:700;text-decoration:none;">
                    <i class="fas fa-shield-halved"></i>
                    Verification Status
                </a>
            </div>
        </div>
    </div>
</div>

<div id="customer-delete-modal" class="hidden" style="position:fixed;inset:0;z-index:81;">
    <div onclick="closeDeleteModal()" style="position:absolute;inset:0;background:rgba(15,23,42,0.62);backdrop-filter:blur(2px);"></div>
    <div style="position:relative;max-width:540px;margin:80px auto;background:white;border-radius:20px;box-shadow:0 20px 50px rgba(15,23,42,0.28);overflow:hidden;">
        <div style="padding:22px;">
            <div style="width:54px;height:54px;border-radius:16px;background:#fee2e2;color:#dc2626;display:flex;align-items:center;justify-content:center;font-size:22px;">
                <i class="fas fa-trash"></i>
            </div>
            <div style="font-size:24px;font-weight:800;color:#1e293b;margin-top:18px;">Delete customer account?</div>
            <div id="delete-message" style="font-size:13px;line-height:1.6;color:#64748b;margin-top:8px;">
                This action permanently removes the selected customer account.
            </div>
            <div style="background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-radius:14px;padding:12px 14px;font-size:12px;line-height:1.6;margin-top:16px;">
                Only customers without booking history can be deleted. Historical records should be retained for operations, reports, and defense presentation.
            </div>
            <form id="delete-customer-form" method="POST" style="margin-top:18px;display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;">
                @csrf
                @method('DELETE')
                <button type="button" onclick="closeDeleteModal()" style="border:1px solid #dbe3ed;background:#f8fafc;color:#475569;border-radius:12px;padding:10px 14px;font-size:13px;font-weight:700;cursor:pointer;">
                    Cancel
                </button>
                <button type="submit" style="border:none;background:#dc2626;color:white;border-radius:12px;padding:10px 16px;font-size:13px;font-weight:700;cursor:pointer;">
                    Delete Permanently
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const customerDirectory = @json($customerDirectory);

function closeCustomerActionMenus() {
    document.querySelectorAll('[data-customer-actions]').forEach(function (menu) {
        menu.style.display = 'none';
    });
}

function toggleCustomerActions(customerId, event) {
    if (event) {
        event.stopPropagation();
    }

    const menu = document.getElementById(`customer-actions-${customerId}`);
    if (!menu) {
        return;
    }

    const isVisible = menu.style.display === 'block';
    closeCustomerActionMenus();
    menu.style.display = isVisible ? 'none' : 'block';
}

function legacyCustomerModal(customerId) {
    const customer = customerDirectory[customerId];
    if (!customer) {
        return;
    }

    closeCustomerActionMenus();

    document.getElementById('detail-name').textContent = customer.name;
    document.getElementById('detail-email').textContent = customer.email;
    document.getElementById('detail-verification-label').textContent = customer.verification_label;
    document.getElementById('detail-verification-date').textContent = customer.verification_date;
    document.getElementById('detail-bookings-count').textContent = `${customer.bookings_count} booking${customer.bookings_count === 1 ? '' : 's'}`;
    document.getElementById('detail-last-booking-date').textContent = customer.bookings_count > 0
        ? `${customer.last_booking_date} • ${customer.last_booking_status}`
        : customer.last_booking_date;
    if (customer.bookings_count > 0) {
        document.getElementById('detail-last-booking-date').textContent = `${customer.last_booking_date} - ${customer.last_booking_status}`;
    }

    document.getElementById('detail-joined-date').textContent = customer.joined_date;
    document.getElementById('detail-joined-relative').textContent = customer.joined_relative;
    document.getElementById('detail-username').textContent = customer.login_identifier;
    document.getElementById('detail-phone').textContent = customer.phone;
    document.getElementById('detail-gender').textContent = customer.gender;
    document.getElementById('detail-barangay').textContent = customer.barangay;
    document.getElementById('detail-street').textContent = customer.street;
    document.getElementById('detail-city-zip').textContent = `${customer.city}, ${customer.zip_code}`;
    document.getElementById('detail-verification-link').href = customer.verification_url;

    const latestBookingLink = document.getElementById('detail-last-booking-link');
    if (customer.last_booking_url) {
        latestBookingLink.href = customer.last_booking_url;
        latestBookingLink.style.display = 'inline-flex';
    } else {
        latestBookingLink.href = '#';
        latestBookingLink.style.display = 'none';
    }

    document.getElementById('customer-detail-modal').classList.remove('hidden');
}

function openCustomerModal(customerId) {
    const customer = customerDirectory[customerId];
    if (!customer) {
        return;
    }

    closeCustomerActionMenus();

    document.getElementById('detail-name').textContent = customer.name;
    document.getElementById('detail-email').textContent = customer.email;
    document.getElementById('detail-verification-label').textContent = customer.verification_label;
    document.getElementById('detail-verification-date').textContent = customer.verification_date;
    document.getElementById('detail-bookings-count').textContent = `${customer.bookings_count} booking${customer.bookings_count === 1 ? '' : 's'}`;
    document.getElementById('detail-last-booking-date').textContent = customer.bookings_count > 0
        ? `${customer.last_booking_date} - ${customer.last_booking_status}`
        : customer.last_booking_date;
    document.getElementById('detail-joined-date').textContent = customer.joined_date;
    document.getElementById('detail-joined-relative').textContent = customer.joined_relative;
    document.getElementById('detail-username').textContent = customer.login_identifier;
    document.getElementById('detail-phone').textContent = customer.phone;
    document.getElementById('detail-gender').textContent = customer.gender;
    document.getElementById('detail-barangay').textContent = customer.barangay;
    document.getElementById('detail-street').textContent = customer.street;
    document.getElementById('detail-city-zip').textContent = `${customer.city}, ${customer.zip_code}`;
    document.getElementById('detail-verification-link').href = customer.verification_url;

    const latestBookingLink = document.getElementById('detail-last-booking-link');
    if (customer.last_booking_url) {
        latestBookingLink.href = customer.last_booking_url;
        latestBookingLink.style.display = 'inline-flex';
    } else {
        latestBookingLink.href = '#';
        latestBookingLink.style.display = 'none';
    }

    document.getElementById('customer-detail-modal').classList.remove('hidden');
}

function closeCustomerModal() {
    document.getElementById('customer-detail-modal').classList.add('hidden');
}

function openDeleteModal(customerId) {
    const customer = customerDirectory[customerId];
    if (!customer || !customer.can_delete) {
        return;
    }

    closeCustomerActionMenus();

    document.getElementById('delete-customer-form').action = customer.delete_url;
    document.getElementById('delete-message').textContent = `You are about to permanently remove ${customer.name}. This should only be used for duplicate, test, or unused accounts with no booking history.`;
    document.getElementById('customer-delete-modal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('customer-delete-modal').classList.add('hidden');
}

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        closeCustomerActionMenus();
        closeCustomerModal();
        closeDeleteModal();
    }
});

document.addEventListener('click', function (event) {
    if (!event.target.closest('[data-customer-action-wrap]')) {
        closeCustomerActionMenus();
    }
});
</script>
@endpush
