@extends('layouts.admin')
@section('title', 'Customers')
@section('page-title', 'Customer Management')
@section('page-subtitle', 'Review account readiness, booking activity, and customer records')

@section('content')
@php
    $hasActiveFilters = $search !== '' || collect($filters)->contains(fn ($value) => $value !== '');
    $verificationClasses = [
        'verified' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        'unverified' => 'border-amber-200 bg-amber-50 text-amber-700',
    ];
    $bookingStatusClasses = [
        'pending' => 'bg-amber-100 text-amber-700',
        'confirmed' => 'bg-accent-50 text-accent-700',
        'in_progress' => 'bg-primary-100 text-primary-700',
        'completed' => 'bg-accent-100 text-accent-800',
        'cancelled' => 'bg-danger-100 text-danger-700',
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

<div class="admin-page-content cleanflow-page-shell space-y-4 p-0">
    @if(session('success'))
        <div class="cleanflow-alert cleanflow-alert--success flex items-start gap-3">
            <i class="fas fa-check-circle mt-0.5"></i>
            <div>
                <div class="text-sm font-bold">Action completed</div>
                <div class="text-sm">{{ session('success') }}</div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="cleanflow-alert cleanflow-alert--error flex items-start gap-3">
            <i class="fas fa-exclamation-triangle mt-0.5"></i>
            <div>
                <div class="text-sm font-bold">Action blocked</div>
                <div class="text-sm">{{ session('error') }}</div>
            </div>
        </div>
    @endif

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Customers</div>
                    <div class="mt-1 text-2xl font-black leading-none text-slate-900">{{ number_format($stats['total']) }}</div>
                    <div class="mt-1 text-xs text-slate-500">Registered client accounts.</div>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Verified</div>
                    <div class="mt-1 text-2xl font-black leading-none text-slate-900">{{ number_format($stats['verified']) }}</div>
                    <div class="mt-1 text-xs text-slate-500">Ready client accounts.</div>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-50 text-green-700">
                    <i class="fas fa-circle-check"></i>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">With Bookings</div>
                    <div class="mt-1 text-2xl font-black leading-none text-slate-900">{{ number_format($stats['with_bookings']) }}</div>
                    <div class="mt-1 text-xs text-slate-500">Have service records.</div>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                    <i class="fas fa-calendar-check"></i>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">New This Month</div>
                    <div class="mt-1 text-2xl font-black leading-none text-slate-900">{{ number_format($stats['new_this_month']) }}</div>
                    <div class="mt-1 text-xs text-slate-500">Since {{ now()->startOfMonth()->format('M d') }}.</div>
                </div>
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                    <i class="fas fa-user-plus"></i>
                </div>
            </div>
        </div>
    </div>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">Search and Filter Customers</h3>
                <p class="mt-1 text-xs text-slate-500">Search records and narrow the list by location, activity, verification, or month.</p>
            </div>
            <div class="text-xs font-semibold text-slate-400">
                {{ number_format($filteredCount) }} result{{ $filteredCount === 1 ? '' : 's' }}
                @if($stats['total'])
                    of {{ number_format($stats['total']) }}
                @endif
            </div>
        </div>

        <form method="GET" action="{{ route('admin.customers') }}" class="space-y-4 px-5 py-4">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(280px,1.5fr)_repeat(4,minmax(150px,1fr))]">
                <div>
                    <label for="customer-search" class="mb-1.5 block text-xs font-extrabold uppercase tracking-wide text-slate-500">Search</label>
                    <div class="relative">
                        <i class="fas fa-search pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input id="customer-search" type="text" name="search" value="{{ $search }}" placeholder="Name, email, phone, or barangay" class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-11 pr-4 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
                    </div>
                </div>
                <div>
                    <label for="customer-barangay" class="mb-1.5 block text-xs font-extrabold uppercase tracking-wide text-slate-500">Barangay</label>
                    <select id="customer-barangay" name="barangay" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
                        <option value="">All barangays</option>
                        @foreach($barangays as $value => $label)
                            <option value="{{ $value }}" {{ $filters['barangay'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="customer-booking-activity" class="mb-1.5 block text-xs font-extrabold uppercase tracking-wide text-slate-500">Activity</label>
                    <select id="customer-booking-activity" name="booking_activity" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
                        <option value="">Any booking activity</option>
                        <option value="with_bookings" {{ $filters['booking_activity'] === 'with_bookings' ? 'selected' : '' }}>With bookings</option>
                        <option value="without_bookings" {{ $filters['booking_activity'] === 'without_bookings' ? 'selected' : '' }}>Without bookings</option>
                    </select>
                </div>
                <div>
                    <label for="customer-verification" class="mb-1.5 block text-xs font-extrabold uppercase tracking-wide text-slate-500">Verification</label>
                    <select id="customer-verification" name="verification" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
                        <option value="">Any verification status</option>
                        <option value="verified" {{ $filters['verification'] === 'verified' ? 'selected' : '' }}>Verified</option>
                        <option value="pending" {{ $filters['verification'] === 'pending' ? 'selected' : '' }}>Pending verification</option>
                    </select>
                </div>
                <div>
                    <label for="customer-registration-month" class="mb-1.5 block text-xs font-extrabold uppercase tracking-wide text-slate-500">Month</label>
                    <select id="customer-registration-month" name="registration_month" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
                        <option value="">Any registration month</option>
                        @foreach($registrationMonthOptions as $value => $label)
                            <option value="{{ $value }}" {{ $filters['registration_month'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-end">
                <div class="hidden text-sm text-slate-500">
                    Use these filters to surface customers by location, verification readiness, booking activity, and registration month.
                </div>
                <div class="flex flex-wrap gap-3">
                    @if($hasActiveFilters)
                        <a href="{{ route('admin.customers') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-100">
                            <i class="fas fa-rotate-left"></i>
                            Clear Filters
                        </a>
                    @endif
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-slate-700">
                        <i class="fas fa-filter"></i>
                        Apply Filters
                    </button>
                </div>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">Registered Customers</h3>
                <p class="mt-1 text-xs text-slate-500">Review account details, verification, bookings, and account actions.</p>
            </div>
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                {{ number_format($customers->total()) }} total account{{ $customers->total() === 1 ? '' : 's' }}
            </div>
        </div>

        @if($customers->count())
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1080px] table-fixed divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50/90">
                        <tr>
                            <th class="w-[260px] px-4 py-3 text-left text-[11px] font-extrabold uppercase tracking-wide text-slate-500">Customer</th>
                            <th class="w-[260px] px-4 py-3 text-left text-[11px] font-extrabold uppercase tracking-wide text-slate-500">Contact</th>
                            <th class="hidden px-4 py-3 text-left text-[11px] font-extrabold uppercase tracking-wide text-slate-500">Location</th>
                            <th class="w-[170px] px-4 py-3 text-left text-[11px] font-extrabold uppercase tracking-wide text-slate-500">Verification</th>
                            <th class="w-[110px] px-4 py-3 text-left text-[11px] font-extrabold uppercase tracking-wide text-slate-500">Bookings</th>
                            <th class="w-[160px] px-4 py-3 text-left text-[11px] font-extrabold uppercase tracking-wide text-slate-500">Last Booking</th>
                            <th class="w-[120px] px-4 py-3 text-left text-[11px] font-extrabold uppercase tracking-wide text-slate-500">Joined</th>
                            <th class="w-[160px] px-4 py-3 text-right text-[11px] font-extrabold uppercase tracking-wide text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($customers as $customer)
                            @php
                                $isVerified = !is_null($customer->email_verified_at);
                                $latestBookingDate = $customer->latest_booking_date ? \Carbon\Carbon::parse($customer->latest_booking_date) : null;
                                $latestBookingStatusClass = $customer->latest_booking_status ? ($bookingStatusClasses[$customer->latest_booking_status] ?? 'bg-slate-100 text-slate-600') : 'bg-slate-100 text-slate-500';
                                $verificationClass = $verificationClasses[$isVerified ? 'verified' : 'unverified'];
                                $fullInitials = strtoupper(substr($customer->first_name, 0, 1) . substr($customer->last_name, 0, 1));
                                $streetPreview = $customer->street ? \Illuminate\Support\Str::limit($customer->street, 26) : '--';
                            @endphp
                            <tr class="align-top transition hover:bg-slate-50/80">
                                <td class="px-4 py-3">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-900 text-sm font-black text-white shadow-sm">
                                            {{ $fullInitials }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="truncate font-bold text-slate-900">{{ $customer->full_name }}</div>
                                            <div class="mt-1 text-xs text-slate-500">
                                                {{ $customer->username ? '@' . $customer->username : 'Email-only login' }}
                                            </div>
                                            <div class="mt-2 inline-flex items-center gap-2 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                                                <i class="fas fa-id-badge text-[10px]"></i>
                                                ID #{{ $customer->id }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="space-y-1.5">
                                        <div class="flex items-center gap-2 text-sm text-slate-700">
                                            <i class="fas fa-envelope text-xs text-slate-400"></i>
                                            <span class="break-all leading-snug">{{ $customer->email }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-sm text-slate-600">
                                            <i class="fas fa-phone text-xs text-slate-400"></i>
                                            <span>{{ $customer->phone ?: 'No phone provided' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden px-4 py-3">
                                    <div class="space-y-1 text-sm text-slate-600">
                                        <div class="font-semibold text-slate-800">{{ $customer->barangay_name }}</div>
                                        <div>{{ $streetPreview }}</div>
                                        <div>{{ $customer->city ?: 'Puerto Princesa City' }}{{ $customer->zip_code ? ' - ' . $customer->zip_code : '' }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-bold {{ $verificationClass }}">
                                        <i class="fas {{ $isVerified ? 'fa-circle-check' : 'fa-clock' }}"></i>
                                        {{ $isVerified ? 'Verified' : 'Pending verification' }}
                                    </span>
                                    <div class="mt-2 text-xs text-slate-500">
                                        {{ $isVerified ? optional($customer->email_verified_at)->format('M d, Y h:i A') : 'Awaiting email verification' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-xl font-black leading-none text-slate-900">{{ $customer->bookings_count }}</div>
                                    <div class="mt-1.5 text-xs text-slate-500">
                                        {{ $customer->bookings_count === 1 ? 'booking record' : 'booking records' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @if($customer->latest_booking_id)
                                        <div class="space-y-1.5">
                                            <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-bold {{ $latestBookingStatusClass }}">
                                                <i class="fas fa-calendar-check"></i>
                                                {{ ucwords(str_replace('_', ' ', $customer->latest_booking_status)) }}
                                            </span>
                                            <div class="text-xs text-slate-500">
                                                {{ $latestBookingDate?->format('M d, Y') ?? 'No bookings yet' }}
                                            </div>
                                        </div>
                                    @else
                                        <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">
                                            <i class="fas fa-calendar-xmark"></i>
                                            No bookings yet
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-800">{{ optional($customer->created_at)->format('M d, Y') }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ optional($customer->created_at)->diffForHumans() }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-nowrap items-start justify-end gap-2">
                                        <button type="button" onclick="openCustomerModal({{ $customer->id }})" class="inline-flex whitespace-nowrap items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700 transition hover:bg-blue-100">
                                            <i class="fas fa-eye"></i>
                                            Overview
                                        </button>

                                        <div class="relative" data-customer-action-wrap>
                                            <button type="button" onclick="toggleCustomerActions({{ $customer->id }}, event)" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600 transition hover:bg-slate-100" aria-label="Customer actions">
                                                <i class="fas fa-ellipsis"></i>
                                            </button>
                                            <div id="customer-actions-{{ $customer->id }}" data-customer-actions class="absolute right-0 top-12 z-20 hidden w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-[0_18px_40px_rgba(15,23,42,0.14)]">
                                                <button type="button" onclick="openCustomerModal({{ $customer->id }})" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                                    <i class="fas fa-circle-info text-slate-400"></i>
                                                    Account overview
                                                </button>
                                                <a href="{{ route('admin.customers.verification.edit', $customer) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                                    <i class="fas fa-shield-halved text-slate-400"></i>
                                                    Manage verification
                                                </a>
                                                @if($customer->bookings_count === 0)
                                                    <button type="button" onclick="openDeleteModal({{ $customer->id }})" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-red-600 transition hover:bg-red-50">
                                                        <i class="fas fa-trash-can text-red-400"></i>
                                                        Delete customer
                                                    </button>
                                                @else
                                                    <div class="rounded-xl bg-slate-50 px-3 py-2.5 text-xs leading-5 text-slate-500">
                                                        Protected from deletion because booking history already exists.
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-6 py-4">
                {{ $customers->links() }}
            </div>
        @else
            <div class="px-6 py-16 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-400">
                    <i class="fas fa-user-slash text-2xl"></i>
                </div>
                <h4 class="mt-5 text-lg font-extrabold text-slate-900">
                    {{ $hasActiveFilters ? 'No customers match the current filters' : 'No customers yet' }}
                </h4>
                <p class="mx-auto mt-2 max-w-xl text-sm leading-7 text-slate-500">
                    {{ $hasActiveFilters
                        ? 'Try broadening the search terms or clearing filters to bring more customer records back into view.'
                        : 'Customer accounts will appear here after new clients register and begin using the platform.' }}
                </p>
                @if($hasActiveFilters)
                    <a href="{{ route('admin.customers') }}" class="mt-6 inline-flex items-center gap-2 rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700">
                        <i class="fas fa-rotate-left"></i>
                        Clear Filters
                    </a>
                @endif
            </div>
        @endif
    </section>
</div>

<div id="customerDetailModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm">
    <div class="relative max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-[32px] border border-slate-200 bg-white shadow-[0_30px_80px_rgba(15,23,42,0.24)]">
        <div class="border-b border-slate-100 px-6 py-5 sm:px-8">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div id="detail-avatar" class="flex h-14 w-14 items-center justify-center rounded-3xl bg-primary-600 text-lg font-black text-white shadow-sm">--</div>
                    <div>
                        <div class="text-xs font-extrabold uppercase tracking-[0.16em] text-slate-400">Customer Overview</div>
                        <h3 id="detail-name" class="mt-1 text-2xl font-black text-slate-900">Customer Name</h3>
                        <div id="detail-email" class="mt-1 text-sm text-slate-500">customer@email.com</div>
                    </div>
                </div>
                <button type="button" onclick="closeCustomerModal()" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-100" aria-label="Close customer overview">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
        </div>

        <div class="grid gap-6 px-6 py-6 sm:px-8 xl:grid-cols-[minmax(0,1fr)_300px]">
            <div class="space-y-6">
                <section class="rounded-[28px] border border-slate-200 bg-slate-50 px-5 py-5">
                    <div class="flex flex-wrap items-center gap-3">
                        <span id="detail-verification-label" class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Verified</span>
                        <span id="detail-bookings-count" class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">0 bookings</span>
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-2xl border border-white bg-white px-4 py-3">
                            <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Verified At</div>
                            <div id="detail-verification-date" class="mt-2 text-sm font-semibold text-slate-800">Email not yet verified</div>
                        </div>
                        <div class="rounded-2xl border border-white bg-white px-4 py-3">
                            <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Last Booking</div>
                            <div id="detail-last-booking-date" class="mt-2 text-sm font-semibold text-slate-800">No bookings yet</div>
                        </div>
                    </div>
                </section>

                <section class="rounded-[28px] border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="border-b border-slate-100 px-5 py-4">
                        <h4 class="text-base font-extrabold text-slate-900">Account Information</h4>
                    </div>
                    <div class="grid gap-4 px-5 py-5 sm:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Login Identifier</div>
                            <div id="detail-username" class="mt-2 text-sm font-semibold text-slate-800">Email only</div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Phone</div>
                            <div id="detail-phone" class="mt-2 text-sm font-semibold text-slate-800">Not provided</div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Gender</div>
                            <div id="detail-gender" class="mt-2 text-sm font-semibold text-slate-800">Not specified</div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Joined</div>
                            <div id="detail-joined-date" class="mt-2 text-sm font-semibold text-slate-800">--</div>
                            <div id="detail-joined-relative" class="mt-1 text-xs text-slate-500">--</div>
                        </div>
                    </div>
                </section>

                <section class="rounded-[28px] border border-slate-200 bg-white shadow-sm overflow-hidden">
                    <div class="border-b border-slate-100 px-5 py-4">
                        <h4 class="text-base font-extrabold text-slate-900">Address Details</h4>
                    </div>
                    <div class="grid gap-4 px-5 py-5 sm:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Barangay</div>
                            <div id="detail-barangay" class="mt-2 text-sm font-semibold text-slate-800">--</div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Street</div>
                            <div id="detail-street" class="mt-2 text-sm font-semibold text-slate-800">--</div>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 sm:col-span-2">
                            <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">City and ZIP Code</div>
                            <div id="detail-city-zip" class="mt-2 text-sm font-semibold text-slate-800">--</div>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="space-y-4">
                <section class="rounded-[28px] border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <h4 class="text-base font-extrabold text-slate-900">Actions</h4>
                    <p class="mt-1 text-sm text-slate-500">Jump into the related admin workflows for this customer.</p>
                    <div class="mt-5 flex flex-col gap-3">
                        <a id="detail-verification-link" href="#" class="inline-flex items-center justify-center gap-2 rounded-full bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700">
                            <i class="fas fa-shield-halved"></i>
                            Manage Verification
                        </a>
                        <a id="detail-last-booking-link" href="#" class="hidden items-center justify-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-bold text-blue-700 transition hover:bg-blue-100">
                            <i class="fas fa-arrow-up-right-from-square"></i>
                            Open Latest Booking
                        </a>
                    </div>
                </section>

                <section class="rounded-[28px] border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <h4 class="text-base font-extrabold text-slate-900">Admin Note</h4>
                    <p class="mt-3 text-sm leading-7 text-slate-500">
                        Profiles with booking history stay protected so operational records, analytics, and proof of service remain complete and defense-ready.
                    </p>
                </section>
            </aside>
        </div>
    </div>
</div>

<div id="deleteCustomerModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/55 px-4 py-6 backdrop-blur-sm">
    <div class="w-full max-w-lg rounded-[30px] border border-slate-200 bg-white shadow-[0_30px_80px_rgba(15,23,42,0.24)]">
        <div class="border-b border-slate-100 px-6 py-5">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-red-50 text-red-600">
                    <i class="fas fa-triangle-exclamation"></i>
                </div>
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900">Delete Customer</h3>
                    <p class="mt-1 text-sm text-slate-500">This action permanently removes the account if it has no booking history.</p>
                </div>
            </div>
        </div>
        <div class="px-6 py-6">
            <p id="deleteCustomerMessage" class="text-sm leading-7 text-slate-600">Are you sure you want to delete this customer?</p>
            <form id="deleteCustomerForm" method="POST" action="#" class="mt-6 flex flex-wrap justify-end gap-3">
                @csrf
                @method('DELETE')
                <button type="button" onclick="closeDeleteModal()" class="inline-flex items-center gap-2 rounded-full border border-slate-300 px-5 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-100">
                    <i class="fas fa-xmark"></i>
                    Cancel
                </button>
                <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-red-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-red-700">
                    <i class="fas fa-trash-can"></i>
                    Delete Customer
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const customerDirectory = @json($customerDirectory);

    const customerDetailModal = document.getElementById('customerDetailModal');
    const deleteCustomerModal = document.getElementById('deleteCustomerModal');
    const deleteCustomerForm = document.getElementById('deleteCustomerForm');
    const deleteCustomerMessage = document.getElementById('deleteCustomerMessage');

    function openModal(modal) {
        if (!modal) {
            return;
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal(modal) {
        if (!modal) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');

        const hasOpenModal = [customerDetailModal, deleteCustomerModal].some((item) => item && !item.classList.contains('hidden'));
        if (!hasOpenModal) {
            document.body.classList.remove('overflow-hidden');
        }
    }

    function closeCustomerActionMenus() {
        document.querySelectorAll('[data-customer-actions]').forEach((menu) => menu.classList.add('hidden'));
    }

    function toggleCustomerActions(customerId, event) {
        event.stopPropagation();

        const menu = document.getElementById(`customer-actions-${customerId}`);
        const shouldOpen = menu.classList.contains('hidden');

        closeCustomerActionMenus();

        if (shouldOpen) {
            menu.classList.remove('hidden');
        }
    }

    function openCustomerModal(customerId) {
        closeCustomerActionMenus();

        const customer = customerDirectory[customerId];
        if (!customer) {
            return;
        }

        const initials = customer.name
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .map((part) => part.charAt(0))
            .join('')
            .toUpperCase();

        document.getElementById('detail-avatar').textContent = initials || '--';
        document.getElementById('detail-name').textContent = customer.name;
        document.getElementById('detail-email').textContent = customer.email;
        document.getElementById('detail-verification-label').innerHTML = customer.verification_label === 'Verified'
            ? '<i class="fas fa-circle-check"></i> Verified'
            : '<i class="fas fa-clock"></i> Pending verification';
        document.getElementById('detail-verification-label').className = customer.verification_label === 'Verified'
            ? 'inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700'
            : 'inline-flex items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700';
        document.getElementById('detail-verification-date').textContent = customer.verification_date;
        document.getElementById('detail-bookings-count').innerHTML = `<i class="fas fa-calendar-check"></i> ${customer.bookings_count} booking${customer.bookings_count === 1 ? '' : 's'}`;
        document.getElementById('detail-last-booking-date').textContent = `${customer.last_booking_date} - ${customer.last_booking_status}`;
        document.getElementById('detail-joined-date').textContent = customer.joined_date;
        document.getElementById('detail-joined-relative').textContent = customer.joined_relative;
        document.getElementById('detail-username').textContent = customer.login_identifier;
        document.getElementById('detail-phone').textContent = customer.phone;
        document.getElementById('detail-gender').textContent = customer.gender;
        document.getElementById('detail-barangay').textContent = customer.barangay || '--';
        document.getElementById('detail-street').textContent = customer.street || '--';
        document.getElementById('detail-city-zip').textContent = [customer.city || 'Puerto Princesa City', customer.zip_code || ''].filter(Boolean).join(' - ');

        const verificationLink = document.getElementById('detail-verification-link');
        verificationLink.href = customer.verification_url;

        const lastBookingLink = document.getElementById('detail-last-booking-link');
        if (customer.last_booking_url) {
            lastBookingLink.href = customer.last_booking_url;
            lastBookingLink.classList.remove('hidden');
            lastBookingLink.classList.add('inline-flex');
        } else {
            lastBookingLink.href = '#';
            lastBookingLink.classList.add('hidden');
            lastBookingLink.classList.remove('inline-flex');
        }

        openModal(customerDetailModal);
    }

    function closeCustomerModal() {
        closeModal(customerDetailModal);
    }

    function openDeleteModal(customerId) {
        closeCustomerActionMenus();

        const customer = customerDirectory[customerId];
        if (!customer || !customer.can_delete) {
            return;
        }

        deleteCustomerForm.action = customer.delete_url;
        deleteCustomerMessage.textContent = `Delete ${customer.name}? This permanently removes the account because it has no booking history yet.`;

        openModal(deleteCustomerModal);
    }

    function closeDeleteModal() {
        closeModal(deleteCustomerModal);
    }

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-customer-action-wrap]')) {
            closeCustomerActionMenus();
        }
    });

    [customerDetailModal, deleteCustomerModal].forEach((modal) => {
        if (!modal) {
            return;
        }

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeCustomerActionMenus();
            closeCustomerModal();
            closeDeleteModal();
        }
    });
</script>
@endpush
