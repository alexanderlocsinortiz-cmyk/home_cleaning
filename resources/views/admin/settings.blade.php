@extends('layouts.admin')
@section('title', 'Settings - Home Cleaning Service Admin')
@section('page-title', 'Settings')
@section('page-subtitle', 'Control account restrictions and staff page access')

@php
    $restrictedStaffCount = $staff->filter->hasActiveAccessRestriction()->count();
    $restrictedClientCount = $clients->filter->hasActiveAccessRestriction()->count();
    $pageLockedStaffCount = $staff->filter(fn ($member) => count($member->staff_restricted_pages ?? []) > 0)->count();
@endphp

@section('content')
<div class="admin-page-content cleanflow-page-shell space-y-5 p-4 sm:p-6">
    @if(session('success'))
        <div class="cleanflow-alert cleanflow-alert--success">
            <div class="text-sm font-semibold">{{ session('success') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="cleanflow-alert cleanflow-alert--danger">
            <div class="text-sm font-semibold">Please review the settings form.</div>
            <ul class="mt-2 list-disc pl-5 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="cleanflow-hero overflow-hidden px-5 py-6 text-white sm:px-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <span class="cleanflow-kicker">
                    <i class="fas fa-gear text-[0.75rem]"></i>
                    Access control
                </span>
                <h2 class="mt-3 text-2xl font-black tracking-tight sm:text-3xl">Keep staff access and account restrictions under control.</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-white/80">
                    Restrict accounts for a fixed number of days and lock specific staff pages without touching roles or deleting users.
                </p>
            </div>
            <div class="grid grid-cols-3 gap-2 text-center sm:min-w-[28rem]">
                <div class="rounded-2xl border border-white/20 bg-white/10 px-3 py-3">
                    <div class="text-2xl font-black">{{ $restrictedStaffCount }}</div>
                    <div class="text-[0.68rem] font-bold uppercase tracking-[0.18em] text-white/70">Staff held</div>
                </div>
                <div class="rounded-2xl border border-white/20 bg-white/10 px-3 py-3">
                    <div class="text-2xl font-black">{{ $restrictedClientCount }}</div>
                    <div class="text-[0.68rem] font-bold uppercase tracking-[0.18em] text-white/70">Clients held</div>
                </div>
                <div class="rounded-2xl border border-white/20 bg-white/10 px-3 py-3">
                    <div class="text-2xl font-black">{{ $pageLockedStaffCount }}</div>
                    <div class="text-[0.68rem] font-bold uppercase tracking-[0.18em] text-white/70">Page locks</div>
                </div>
            </div>
        </div>
    </section>

    <nav class="cleanflow-panel flex flex-wrap gap-2 px-4 py-3" aria-label="Settings sections">
        <button type="button" data-settings-tab="general-settings" class="inline-flex items-center gap-2 rounded-xl border border-blue-100 bg-blue-50 px-4 py-2 text-sm font-black text-blue-700 transition hover:border-blue-200 hover:bg-blue-100">
            <i class="fas fa-globe"></i>
            General Settings
        </button>
        <button type="button" data-settings-tab="restriction-history" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
            <i class="fas fa-clock-rotate-left"></i>
            Restriction History
        </button>
        <button type="button" data-settings-tab="staff-access" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
            <i class="fas fa-user-shield"></i>
            Staff Access
        </button>
        <button type="button" data-settings-tab="client-restrictions" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
            <i class="fas fa-users"></i>
            Client Restrictions
        </button>
        <button type="button" data-settings-tab="database-backup" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
            <i class="fas fa-database"></i>
            Database Backup
        </button>
    </nav>

    <section id="general-settings" data-settings-panel="general-settings" class="cleanflow-panel scroll-mt-24 overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-black text-slate-900">General Settings</h3>
                <p class="mt-1 text-sm text-slate-500">Update the public brand, contact details, and admin contact information.</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                <i class="fas fa-globe"></i>
                Website profile
            </span>
        </div>

        <form method="POST" action="{{ route('admin.settings.general') }}" enctype="multipart/form-data" class="grid gap-6 px-5 py-5 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            @csrf
            @method('PATCH')

            <div class="space-y-4">
                <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                    <div class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Website brand</div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-[10rem_1fr] sm:items-start">
                        <div class="flex shrink-0 items-center justify-center justify-self-center overflow-hidden rounded-2xl border border-slate-200 bg-white p-3 shadow-sm" style="width: 9rem; height: 9rem;">
                            <img src="{{ $generalSettings->logo_url }}" alt="{{ $generalSettings->website_name }}" style="display: block; width: 100%; height: 100%; max-width: 100%; max-height: 100%; object-fit: contain;">
                        </div>
                        <div class="space-y-3">
                            <label class="block">
                                <span class="text-sm font-bold text-slate-700">Website name</span>
                                <input type="text" name="website_name" value="{{ old('website_name', $generalSettings->website_name) }}" required class="mt-2 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                            </label>
                            <label class="block">
                                <span class="text-sm font-bold text-slate-700">Logo</span>
                                <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="mt-2 block w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-bold file:text-blue-700">
                            </label>
                            <p class="text-xs leading-5 text-slate-500">Use PNG, JPG, or WebP. Keep it readable at small sizes.</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-100 bg-white p-4">
                    <div class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Admin info</div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label class="block sm:col-span-2">
                            <span class="text-sm font-bold text-slate-700">Admin name</span>
                            <input type="text" name="admin_name" value="{{ old('admin_name', $generalSettings->admin_name) }}" class="mt-2 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                        </label>
                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Admin email</span>
                            <input type="email" name="admin_email" value="{{ old('admin_email', $generalSettings->admin_email) }}" class="mt-2 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                        </label>
                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Admin phone</span>
                            <input type="text" name="admin_phone" value="{{ old('admin_phone', $generalSettings->admin_phone) }}" class="mt-2 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                        </label>
                    </div>
                    <div class="mt-5 border-t border-slate-100 pt-4">
                        <div class="flex items-center gap-2 text-xs font-black uppercase tracking-[0.16em] text-slate-500">
                            <i class="fas fa-lock text-blue-600"></i>
                            Change password
                        </div>
                        <div class="mt-4 grid gap-3">
                            <label class="block">
                                <span class="text-sm font-bold text-slate-700">Current password</span>
                                <div class="relative mt-2">
                                    <input
                                        type="password"
                                        name="admin_current_password"
                                        value=""
                                        autocomplete="off"
                                        data-clear-password-on-load
                                        class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 pr-11 text-sm outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100"
                                    >
                                    <button type="button" data-password-toggle class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-blue-700" aria-label="Show current password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                @error('admin_current_password')
                                    <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>
                                @enderror
                            </label>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="block">
                                    <span class="text-sm font-bold text-slate-700">New password</span>
                                    <div class="relative mt-2">
                                        <input type="password" name="admin_new_password" autocomplete="new-password" minlength="12" aria-describedby="admin-password-help" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 pr-11 text-sm outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                                        <button type="button" data-password-toggle class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-blue-700" aria-label="Show new password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    @error('admin_new_password')
                                        <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>
                                    @enderror
                                </label>
                                <label class="block">
                                    <span class="text-sm font-bold text-slate-700">Confirm new password</span>
                                    <div class="relative mt-2">
                                        <input type="password" name="admin_new_password_confirmation" autocomplete="new-password" minlength="12" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 pr-11 text-sm outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                                        <button type="button" data-password-toggle class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-blue-700" aria-label="Show confirm new password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </label>
                            </div>
                            <p id="admin-password-help" class="text-xs leading-5 text-slate-500">New passwords need at least 12 characters with uppercase, lowercase, a number, and a symbol.</p>
                            <p class="text-xs leading-5 text-slate-500">Leave these fields empty if you only want to update website or contact information.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-2xl border border-slate-100 bg-white p-4">
                    <div class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Contact info</div>
                    <p class="mt-2 text-xs leading-5 text-slate-500">These values are shown publicly. Enter the real business email, phone, address, and office hours before launch; blank values are shown as unavailable instead of being replaced with placeholders.</p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Contact email</span>
                            <input type="email" name="contact_email" value="{{ old('contact_email', $generalSettings->contact_email) }}" class="mt-2 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                        </label>
                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Contact phone</span>
                            <input type="text" name="contact_phone" value="{{ old('contact_phone', $generalSettings->contact_phone) }}" class="mt-2 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="text-sm font-bold text-slate-700">Contact address</span>
                            <input type="text" name="contact_address" value="{{ old('contact_address', $generalSettings->contact_address) }}" class="mt-2 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                        </label>
                        <label class="block sm:col-span-2">
                            <span class="text-sm font-bold text-slate-700">Office hours</span>
                            <input type="text" name="office_hours" value="{{ old('office_hours', $generalSettings->office_hours) }}" class="mt-2 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                        </label>
                    </div>
                </div>

                <div class="rounded-2xl border border-blue-100 bg-blue-50/80 p-4">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="text-sm font-black text-slate-900">Save website profile</div>
                            <p class="mt-1 text-xs leading-5 text-slate-600">These values update shared layout branding and footer contact details.</p>
                        </div>
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-black text-white transition hover:bg-blue-700">
                            <i class="fas fa-save"></i>
                            Save General Settings
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </section>

    <section id="database-backup" data-settings-panel="database-backup" class="cleanflow-panel hidden scroll-mt-24 overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-black text-slate-900">Database Backup</h3>
                <p class="mt-1 text-sm text-slate-500">Download a point-in-time copy of the application database.</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                <i class="fas fa-shield-halved"></i>
                Admin only
            </span>
        </div>

        <div class="grid gap-5 px-5 py-5 lg:grid-cols-[minmax(0,1fr)_minmax(20rem,24rem)] lg:items-start">
            <div class="rounded-2xl border border-blue-100 bg-blue-50/70 p-4">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white text-blue-700 shadow-sm">
                        <i class="fas fa-download"></i>
                    </div>
                    <div>
                        <div class="text-sm font-black text-slate-900">Download database backup</div>
                        <p class="mt-1 text-sm leading-6 text-slate-600">
                            This creates a temporary backup file and downloads it immediately. Store it somewhere private because it can contain customers, staff, bookings, payments, and messages.
                        </p>
                        <button type="button" data-database-backup-password-toggle class="mt-4 inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-white px-4 py-2 text-sm font-black text-blue-700 transition hover:bg-blue-50">
                            <i class="fas fa-key"></i>
                            Change backup password
                        </button>

                        <form method="POST" action="{{ route('admin.settings.database-backup.password') }}" class="mt-4 hidden rounded-2xl border border-blue-100 bg-white p-4" data-database-backup-password-form>
                            @csrf
                            @method('PATCH')
                            <div class="mb-4 text-sm font-black text-slate-900">Change backup password</div>
                            <div class="space-y-3">
                                <label class="block">
                                    <span class="text-sm font-bold text-slate-700">Current password</span>
                                    <div class="relative mt-2">
                                        <input type="password" name="database_backup_admin_password" autocomplete="current-password" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 pr-11 text-sm outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                                        <button type="button" data-password-toggle class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-blue-700" aria-label="Show current password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    @error('database_backup_admin_password')
                                        <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>
                                    @enderror
                                </label>
                                <label class="block">
                                    <span class="text-sm font-bold text-slate-700">New backup password</span>
                                    <div class="relative mt-2">
                                        <input type="password" name="database_backup_new_password" autocomplete="new-password" minlength="12" aria-describedby="backup-password-help" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 pr-11 text-sm outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                                        <button type="button" data-password-toggle class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-blue-700" aria-label="Show new backup password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <p id="backup-password-help" class="mt-1 text-xs text-slate-500">Use at least 12 characters with uppercase, lowercase, a number, and a symbol.</p>
                                    @error('database_backup_new_password')
                                        <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>
                                    @enderror
                                </label>
                                <label class="block">
                                    <span class="text-sm font-bold text-slate-700">Confirm new backup password</span>
                                    <div class="relative mt-2">
                                        <input type="password" name="database_backup_new_password_confirmation" autocomplete="new-password" minlength="12" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 pr-11 text-sm outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                                        <button type="button" data-password-toggle class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-blue-700" aria-label="Show confirm new backup password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </label>
                            </div>
                            <button type="submit" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-black text-white transition hover:bg-blue-700">
                                <i class="fas fa-key"></i>
                                Save backup password
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <form method="POST" action="{{ route('admin.settings.database-backup') }}" class="rounded-2xl border border-slate-100 bg-white p-4" data-database-backup-form>
                    @csrf
                    @unless($generalSettings->database_backup_password_hash)
                        <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold leading-6 text-amber-900">
                            Set a backup password before downloading database backups.
                        </div>
                    @endunless
                    <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900 hidden" data-database-backup-confirm>
                        <div class="font-black">Warning:</div>
                        <p class="mt-1">
                            Backup files may contain confidential information such as customer records, staff accounts, bookings, and payment history.
                            Store backup files securely.
                        </p>
                    </div>
                    <label class="hidden" data-database-backup-confirm>
                        <span class="text-sm font-bold text-slate-700">Database backup password</span>
                        <div class="relative mt-2">
                            <input
                                type="password"
                                name="database_backup_password"
                                autocomplete="off"
                                class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 pr-11 text-sm outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100"
                                required
                                disabled
                            >
                            <button type="button" data-password-toggle class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-blue-700" aria-label="Show backup password">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        @error('database_backup_password')
                            <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>
                        @enderror
                    </label>
                    @error('database_backup')
                        <div class="mt-3 rounded-xl border border-red-100 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700">{{ $message }}</div>
                    @enderror
                    <button type="submit" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-black text-white transition hover:bg-blue-700">
                        <i class="fas fa-database"></i>
                        <span data-database-backup-button-label>Download Database</span>
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.settings.database-backup.cloud') }}" class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-4">
                    @csrf
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white text-emerald-700 shadow-sm">
                            <i class="fas fa-cloud-arrow-up"></i>
                        </div>
                        <div>
                            <div class="text-sm font-black text-slate-900">Upload database to private cloud storage</div>
                            <p class="mt-1 text-sm leading-6 text-slate-600">Creates the backup, uploads it to the configured private backup disk, confirms it exists, and removes the temporary local copy. Older backups beyond the retention count are pruned.</p>
                        </div>
                    </div>
                    <label class="mt-4 block">
                        <span class="text-sm font-bold text-slate-700">Database backup password</span>
                        <input type="password" name="database_backup_password" autocomplete="off" class="mt-2 h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100" required>
                        @error('database_backup_password')
                            <span class="mt-1 block text-xs font-semibold text-red-600">{{ $message }}</span>
                        @enderror
                    </label>
                    @error('database_backup')
                        <div class="mt-3 rounded-xl border border-red-100 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700">{{ $message }}</div>
                    @enderror
                    <button type="submit" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-black text-white transition hover:bg-emerald-700">
                        <i class="fas fa-cloud-arrow-up"></i>
                        Upload Backup to Cloud
                    </button>
                </form>

            </div>
        </div>
    </section>

    <section id="restriction-history" data-settings-panel="restriction-history" class="cleanflow-panel hidden scroll-mt-24 overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-black text-slate-900">Restriction History</h3>
                <p class="mt-1 text-sm text-slate-500">Latest account holds, cleared holds, and staff page-access changes.</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-bold text-slate-600">
                <i class="fas fa-clock-rotate-left text-blue-600"></i>
                Latest {{ $restrictionHistories->count() }}
            </span>
        </div>

        <div class="max-h-96 divide-y divide-slate-100 overflow-y-auto">
            @forelse($restrictionHistories as $history)
                @php
                    $historyLabel = match ($history->action) {
                        'account_restricted' => 'Account restricted',
                        'account_restriction_cleared' => 'Restriction cleared',
                        'staff_pages_updated' => 'Staff pages updated',
                        default => ucfirst(str_replace('_', ' ', $history->action)),
                    };
                    $historyTone = match ($history->action) {
                        'account_restricted' => 'bg-red-100 text-red-700',
                        'account_restriction_cleared' => 'bg-emerald-100 text-emerald-700',
                        default => 'bg-blue-100 text-blue-700',
                    };
                    $historyPages = collect($history->restricted_pages ?? [])
                        ->map(fn ($page) => $staffPages[$page] ?? ucfirst(str_replace('_', ' ', $page)))
                        ->implode(', ');
                @endphp
                <article class="grid gap-3 px-5 py-4 lg:grid-cols-[minmax(12rem,0.75fr)_minmax(0,1.35fr)_auto] lg:items-center">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full px-2.5 py-1 text-[0.68rem] font-black uppercase tracking-[0.12em] {{ $historyTone }}">{{ $historyLabel }}</span>
                            <span class="rounded-full border border-slate-200 px-2.5 py-1 text-[0.68rem] font-black uppercase tracking-[0.12em] text-slate-500">{{ $history->target_role }}</span>
                        </div>
                        <div class="mt-2 text-sm font-black text-slate-900">{{ $history->target_name }}</div>
                        <div class="mt-0.5 text-xs text-slate-500">{{ $history->target_email }}</div>
                    </div>

                    <div class="text-sm leading-6 text-slate-600">
                        @if($history->action === 'account_restricted')
                            <span class="font-semibold text-slate-900">{{ $history->duration_days }} day{{ $history->duration_days === 1 ? '' : 's' }}</span>
                            until {{ optional($history->restricted_until)->format('M d, Y h:i A') }}.
                            <span class="text-slate-500">Reason: {{ $history->reason ?: 'No reason recorded.' }}</span>
                        @elseif($history->action === 'account_restriction_cleared')
                            Cleared by {{ $history->actorUser?->display_name ?? 'Unknown admin' }}.
                            @if($history->reason)
                                <span class="text-slate-500">Previous reason: {{ $history->reason }}</span>
                            @endif
                        @elseif($history->action === 'staff_pages_updated')
                            Restricted pages now:
                            <span class="font-semibold text-slate-900">{{ $historyPages !== '' ? $historyPages : 'None' }}</span>
                        @else
                            {{ $history->reason ?: 'Access control event recorded.' }}
                        @endif
                    </div>

                    <div class="text-xs text-slate-500 lg:text-right">
                        <div class="font-bold text-slate-700">{{ $history->created_at->format('M d, Y') }}</div>
                        <div>{{ $history->created_at->format('h:i A') }}</div>
                        <div class="mt-1">By {{ $history->actorUser?->display_name ?? 'System' }}</div>
                    </div>
                </article>
            @empty
                <div class="px-6 py-10 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <i class="fas fa-clock-rotate-left"></i>
                    </div>
                    <div class="mt-3 text-sm font-bold text-slate-700">No restriction history yet.</div>
                    <div class="mt-1 text-xs text-slate-500">Restrict or clear an account to start the audit trail.</div>
                </div>
            @endforelse
        </div>
    </section>

    <section id="staff-access" data-settings-panel="staff-access" class="cleanflow-panel hidden scroll-mt-24 overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-black text-slate-900">Staff Access</h3>
                <p class="mt-1 text-sm text-slate-500">Use page locks for workflow access. Use account restriction only for serious temporary blocks.</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-bold text-slate-600">
                <i class="fas fa-user-shield text-blue-600"></i>
                {{ $staff->count() }} staff
            </span>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($staff as $member)
                @php
                    $staffRestrictedPages = $member->staff_restricted_pages ?? [];
                @endphp
                <article class="px-5 py-4">
                    <div class="grid gap-4 xl:grid-cols-[minmax(14rem,0.9fr)_minmax(20rem,1.2fr)_minmax(22rem,1.5fr)] xl:items-start">
                        <div class="space-y-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-black text-slate-900">{{ $member->display_name }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $member->email }}</div>
                                </div>
                                @if($member->hasActiveAccessRestriction())
                                    <span class="rounded-full bg-red-100 px-2.5 py-1 text-[0.68rem] font-black uppercase tracking-[0.12em] text-red-700">Restricted</span>
                                @else
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[0.68rem] font-black uppercase tracking-[0.12em] text-emerald-700">Active</span>
                                @endif
                            </div>

                            @if($member->hasActiveAccessRestriction())
                                <div class="rounded-2xl border border-red-100 bg-red-50 px-3 py-2 text-xs leading-5 text-red-800">
                                    <div class="font-bold">Until {{ $member->access_restricted_until->format('M d, Y h:i A') }}</div>
                                    <div class="text-red-700/80">{{ $member->access_restriction_reason ?: 'No reason recorded.' }}</div>
                                </div>
                            @else
                                <div class="rounded-2xl border border-slate-100 bg-slate-50 px-3 py-2 text-xs leading-5 text-slate-500">
                                    No active account restriction.
                                </div>
                            @endif
                        </div>

                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-3">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Account hold</div>
                                    <div class="text-xs text-slate-400">Blocks login until the hold expires.</div>
                                </div>
                                <form method="POST" action="{{ route('admin.settings.users.access', $member) }}"
                                    data-access-confirm-form
                                    data-confirm-action="clear"
                                    data-confirm-target="{{ $member->display_name }}"
                                    data-confirm-role="staff">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="action" value="clear">
                                    <button type="submit" class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-100">Clear</button>
                                </form>
                            </div>
                            <form method="POST" action="{{ route('admin.settings.users.access', $member) }}" class="grid gap-2 sm:grid-cols-[6rem_1fr_auto]"
                                data-access-confirm-form
                                data-confirm-action="restrict"
                                data-confirm-target="{{ $member->display_name }}"
                                data-confirm-role="staff">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="action" value="restrict">
                                <input type="number" name="restriction_days" min="1" max="365" placeholder="Days" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                                <input type="text" name="access_restriction_reason" placeholder="Reason for restriction" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                                <button type="submit" class="h-11 rounded-xl bg-red-600 px-4 text-sm font-black text-white transition hover:bg-red-700">Restrict</button>
                            </form>
                        </div>

                        <form method="POST" action="{{ route('admin.settings.staff.pages', $member) }}" class="rounded-2xl border border-slate-100 bg-white p-3"
                            data-access-confirm-form
                            data-confirm-action="pages"
                            data-confirm-target="{{ $member->display_name }}"
                            data-confirm-role="staff">
                            @csrf
                            @method('PATCH')
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Page restrictions</div>
                                    <div class="text-xs text-slate-400">{{ count($staffRestrictedPages) }} restricted page{{ count($staffRestrictedPages) === 1 ? '' : 's' }}</div>
                                </div>
                                <button type="submit" class="rounded-full bg-blue-600 px-3 py-1.5 text-xs font-black text-white transition hover:bg-blue-700">Save pages</button>
                            </div>
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach($staffPages as $key => $label)
                                    <label class="group flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 transition hover:border-blue-200 hover:bg-blue-50/60">
                                        <span>{{ $label }}</span>
                                        <input type="checkbox" name="restricted_pages[]" value="{{ $key }}" class="peer sr-only" @checked($member->isStaffPageRestricted($key))>
                                        <span class="relative h-5 w-9 rounded-full bg-slate-200 transition after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow after:transition peer-checked:bg-red-500 peer-checked:after:translate-x-4"></span>
                                    </label>
                                @endforeach
                            </div>
                        </form>
                    </div>
                </article>
            @empty
                <div class="px-6 py-12 text-center text-sm text-slate-500">No staff accounts found.</div>
            @endforelse
        </div>
    </section>

    <section id="client-restrictions" data-settings-panel="client-restrictions" class="cleanflow-panel hidden scroll-mt-24 overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-black text-slate-900">Client Restrictions</h3>
                <p class="mt-1 text-sm text-slate-500">Temporarily block clients from logging in or booking while keeping their records intact.</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-bold text-slate-600">
                <i class="fas fa-users text-blue-600"></i>
                {{ $clients->count() }} clients
            </span>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($clients as $client)
                <article class="grid gap-4 px-5 py-4 lg:grid-cols-[minmax(13rem,0.8fr)_minmax(24rem,1.4fr)] lg:items-center">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-sm font-black text-slate-900">{{ $client->display_name }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $client->email }}</div>
                            @if($client->hasActiveAccessRestriction())
                                <div class="mt-2 text-xs font-semibold text-red-700">
                                    Restricted until {{ $client->access_restricted_until->format('M d, Y h:i A') }}
                                </div>
                            @endif
                        </div>
                        @if($client->hasActiveAccessRestriction())
                            <span class="rounded-full bg-red-100 px-2.5 py-1 text-[0.68rem] font-black uppercase tracking-[0.12em] text-red-700">Held</span>
                        @else
                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[0.68rem] font-black uppercase tracking-[0.12em] text-emerald-700">Active</span>
                        @endif
                    </div>

                    <div class="flex flex-col gap-2 xl:flex-row xl:items-center">
                        <form method="POST" action="{{ route('admin.settings.users.access', $client) }}" class="grid flex-1 gap-2 sm:grid-cols-[6rem_1fr_auto]"
                            data-access-confirm-form
                            data-confirm-action="restrict"
                            data-confirm-target="{{ $client->display_name }}"
                            data-confirm-role="client">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="action" value="restrict">
                            <input type="number" name="restriction_days" min="1" max="365" placeholder="Days" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                            <input type="text" name="access_restriction_reason" placeholder="Reason for restriction" class="h-11 rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100">
                            <button type="submit" class="h-11 rounded-xl bg-red-600 px-4 text-sm font-black text-white transition hover:bg-red-700">Restrict</button>
                        </form>
                        <form method="POST" action="{{ route('admin.settings.users.access', $client) }}"
                            data-access-confirm-form
                            data-confirm-action="clear"
                            data-confirm-target="{{ $client->display_name }}"
                            data-confirm-role="client">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="h-11 w-full rounded-xl border border-slate-200 px-4 text-sm font-black text-slate-600 transition hover:bg-slate-50 xl:w-auto">Clear</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="px-6 py-12 text-center text-sm text-slate-500">No client accounts found.</div>
            @endforelse
        </div>
    </section>

    <div id="access-confirm-modal" class="fixed inset-0 z-[200] hidden items-center justify-center bg-slate-950/60 px-4 py-6 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="access-confirm-title">
        <div class="w-full max-w-lg overflow-hidden rounded-3xl bg-white shadow-2xl">
            <div class="border-b border-slate-100 px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1 text-xs font-black uppercase tracking-[0.16em] text-red-700">
                            <i class="fas fa-triangle-exclamation"></i>
                            Confirm access change
                        </div>
                        <h3 id="access-confirm-title" class="mt-3 text-xl font-black text-slate-900">Confirm restriction</h3>
                        <p id="access-confirm-message" class="mt-2 text-sm leading-6 text-slate-600"></p>
                    </div>
                    <button type="button" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-50" data-access-confirm-cancel aria-label="Close confirmation">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
            </div>

            <div class="space-y-3 px-6 py-5">
                <div class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                    <div class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">Target</div>
                    <div id="access-confirm-target" class="mt-1 text-sm font-black text-slate-900"></div>
                </div>
                <div class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                    <div class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">Details</div>
                    <div id="access-confirm-details" class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700"></div>
                </div>
                <p class="text-xs leading-5 text-slate-500">
                    This action is recorded in restriction history. Confirm only if the reason is clear enough for another admin to review later.
                </p>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
                <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-100" data-access-confirm-cancel>Cancel</button>
                <button type="button" id="access-confirm-submit" class="rounded-xl bg-red-600 px-4 py-3 text-sm font-black text-white transition hover:bg-red-700">Confirm Restriction</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const tabs = document.querySelectorAll('[data-settings-tab]');
    const panels = document.querySelectorAll('[data-settings-panel]');
    const validPanelIds = Array.from(panels).map((panel) => panel.dataset.settingsPanel);

    function setActiveTab(panelId, updateHash = true) {
        const nextPanelId = validPanelIds.includes(panelId) ? panelId : 'general-settings';

        panels.forEach((panel) => {
            panel.classList.toggle('hidden', panel.dataset.settingsPanel !== nextPanelId);
        });

        tabs.forEach((tab) => {
            const isActive = tab.dataset.settingsTab === nextPanelId;

            tab.classList.toggle('border-blue-100', isActive);
            tab.classList.toggle('bg-blue-50', isActive);
            tab.classList.toggle('text-blue-700', isActive);
            tab.classList.toggle('font-black', isActive);
            tab.classList.toggle('border-slate-200', !isActive);
            tab.classList.toggle('bg-white', !isActive);
            tab.classList.toggle('text-slate-700', !isActive);
            tab.classList.toggle('font-bold', !isActive);
        });

        if (updateHash) {
            history.replaceState(null, '', `#${nextPanelId}`);
        }
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => setActiveTab(tab.dataset.settingsTab));
    });

    setActiveTab(window.location.hash.replace('#', ''), false);
})();

(() => {
    const clearAutofilledPasswordFields = () => {
        document.querySelectorAll('[data-clear-password-on-load]').forEach((input) => {
            if (document.activeElement !== input) {
                input.value = '';
            }
        });
    };

    clearAutofilledPasswordFields();
    window.setTimeout(clearAutofilledPasswordFields, 250);

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = button.parentElement?.querySelector('input[type="password"], input[type="text"]');
        const icon = button.querySelector('i');

        if (!input || !icon) {
            return;
        }

        button.addEventListener('click', () => {
            const willShow = input.type === 'password';

            input.type = willShow ? 'text' : 'password';
            icon.classList.toggle('fa-eye', !willShow);
            icon.classList.toggle('fa-eye-slash', willShow);
            button.setAttribute('aria-label', willShow ? 'Hide password' : 'Show password');
        });
    });
})();

(() => {
    const passwordFormHasErrors = @json($errors->has('database_backup_admin_password') || $errors->has('database_backup_new_password'));

    document.querySelectorAll('[data-database-backup-password-toggle]').forEach((button) => {
        const form = document.querySelector('[data-database-backup-password-form]');

        button.addEventListener('click', () => {
            form?.classList.toggle('hidden');
            form?.querySelector('input')?.focus();
        });
    });

    if (passwordFormHasErrors) {
        const form = document.querySelector('[data-database-backup-password-form]');
        form?.classList.remove('hidden');
        form?.querySelector('input')?.focus();
    }

    document.querySelectorAll('[data-database-backup-form]').forEach((form) => {
        const confirmBlocks = form.querySelectorAll('[data-database-backup-confirm]');
        const passwordInput = form.querySelector('[name="database_backup_password"]');
        const buttonLabel = form.querySelector('[data-database-backup-button-label]');
        const shouldOpen = @json($errors->has('database_backup_password') || $errors->has('database_backup'));

        function openConfirmation() {
            confirmBlocks.forEach((block) => block.classList.remove('hidden'));
            if (passwordInput) {
                passwordInput.disabled = false;
            }
            if (buttonLabel) {
                buttonLabel.textContent = 'Confirm and Download';
            }
            passwordInput?.focus();
            form.dataset.databaseBackupReady = 'true';
        }

        if (shouldOpen) {
            openConfirmation();
        }

        form.addEventListener('submit', (event) => {
            if (form.dataset.databaseBackupReady === 'true') {
                window.setTimeout(() => {
                    if (passwordInput) {
                        passwordInput.value = '';
                        passwordInput.type = 'password';
                    }

                    const toggleIcon = form.querySelector('[data-password-toggle] i');

                    if (toggleIcon) {
                        toggleIcon.classList.add('fa-eye');
                        toggleIcon.classList.remove('fa-eye-slash');
                    }
                }, 500);

                return;
            }

            event.preventDefault();
            openConfirmation();
        });
    });
})();

(() => {
    const modal = document.getElementById('access-confirm-modal');
    const title = document.getElementById('access-confirm-title');
    const message = document.getElementById('access-confirm-message');
    const target = document.getElementById('access-confirm-target');
    const details = document.getElementById('access-confirm-details');
    const submitButton = document.getElementById('access-confirm-submit');
    let pendingForm = null;

    if (!modal || !submitButton) {
        return;
    }

    const actionCopy = {
        restrict: {
            title: 'Restrict this account?',
            button: 'Confirm Restriction',
            message: 'This will block the selected account from signing in until the restriction expires.',
        },
        clear: {
            title: 'Clear this restriction?',
            button: 'Confirm Clear',
            message: 'This will restore account access immediately if the user has an active restriction.',
        },
        pages: {
            title: 'Save staff page restrictions?',
            button: 'Confirm Page Access',
            message: 'This will change which staff portal pages this staff member can open.',
        },
    };

    function formInputValue(form, name) {
        return form.querySelector(`[name="${name}"]`)?.value?.trim() || '';
    }

    function selectedPageNames(form) {
        return Array.from(form.querySelectorAll('input[name="restricted_pages[]"]:checked'))
            .map((input) => input.closest('label')?.querySelector('span')?.textContent?.trim() || input.value)
            .filter(Boolean);
    }

    function describeForm(form) {
        const action = form.dataset.confirmAction;

        if (action === 'restrict') {
            const days = formInputValue(form, 'restriction_days');
            const reason = formInputValue(form, 'access_restriction_reason') || 'Restricted by admin.';

            return `Duration: ${days} day${days === '1' ? '' : 's'}\nReason: ${reason}`;
        }

        if (action === 'clear') {
            return 'The current account restriction will be removed.';
        }

        if (action === 'pages') {
            const pages = selectedPageNames(form);

            return pages.length
                ? `Restricted pages: ${pages.join(', ')}`
                : 'No staff pages will be restricted.';
        }

        return 'This access setting will be changed.';
    }

    function openModal(form) {
        const copy = actionCopy[form.dataset.confirmAction] || actionCopy.restrict;
        pendingForm = form;

        title.textContent = copy.title;
        message.textContent = copy.message;
        target.textContent = `${form.dataset.confirmTarget} (${form.dataset.confirmRole})`;
        details.textContent = describeForm(form);
        submitButton.textContent = copy.button;
        submitButton.classList.toggle('bg-red-600', form.dataset.confirmAction !== 'pages');
        submitButton.classList.toggle('hover:bg-red-700', form.dataset.confirmAction !== 'pages');
        submitButton.classList.toggle('bg-blue-600', form.dataset.confirmAction === 'pages');
        submitButton.classList.toggle('hover:bg-blue-700', form.dataset.confirmAction === 'pages');

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        submitButton.focus();
    }

    function closeModal() {
        pendingForm = null;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.querySelectorAll('[data-access-confirm-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === 'true') {
                return;
            }

            event.preventDefault();
            openModal(form);
        });
    });

    submitButton.addEventListener('click', () => {
        if (!pendingForm) {
            return;
        }

        pendingForm.dataset.confirmed = 'true';
        pendingForm.requestSubmit();
    });

    document.querySelectorAll('[data-access-confirm-cancel]').forEach((button) => {
        button.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
})();
</script>
@endpush
