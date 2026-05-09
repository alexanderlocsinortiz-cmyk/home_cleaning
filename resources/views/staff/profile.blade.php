@extends('layouts.staff')
@section('title', 'My Profile')
@section('page-title', 'Profile')
@section('page-subtitle', 'Manage your contact information')

@section('content')
@php
    $initials = $user->initials;
    $tips = [
        'Keep your phone number updated so admins can reach you quickly for schedule changes.',
        'Your assigned barangay helps align service coverage and nearby job assignments.',
        'A complete staff profile supports smoother coordination across active bookings.',
    ];
@endphp

<div class="cleanflow-page-shell min-h-[calc(100vh-81px)] px-4 py-6 sm:px-6 sm:py-8">
    <div class="mx-auto max-w-6xl space-y-6">
        @if (session('success'))
            <div class="cleanflow-alert cleanflow-alert--success flex items-start gap-3">
                <i class="fas fa-circle-check mt-0.5 text-base"></i>
                <div>
                    <p class="text-sm font-semibold">Profile updated successfully.</p>
                    <p class="mt-1 text-sm text-emerald-800/80">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="cleanflow-alert cleanflow-alert--error">
                <div class="flex items-start gap-3">
                    <i class="fas fa-circle-exclamation mt-0.5 text-base"></i>
                    <div>
                        <p class="text-sm font-semibold">Please review your profile details.</p>
                        <ul class="mt-2 space-y-1 text-sm text-red-700/90">
                            @foreach ($errors->all() as $error)
                                <li class="flex items-start gap-2">
                                    <span class="mt-1 h-1.5 w-1.5 rounded-full bg-red-400"></span>
                                    <span>{{ $error }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8 lg:px-10">
            <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl space-y-4">
                    <span class="cleanflow-kicker">
                        <i class="fas fa-id-badge text-[0.75rem]"></i>
                        Staff profile
                    </span>
                    <div class="space-y-3">
                        <h1 class="max-w-2xl text-3xl font-black tracking-tight sm:text-4xl">
                            Keep your staff details ready for daily operations
                        </h1>
                        <p class="max-w-2xl text-sm leading-7 text-white/80 sm:text-base">
                            Update your contact information and assigned barangay so admin coordination, job routing,
                            and daily communication stay accurate.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3 text-sm text-white/85">
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-phone text-xs"></i>
                            Faster schedule updates
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-location-dot text-xs"></i>
                            Clear service coverage
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-user-check text-xs"></i>
                            Better assignment readiness
                        </span>
                    </div>
                </div>

                <a href="{{ route('staff.dashboard') }}" class="cleanflow-ghost-button self-start xl:self-auto">
                    <i class="fas fa-arrow-left text-xs"></i>
                    Back to dashboard
                </a>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <form action="{{ route('staff.profile.update') }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <section class="cleanflow-panel p-6 md:p-7">
                    <div class="mb-6 flex flex-col gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                                <i class="fas fa-user text-lg"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">Personal information</h2>
                                <p class="mt-1 text-sm text-slate-500">These details are used across your staff assignments and communication updates.</p>
                            </div>
                        </div>
                        <div class="rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">
                            Staff account
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="space-y-2">
                            <label for="first_name" class="text-sm font-semibold text-slate-700">First name</label>
                            <input
                                id="first_name"
                                type="text"
                                name="first_name"
                                value="{{ old('first_name', $user->first_name) }}"
                                class="client-profile-input"
                            >
                            @error('first_name')
                                <p class="text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="last_name" class="text-sm font-semibold text-slate-700">Last name</label>
                            <input
                                id="last_name"
                                type="text"
                                name="last_name"
                                value="{{ old('last_name', $user->last_name) }}"
                                class="client-profile-input"
                            >
                            @error('last_name')
                                <p class="text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="email_preview" class="text-sm font-semibold text-slate-700">Email address</label>
                            <input
                                id="email_preview"
                                type="email"
                                value="{{ $user->email }}"
                                class="client-profile-input"
                                disabled
                            >
                            <p class="text-xs text-slate-500">Email is fixed because it is tied to staff login access.</p>
                        </div>

                        <div class="space-y-2">
                            <label for="phone" class="text-sm font-semibold text-slate-700">Phone number</label>
                            <input
                                id="phone"
                                type="text"
                                name="phone"
                                value="{{ old('phone', $user->phone) }}"
                                class="client-profile-input"
                                placeholder="09XXXXXXXXX"
                            >
                            @error('phone')
                                <p class="text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-5 space-y-2">
                        <label for="barangay" class="text-sm font-semibold text-slate-700">Assigned barangay</label>
                        <select id="barangay" name="barangay" class="client-profile-input">
                            @foreach ($barangays as $value => $label)
                                <option value="{{ $value }}" {{ old('barangay', $user->barangay) === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('barangay')
                            <p class="text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </section>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-primary px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-emerald-200/70 transition hover:-translate-y-0.5 hover:bg-primary-dark"
                    >
                        <i class="fas fa-floppy-disk text-xs"></i>
                        Save changes
                    </button>
                    <a
                        href="{{ route('staff.dashboard') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-6 py-3.5 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900"
                    >
                        <i class="fas fa-xmark text-xs"></i>
                        Cancel
                    </a>
                </div>
            </form>

            <aside class="space-y-6 xl:sticky xl:top-28">
                <section class="cleanflow-panel p-6">
                    <div class="mb-5 flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary-600 text-sm font-bold text-white shadow-md">
                            {{ $initials }}
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Current staff profile</h2>
                            <p class="text-sm text-slate-500">A quick reference for the account details currently on file.</p>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-100 bg-slate-50/90 p-5 text-center">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-primary-600 text-2xl font-black text-white shadow-lg">
                            {{ $initials }}
                        </div>
                        <div class="mt-4 text-lg font-bold text-slate-900">{{ $user->display_name }}</div>
                        <div class="mt-1 text-sm text-slate-500">{{ $user->email }}</div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <div class="client-profile-summary-row">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-white text-slate-500 shadow-sm">
                                    <i class="fas fa-phone text-sm"></i>
                                </span>
                                <span class="text-sm font-medium text-slate-500">Phone</span>
                            </div>
                            <span class="client-profile-summary-value text-sm">{{ $user->phone ?? 'Not set' }}</span>
                        </div>

                        <div class="client-profile-summary-row">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-white text-slate-500 shadow-sm">
                                    <i class="fas fa-location-dot text-sm"></i>
                                </span>
                                <span class="text-sm font-medium text-slate-500">Barangay</span>
                            </div>
                            <span class="client-profile-summary-value text-sm">{{ $barangays[$user->barangay] ?? ucfirst($user->barangay ?? 'N/A') }}</span>
                        </div>
                    </div>
                </section>

                <section class="cleanflow-panel border border-accent-100 bg-accent-50/80 p-6">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-accent-600 shadow-sm">
                            <i class="fas fa-lightbulb text-base"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Quick tips</h2>
                            <p class="text-sm text-slate-500">A complete staff profile makes daily coordination smoother.</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        @foreach ($tips as $tip)
                            <div class="client-profile-tip">
                                <span class="client-profile-tip-icon">
                                    <i class="fas fa-check text-xs"></i>
                                </span>
                                <p class="text-sm leading-6 text-slate-600">{{ $tip }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            </aside>
        </div>
    </div>
</div>
@endsection
