@extends('layouts.app')
@section('title', 'Edit Profile')

@section('content')
@php
    $currentBarangay = old('barangay', $user->barangay);
    $currentGender = old('gender', $user->gender);
@endphp

<div class="cleanflow-page-shell px-4 py-8 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-6xl space-y-6">
        @if ($errors->any())
            <div class="cleanflow-alert cleanflow-alert--error">
                <div class="text-sm font-bold">Please review your profile details.</div>
                <div class="mt-2 space-y-1 text-sm">
                    @foreach ($errors->all() as $error)
                        <div class="flex items-start gap-2">
                            <i class="fas fa-circle mt-1 text-[7px]"></i>
                            <span>{{ $error }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8">
            <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl">
                    <span class="cleanflow-kicker">
                        <i class="fas fa-user-cog"></i>
                        Edit Profile
                    </span>
                    <h1 class="mt-4 text-3xl font-black tracking-tight sm:text-4xl">Keep your contact and location details booking-ready.</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-white/82 sm:text-base">
                        Update the information used for confirmations, service addresses, and account recovery without leaving the main profile workflow.
                    </p>
                </div>
                <div class="flex flex-col gap-3 rounded-3xl border border-white/18 bg-white/10 px-5 py-4 shadow-[0_18px_40px_rgba(15,23,42,0.15)] backdrop-blur xl:min-w-[280px]">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">Account Email</div>
                    <div class="text-sm font-semibold text-white break-all">{{ $user->email }}</div>
                    <a href="{{ route('profile.show') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-bold text-emerald-700 transition hover:bg-emerald-50">
                        <i class="fas fa-arrow-left"></i>
                        Back to Profile
                    </a>
                </div>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <section class="cleanflow-panel overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-5">
                    <h2 class="text-lg font-extrabold text-slate-900">Profile Form</h2>
                    <p class="mt-1 text-sm text-slate-500">Edit the account details used across booking, contact, and service coordination flows.</p>
                </div>

                <form action="{{ route('profile.update') }}" method="POST" class="space-y-6 px-6 py-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <div class="mb-4 flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <div class="text-base font-extrabold text-slate-900">Personal Information</div>
                                <div class="text-sm text-slate-500">Core identity and contact details.</div>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">First Name</label>
                                <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required class="client-profile-input">
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Last Name</label>
                                <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required class="client-profile-input">
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Phone</label>
                                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="09XXXXXXXXX" class="client-profile-input">
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Birthday</label>
                                <input type="date" name="date_of_birth" value="{{ old('date_of_birth', optional($user->date_of_birth)->format('Y-m-d')) }}" class="client-profile-input">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Gender</label>
                                <select name="gender" class="client-profile-input">
                                    <option value="">Select gender</option>
                                    <option value="male" {{ $currentGender === 'male' ? 'selected' : '' }}>Male</option>
                                    <option value="female" {{ $currentGender === 'female' ? 'selected' : '' }}>Female</option>
                                    <option value="prefer_not_to_say" {{ $currentGender === 'prefer_not_to_say' ? 'selected' : '' }}>Prefer not to say</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 pt-6">
                        <div class="mb-4 flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-cyan-50 text-cyan-700">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div>
                                <div class="text-base font-extrabold text-slate-900">Address</div>
                                <div class="text-sm text-slate-500">The location used for booking coordination and service dispatch.</div>
                            </div>
                        </div>

                        <div class="grid gap-4">
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Street</label>
                                <input type="text" name="street" value="{{ old('street', $user->street) }}" required class="client-profile-input">
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Barangay</label>
                                    <select name="barangay" required class="client-profile-input">
                                        <option value="">Select barangay</option>
                                        @foreach($barangays as $value => $label)
                                            <option value="{{ $value }}" {{ $currentBarangay === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">ZIP Code</label>
                                    <input type="text" name="zip_code" value="{{ old('zip_code', $user->zip_code) }}" required class="client-profile-input">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 pt-6">
                        <div class="mb-4 flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                                <i class="fas fa-lock"></i>
                            </div>
                            <div>
                                <div class="text-base font-extrabold text-slate-900">Account</div>
                                <div class="text-sm text-slate-500">Email stays read-only. Password changes are optional.</div>
                            </div>
                        </div>

                        <div class="grid gap-4">
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Email</label>
                                <input type="email" value="{{ $user->email }}" disabled class="client-profile-input">
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Current Password</label>
                                    <input type="password" name="current_password" class="client-profile-input">
                                </div>
                                <div>
                                    <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">New Password</label>
                                    <input type="password" name="new_password" class="client-profile-input">
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Confirm New Password</label>
                                <input type="password" name="new_password_confirmation" class="client-profile-input">
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-5">
                        <a href="{{ route('profile.show') }}" class="inline-flex items-center gap-2 rounded-full border border-slate-300 px-5 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-100">
                            <i class="fas fa-arrow-left"></i>
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </section>

            <aside class="space-y-4">
                <section class="cleanflow-panel px-5 py-5">
                    <h2 class="text-base font-extrabold text-slate-900">Update Tips</h2>
                    <div class="mt-4 space-y-3">
                        <div class="client-profile-tip">
                            <span class="client-profile-tip-icon"><i class="fas fa-phone"></i></span>
                            <p class="text-sm leading-7 text-slate-500">Keep your phone number current so the team can reach you quickly for schedule confirmations.</p>
                        </div>
                        <div class="client-profile-tip">
                            <span class="client-profile-tip-icon"><i class="fas fa-location-dot"></i></span>
                            <p class="text-sm leading-7 text-slate-500">Accurate address details help prevent booking delays, especially for first-time service visits.</p>
                        </div>
                        <div class="client-profile-tip">
                            <span class="client-profile-tip-icon"><i class="fas fa-key"></i></span>
                            <p class="text-sm leading-7 text-slate-500">Only fill in the password fields when you actually want to change your current password.</p>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</div>
@endsection
