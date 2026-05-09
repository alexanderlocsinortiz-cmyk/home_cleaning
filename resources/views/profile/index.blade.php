@extends('layouts.app')
@section('title', 'My Profile')

@section('content')
@php
    $birthday = optional($user->date_of_birth)->format('M d, Y') ?: 'Not set';
    $gender = $user->gender ? ucfirst(str_replace('_', ' ', $user->gender)) : 'Not set';
    $barangayLabel = $user->barangay ? ($barangays[$user->barangay] ?? $user->barangay) : 'Not set';
    $address = $user->street && $user->barangay && $user->zip_code
        ? $user->street . ', ' . ($barangays[$user->barangay] ?? $user->barangay) . ', ' . $user->city . ' ' . $user->zip_code
        : 'Not set';
    $isVerified = ! is_null($user->email_verified_at);
@endphp

<div class="cleanflow-page-shell px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-6xl space-y-6">
        @if(session('success'))
            <div class="cleanflow-alert cleanflow-alert--success flex items-start gap-3">
                <i class="fas fa-check-circle mt-0.5"></i>
                <div>
                    <div class="text-sm font-bold">Profile updated</div>
                    <div class="text-sm">{{ session('success') }}</div>
                </div>
            </div>
        @endif

        <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8">
            <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl">
                    <span class="cleanflow-kicker">
                        <i class="fas fa-user-circle"></i>
                        Account Overview
                    </span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">{{ $user->display_name }}</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-white/82 sm:text-base">
                        Review your account details, contact information, and booking readiness from one cleaner profile summary.
                    </p>
                </div>
                <div class="flex flex-col gap-3 rounded-3xl border border-white/18 bg-white/10 px-5 py-4 shadow-[0_18px_40px_rgba(15,23,42,0.15)] backdrop-blur xl:min-w-[280px]">
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-bold {{ $isVerified ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-amber-200 bg-amber-50 text-amber-900' }}">
                            <i class="fas {{ $isVerified ? 'fa-circle-check' : 'fa-clock' }}"></i>
                            {{ $isVerified ? 'Verified account' : 'Pending verification' }}
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-bold text-white/85">
                            <i class="fas fa-calendar-day"></i>
                            Member since {{ optional($user->created_at)->format('M Y') }}
                        </span>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50">
                        <i class="fas fa-pen"></i>
                        Edit Profile
                    </a>
                </div>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <section class="cleanflow-panel overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="text-lg font-extrabold text-slate-900">Profile Details</h2>
                    <p class="mt-1 text-sm text-slate-500">Your stored contact, identity, and location details used throughout the platform.</p>
                </div>

                <div class="grid gap-4 px-6 py-6 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Full Name</div>
                        <div class="mt-2 text-sm font-semibold text-slate-900">{{ $user->display_name }}</div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Email</div>
                        <div class="mt-2 break-all text-sm font-semibold text-slate-900">{{ $user->email }}</div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Phone</div>
                        <div class="mt-2 text-sm font-semibold text-slate-900">{{ $user->phone ?? 'Not set' }}</div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Birthday</div>
                        <div class="mt-2 text-sm font-semibold text-slate-900">{{ $birthday }}</div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Gender</div>
                        <div class="mt-2 text-sm font-semibold text-slate-900">{{ $gender }}</div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Barangay</div>
                        <div class="mt-2 text-sm font-semibold text-slate-900">{{ $barangayLabel }}</div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 sm:col-span-2">
                        <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Address</div>
                        <div class="mt-2 text-sm font-semibold leading-6 text-slate-900">{{ $address }}</div>
                    </div>
                </div>
            </section>

            <aside class="space-y-4">
                <section class="cleanflow-panel px-5 py-5">
                    <h2 class="text-base font-extrabold text-slate-900">Account Status</h2>
                    <p class="mt-1 text-sm text-slate-500">A quick read on whether your account is ready for bookings and updates.</p>

                    <div class="mt-5 space-y-4">
                        <div class="client-profile-summary-row">
                            <div>
                                <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Verification</div>
                                <div class="mt-1 text-sm font-semibold text-slate-900">{{ $isVerified ? 'Verified' : 'Pending verification' }}</div>
                            </div>
                            <span class="client-profile-summary-value {{ $isVerified ? 'text-emerald-700' : 'text-amber-700' }}">
                                {{ $isVerified ? 'Ready' : 'Needs email confirmation' }}
                            </span>
                        </div>

                        <div class="client-profile-summary-row">
                            <div>
                                <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Contact</div>
                                <div class="mt-1 text-sm font-semibold text-slate-900">Phone number</div>
                            </div>
                            <span class="client-profile-summary-value">{{ $user->phone ? 'Available' : 'Missing' }}</span>
                        </div>

                        <div class="client-profile-summary-row">
                            <div>
                                <div class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-slate-400">Address</div>
                                <div class="mt-1 text-sm font-semibold text-slate-900">Service location details</div>
                            </div>
                            <span class="client-profile-summary-value">{{ $address !== 'Not set' ? 'Saved' : 'Incomplete' }}</span>
                        </div>
                    </div>
                </section>

                <section class="cleanflow-panel px-5 py-5">
                    <h2 class="text-base font-extrabold text-slate-900">Profile Tip</h2>
                    <div class="mt-4 client-profile-tip">
                        <span class="client-profile-tip-icon"><i class="fas fa-lightbulb"></i></span>
                        <p class="text-sm leading-7 text-slate-500">
                            Keeping your address and phone details current helps the team confirm bookings faster and reduces booking delays during review.
                        </p>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</div>
@endsection
