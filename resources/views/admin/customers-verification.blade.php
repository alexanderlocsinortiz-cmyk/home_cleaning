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
@endphp

<div class="admin-page-content cleanflow-page-shell space-y-6 p-6">
    @if(session('success'))
        <div class="cleanflow-alert cleanflow-alert--success flex items-start gap-3">
            <i class="fas fa-check-circle mt-0.5"></i>
            <div>
                <div class="text-sm font-bold">Verification updated</div>
                <div class="text-sm">{{ session('success') }}</div>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="cleanflow-alert cleanflow-alert--error">
            <div class="text-sm font-bold">Please review the verification form.</div>
            <div class="mt-1 text-sm">The verification status could not be saved because of validation errors.</div>
        </div>
    @endif

    <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8">
        <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-3xl">
                <span class="cleanflow-kicker">
                    <i class="fas fa-shield-halved"></i>
                    Verification Review
                </span>
                <h2 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">{{ $customer->full_name }}</h2>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-white/82 sm:text-base">
                    Manage email verification only. Customer profile details remain read-only in this workflow so verification decisions stay focused and auditable.
                </p>
            </div>
            <div class="flex flex-col gap-3 rounded-3xl border border-white/18 bg-white/10 px-5 py-4 shadow-[0_18px_40px_rgba(15,23,42,0.15)] backdrop-blur xl:min-w-[260px]">
                <a href="{{ route('admin.customers') }}" class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50">
                    <i class="fas fa-arrow-left"></i>
                    Back to Customers
                </a>
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-bold {{ $isVerified ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-800' }}">
                        <i class="fas {{ $isVerified ? 'fa-circle-check' : 'fa-clock' }}"></i>
                        {{ $isVerified ? 'Verified' : 'Pending verification' }}
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-bold text-white/85">
                        <i class="fas fa-calendar-check"></i>
                        {{ $customer->bookings_count }} booking{{ $customer->bookings_count === 1 ? '' : 's' }}
                    </span>
                </div>
            </div>
        </div>
    </section>

    <div class="rounded-3xl border px-5 py-4 text-sm leading-7 {{ $isVerified ? 'border-blue-200 bg-blue-50 text-blue-800' : 'border-amber-200 bg-amber-50 text-amber-900' }}">
        <div class="flex items-start gap-3">
            <i class="fas {{ $isVerified ? 'fa-shield-halved' : 'fa-triangle-exclamation' }} mt-0.5"></i>
            <div>
                {{ $isVerified
                    ? 'This customer currently has verified-client access. Marking the account as pending verification will remove that verified status until the email is confirmed again.'
                    : 'This customer is still pending verification. Mark the account as verified only when the verification state has been confirmed by the admin team.' }}
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
        <section class="rounded-[28px] border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 px-6 py-5">
                <h3 class="text-lg font-extrabold text-slate-900">Verification Management</h3>
                <p class="mt-1 text-sm text-slate-500">Update the customer's verification status without editing personal profile fields from this screen.</p>
            </div>

            <form method="POST" action="{{ route('admin.customers.verification.update', $customer) }}" class="space-y-6 px-6 py-6">
                @csrf
                @method('PUT')

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Customer Email</label>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800">{{ $customer->email }}</div>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Login Method</label>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800">{{ $customer->username ? '@' . $customer->username : 'Email only' }}</div>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Joined</label>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800">{{ $customer->created_at->format('F d, Y') }}</div>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Verified At</label>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800">{{ $customer->email_verified_at ? $customer->email_verified_at->format('F d, Y h:i A') : 'Not yet verified' }}</div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-5">
                    <label for="verification_status" class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Verification Status</label>
                    <select id="verification_status" name="verification_status" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
                        <option value="verified" {{ $currentVerificationStatus === 'verified' ? 'selected' : '' }}>Verified</option>
                        <option value="pending" {{ $currentVerificationStatus === 'pending' ? 'selected' : '' }}>Pending verification</option>
                    </select>
                    @error('verification_status')<div class="mt-2 text-xs text-red-500">{{ $message }}</div>@enderror

                    <div class="mt-4 grid gap-3">
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
                            <div class="text-xs font-extrabold uppercase tracking-[0.16em] text-emerald-700">Verified</div>
                            <div class="mt-1 text-sm leading-6 text-emerald-900">Use when the account should be treated as email-verified and ready for verified-client access.</div>
                        </div>
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                            <div class="text-xs font-extrabold uppercase tracking-[0.16em] text-amber-700">Pending verification</div>
                            <div class="mt-1 text-sm leading-6 text-amber-900">Use when the verification state should be cleared and the customer should return to a pending email verification status.</div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-4">
                    <a href="{{ route('admin.customers') }}" class="inline-flex items-center gap-2 rounded-full border border-slate-300 px-5 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-100">
                        <i class="fas fa-xmark"></i>
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700">
                        <i class="fas fa-shield-halved"></i>
                        Save Verification
                    </button>
                </div>
            </form>
        </section>

        <div class="space-y-4">
            <section class="rounded-[28px] border border-slate-200 bg-white px-5 py-5 shadow-sm">
                <h3 class="text-base font-extrabold text-slate-900">Verification Summary</h3>
                <p class="mt-1 text-sm text-slate-500">Read-only account context for verification decisions.</p>
                <div class="mt-5 space-y-4">
                    <div class="border-t border-slate-100 pt-4">
                        <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Customer Name</div>
                        <div class="mt-1 text-sm font-bold text-slate-900">{{ $customer->full_name }}</div>
                    </div>
                    <div class="border-t border-slate-100 pt-4">
                        <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Registration Month</div>
                        <div class="mt-1 text-sm font-bold text-slate-900">{{ $customer->created_at->format('F Y') }}</div>
                    </div>
                    <div class="border-t border-slate-100 pt-4">
                        <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Latest Booking Status</div>
                        <div class="mt-1 text-sm font-bold text-slate-900">{{ $latestBookingStatus }}</div>
                    </div>
                    <div class="border-t border-slate-100 pt-4">
                        <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Operational Note</div>
                        <div class="mt-1 text-sm leading-6 text-slate-500">Profiles with booking history should remain in the system to preserve operations, reports, and defense-ready records.</div>
                    </div>
                </div>
            </section>

            @if($latestBooking)
                <section class="rounded-[28px] border border-slate-200 bg-white px-5 py-5 shadow-sm">
                    <h3 class="text-base font-extrabold text-slate-900">Latest Booking</h3>
                    <p class="mt-1 text-sm text-slate-500">Most recent service request linked to this customer.</p>
                    <div class="mt-4 rounded-3xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="text-sm font-black text-slate-900">CF-{{ str_pad($latestBooking->id, 5, '0', STR_PAD_LEFT) }}</div>
                        <div class="mt-1 text-sm text-slate-500">{{ $latestBooking->service_label }}</div>
                        <div class="mt-1 text-sm text-slate-500">{{ $latestBookingDate?->format('M d, Y') }} at {{ \Carbon\Carbon::parse($latestBooking->scheduled_time)->format('h:i A') }}</div>
                        <a href="{{ route('bookings.show', $latestBooking->id) }}" class="mt-4 inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-bold text-blue-700 transition hover:bg-blue-100">
                            <i class="fas fa-arrow-up-right-from-square"></i>
                            Open Booking
                        </a>
                    </div>
                </section>
            @endif
        </div>
    </div>
</div>
@endsection
