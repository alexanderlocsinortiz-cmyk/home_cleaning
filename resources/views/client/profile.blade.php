@extends('layouts.client')
@section('title', 'My Profile')

@section('content')
@php
    $birthday = optional($user->date_of_birth)->format('M d, Y') ?: 'Not set';
    $gender = $user->gender ? ucfirst(str_replace('_', ' ', $user->gender)) : 'Not set';
    $address = $user->street && $user->barangay && $user->zip_code
        ? $user->street . ', ' . ucwords(str_replace('_', ' ', $user->barangay)) . ', ' . $user->city . ' ' . $user->zip_code
        : 'Not set';
    $initials = $user->initials;
    $memberSince = optional($user->created_at)->format('M d, Y') ?: 'Not set';
    $profileItems = [
        ['icon' => 'fa-user', 'label' => 'Full name', 'value' => $user->display_name],
        ['icon' => 'fa-envelope', 'label' => 'Email', 'value' => $user->email ?: 'Not set'],
        ['icon' => 'fa-phone', 'label' => 'Phone', 'value' => $user->phone ?: 'Not set'],
        ['icon' => 'fa-cake-candles', 'label' => 'Birthday', 'value' => $birthday],
        ['icon' => 'fa-venus-mars', 'label' => 'Gender', 'value' => $gender],
        ['icon' => 'fa-location-dot', 'label' => 'Address', 'value' => $address],
        ['icon' => 'fa-calendar-days', 'label' => 'Member since', 'value' => $memberSince],
    ];
    $profileChecks = [
        [
            'label' => 'Contact ready',
            'value' => $user->phone ? 'Complete' : 'Needs update',
            'classes' => $user->phone ? 'border-accent-200 bg-accent-50 text-accent-700' : 'border-amber-200 bg-amber-50 text-amber-700',
        ],
        [
            'label' => 'Address ready',
            'value' => $address !== 'Not set' ? 'Complete' : 'Needs update',
            'classes' => $address !== 'Not set' ? 'border-accent-200 bg-accent-50 text-accent-700' : 'border-amber-200 bg-amber-50 text-amber-700',
        ],
        [
            'label' => 'Age verified',
            'value' => $user->date_of_birth ? 'On file' : 'Needs update',
            'classes' => $user->date_of_birth ? 'border-secondary-200 bg-secondary-50 text-secondary-700' : 'border-amber-200 bg-amber-50 text-amber-700',
        ],
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

        <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8 lg:px-10">
            <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl space-y-4">
                    <span class="cleanflow-kicker">
                        <i class="fas fa-address-card text-[0.75rem]"></i>
                        Client profile
                    </span>
                    <div class="space-y-3">
                        <h1 class="max-w-2xl text-3xl font-black tracking-tight sm:text-4xl">
                            Your account details, all in one place
                        </h1>
                        <p class="max-w-2xl text-sm leading-7 text-white/80 sm:text-base">
                            Review the information that supports your bookings, cleaner assignments, and service
                            updates before your next appointment.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3 text-sm text-white/85">
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-phone-volume text-xs"></i>
                            Faster confirmations
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-location-arrow text-xs"></i>
                            Cleaner arrivals stay accurate
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-shield-check text-xs"></i>
                            Review-ready profile
                        </span>
                    </div>
                </div>

                <a href="{{ route('client.profile.edit') }}" class="cleanflow-ghost-button self-start xl:self-auto">
                    <i class="fas fa-user-pen text-xs"></i>
                    Edit profile
                </a>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <section class="cleanflow-panel p-6 md:p-7">
                <div class="mb-6 flex flex-col gap-4 border-b border-slate-100 pb-5 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Profile details</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            These are the details currently tied to your CleanFlow account.
                        </p>
                    </div>
                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Active profile
                    </span>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ($profileItems as $item)
                        <div class="rounded-[1.35rem] border border-slate-100 bg-slate-50/85 p-4">
                            <div class="flex items-start gap-3">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-slate-500 shadow-sm">
                                    <i class="fas {{ $item['icon'] }} text-sm"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">{{ $item['label'] }}</p>
                                    <p class="mt-2 text-sm font-semibold leading-6 text-slate-700">{{ $item['value'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <aside class="space-y-6 xl:sticky xl:top-28">
                <section class="cleanflow-panel p-6">
                    <div class="rounded-[1.6rem] border border-slate-100 bg-slate-50/90 p-5 text-center">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-primary-600 text-2xl font-black text-white shadow-lg">
                            {{ $initials }}
                        </div>
                        <div class="mt-4 text-lg font-bold text-slate-900">{{ $user->display_name }}</div>
                        <div class="mt-1 text-sm text-slate-500">{{ $user->email }}</div>
                        <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Booking readiness</p>
                            <div class="mt-3 space-y-2">
                                @foreach ($profileChecks as $check)
                                    <div class="flex items-center justify-between gap-3 rounded-2xl border px-3 py-2.5 text-sm font-medium {{ $check['classes'] }}">
                                        <span>{{ $check['label'] }}</span>
                                        <span>{{ $check['value'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>

                <section class="cleanflow-panel border border-accent-100 bg-accent-50/80 p-6">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-accent-600 shadow-sm">
                            <i class="fas fa-sparkles text-base"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Helpful next step</h2>
                            <p class="text-sm text-slate-500">A complete profile keeps booking review and cleaner assignment smooth.</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="client-profile-tip">
                            <span class="client-profile-tip-icon">
                                <i class="fas fa-check text-xs"></i>
                            </span>
                            <p class="text-sm leading-6 text-slate-600">
                                If any status shows "Needs update," open the profile editor before creating your next booking.
                            </p>
                        </div>
                        <div class="client-profile-tip">
                            <span class="client-profile-tip-icon">
                                <i class="fas fa-arrow-right text-xs"></i>
                            </span>
                            <p class="text-sm leading-6 text-slate-600">
                                Keeping your address current helps the assigned cleaner navigate and arrive on time.
                            </p>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</div>
@endsection
