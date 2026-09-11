@extends('layouts.app')

@section('title', 'Apply as Cleaner')

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
<style>
    .cleaner-apply-choice:has(input:checked) {
        border-color: #2563eb;
        background: #eff6ff;
        box-shadow: inset 0 0 0 1px rgba(37, 99, 235, 0.2);
        color: #0f172a;
    }

    .cleaner-apply-choice:has(input:checked) .cleaner-apply-choice-icon {
        background: #2563eb;
        color: #ffffff;
    }

    .cleaner-apply-input {
        min-height: 46px;
    }

    .cleaner-apply-invalid {
        border-color: #dc2626 !important;
        background: #fef2f2 !important;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.14) !important;
    }
</style>
@endpush

@section('content')
<section class="bg-slate-50">
    <div class="mx-auto grid max-w-7xl gap-6 px-5 py-6 sm:py-8 lg:grid-cols-[320px_minmax(0,1fr)] lg:px-6 lg:py-10">
        <aside class="self-start lg:sticky lg:top-24">
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <span class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 text-[11px] font-black uppercase tracking-[0.14em] text-blue-700">
                    <i class="fas fa-briefcase"></i>
                    Cleaner Partnership
                </span>
                <h1 class="mt-4 text-2xl font-black leading-tight text-slate-950 sm:text-3xl lg:text-[2rem]">Get cleaning jobs through CleanFlow.</h1>
                <p class="mt-3 text-sm leading-7 text-slate-600">
                    Complete the four sections below. Approval is required before any cleaner is assigned to customer bookings.
                </p>
                <div class="mt-5 hidden divide-y divide-slate-100 text-sm font-semibold text-slate-700 sm:block">
                    <div class="flex gap-3 py-3">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-50 text-xs text-blue-700"><i class="fas fa-clock"></i></span>
                        <span>Applications stay pending until reviewed by CleanFlow admin.</span>
                    </div>
                    <div class="flex gap-3 py-3">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-50 text-xs text-blue-700"><i class="fas fa-id-card"></i></span>
                        <span>Government ID, clearance, and selfie verification are checked.</span>
                    </div>
                    <div class="flex gap-3 py-3">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-50 text-xs text-blue-700"><i class="fas fa-location-dot"></i></span>
                        <span>Coverage and capacity should match jobs you can realistically accept.</span>
                    </div>
                </div>
            </div>
        </aside>

        <div class="min-w-0 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:p-6 lg:p-7">
            @if(session('success'))
                <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('tracking_token'))
                <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 px-4 py-4 text-sm text-blue-900">
                    <div class="font-black">Save your private tracking link</div>
                    <p class="mt-1 leading-6">Use it to check your application status without creating an account. Anyone with this link can view the application status, so keep it private.</p>
                    <a href="{{ route('cleaner-applications.status', ['token' => session('tracking_token')]) }}" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-xs font-black text-white hover:bg-blue-700">
                        <i class="fas fa-arrow-up-right-from-square"></i>
                        View application status
                    </a>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                    Please fix the highlighted fields before submitting.
                </div>
            @endif

            @php
                $initialStep = 1;
                $stepOneFields = ['applicant_type', 'individual_name', 'date_of_birth', 'individual_current_address', 'profile_photo', 'team_business_name', 'contact_person', 'business_address', 'team_size', 'business_logo', 'email', 'phone'];
                $stepTwoFields = ['coverage_mode', 'coverage_barangays', 'years_experience', 'services_offered', 'available_days', 'max_daily_bookings'];
                $stepThreeFields = ['government_id_type', 'government_id_number', 'government_id_document', 'nbi_clearance_number', 'nbi_clearance_document', 'selfie_with_id', 'worked_as_cleaner_before', 'worked_for_cleaning_company_before', 'has_cleaning_certifications', 'owns_cleaning_equipment', 'verification_notes'];
                $stepFourFields = ['terms_certify_accurate', 'terms_agree_verification', 'terms_approval_not_guaranteed', 'terms_service_standards'];

                foreach ([
                    1 => $stepOneFields,
                    2 => $stepTwoFields,
                    3 => $stepThreeFields,
                    4 => $stepFourFields,
                ] as $step => $fields) {
                    if (collect($fields)->contains(fn (string $field): bool => $errors->has($field))) {
                        $initialStep = $step;
                        break;
                    }
                }
            @endphp

            <form action="{{ route('cleaner-applications.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6" data-multi-step-form data-initial-step="{{ $initialStep }}">
                @csrf

                <div data-form-warning role="alert" aria-live="polite" tabindex="-1" class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-800">
                    Fix the red warning fields before submitting.
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-600">
                    <i class="fas fa-lock mr-1 text-slate-500"></i>
                    Your progress saves locally so you can move between steps. Personal contact and identity details are never saved in the browser draft.
                </div>

                <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-slate-50 px-4 py-4 sm:px-5">
                        <div class="min-w-0">
                            <div class="text-[11px] font-black uppercase tracking-[0.18em] text-blue-700"><span class="hidden sm:inline">Application </span>Progress</div>
                            <div class="mt-1 text-sm font-semibold text-slate-500" data-step-progress-copy>Step 1 of 4</div>
                        </div>
                        <div class="inline-flex w-fit whitespace-nowrap rounded-full bg-blue-50 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-blue-700 ring-1 ring-blue-100" data-step-progress-percent>
                            25% complete
                        </div>
                    </div>
                    <div class="h-1.5 bg-slate-100">
                        <div class="h-full rounded-r-full bg-blue-600 transition-all duration-300" data-step-progress-bar style="width: 25%"></div>
                    </div>
                    <div class="relative px-3 py-3 sm:px-4" data-step-progress>
                        <div class="absolute left-9 right-9 top-8 hidden h-0.5 rounded-full bg-slate-200 md:block">
                            <div class="h-full rounded-full bg-blue-600 transition-all duration-300" data-step-connector style="width: 0%"></div>
                        </div>
                        <div class="grid grid-cols-4 gap-1 sm:gap-2">
                        @foreach([
                            1 => ['Information', 'Applicant details', 'fa-user'],
                            2 => ['Services', 'Coverage and schedule', 'fa-broom'],
                            3 => ['Verification', 'ID and background', 'fa-shield-halved'],
                            4 => ['Submit', 'Final agreement', 'fa-paper-plane'],
                        ] as $stepNumber => [$stepLabel, $stepDescription, $stepIcon])
                            <button type="button" data-step-target="{{ $stepNumber }}" class="relative z-10 group flex min-w-0 flex-col items-center gap-1 rounded-lg border border-transparent bg-transparent px-1.5 py-2 text-center transition hover:border-blue-100 hover:bg-blue-50/70 disabled:cursor-not-allowed disabled:opacity-55 sm:gap-2 sm:px-2.5 sm:py-2.5">
                                <span data-step-number class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-black text-slate-500 ring-4 ring-white transition">
                                    <i class="fas {{ $stepIcon }} text-xs"></i>
                                </span>
                                <span class="min-w-0 sm:min-w-0">
                                    <span class="block text-[10px] font-black uppercase tracking-[0.12em] text-slate-400">Step {{ $stepNumber }}</span>
                                    <span class="block text-[11px] font-black leading-4 text-slate-800 sm:text-sm">{{ $stepLabel }}</span>
                                    <span class="mt-0.5 hidden text-xs font-semibold leading-4 text-slate-500 sm:block">{{ $stepDescription }}</span>
                                </span>
                            </button>
                        @endforeach
                        </div>
                    </div>
                </div>

                <div data-step-panel="1" class="space-y-8">
                <section class="space-y-5">
                    <div>
                        <div class="text-xs font-black uppercase tracking-[0.16em] text-blue-700">Step 1</div>
                        <h2 class="mt-1 text-xl font-black text-slate-950">Applicant Information</h2>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-800">Applicant type</label>
                        <div class="mt-2 grid gap-3 sm:grid-cols-2">
                            <label class="cleaner-apply-choice flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50">
                                <input type="radio" name="applicant_type" value="individual" class="h-4 w-4 text-blue-600" {{ old('applicant_type', 'individual') === 'individual' ? 'checked' : '' }}>
                                <span class="cleaner-apply-choice-icon flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs text-slate-500 transition"><i class="fas fa-user"></i></span>
                                <span>Individual Cleaner</span>
                            </label>
                            <label class="cleaner-apply-choice flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50">
                                <input type="radio" name="applicant_type" value="team" class="h-4 w-4 text-blue-600" {{ old('applicant_type') === 'team' ? 'checked' : '' }}>
                                <span class="cleaner-apply-choice-icon flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs text-slate-500 transition"><i class="fas fa-users"></i></span>
                                <span>Cleaning Team / Business</span>
                            </label>
                        </div>
                        @error('applicant_type')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div data-individual-fields class="space-y-5 rounded-lg border border-blue-100 bg-blue-50/40 p-4">
                        <h3 class="text-sm font-black uppercase tracking-[0.12em] text-blue-800">Individual Cleaner</h3>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="individual_name" class="block text-sm font-bold text-slate-800">Full Legal Name *</label>
                                <input id="individual_name" name="individual_name" value="{{ old('individual_name') }}" class="cleaner-apply-input mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                @error('individual_name')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="date_of_birth" class="block text-sm font-bold text-slate-800">Date of Birth *</label>
                                <input id="date_of_birth" type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" max="{{ now(config('cleanflow.attendance_timezone', config('app.timezone')))->subYears(18)->toDateString() }}" title="Individual cleaners must be at least 18 years old" class="cleaner-apply-input mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                @error('date_of_birth')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label for="individual_current_address" class="block text-sm font-bold text-slate-800">Current Address *</label>
                                <input id="individual_current_address" name="individual_current_address" value="{{ old('individual_current_address') }}" class="cleaner-apply-input mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                @error('individual_current_address')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label for="profile_photo" class="block text-sm font-bold text-slate-800">Profile Photo <span class="font-semibold text-slate-400">(Optional)</span></label>
                                <input id="profile_photo" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp" class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-bold file:text-blue-700">
                                @error('profile_photo')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <div data-team-fields class="space-y-5 rounded-lg border border-sky-100 bg-sky-50/40 p-4">
                        <h3 class="text-sm font-black uppercase tracking-[0.12em] text-sky-800">Cleaning Team / Business</h3>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="team_business_name" class="block text-sm font-bold text-slate-800">Business / Team Name *</label>
                                <input id="team_business_name" name="team_business_name" value="{{ old('team_business_name') }}" class="cleaner-apply-input mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                @error('team_business_name')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="contact_person" class="block text-sm font-bold text-slate-800">Contact Person *</label>
                                <input id="contact_person" name="contact_person" value="{{ old('contact_person') }}" class="cleaner-apply-input mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                @error('contact_person')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label for="business_address" class="block text-sm font-bold text-slate-800">Business Address *</label>
                                <input id="business_address" name="business_address" value="{{ old('business_address') }}" class="cleaner-apply-input mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                @error('business_address')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="team_size" class="block text-sm font-bold text-slate-800">Team Size *</label>
                        <input id="team_size" type="number" min="2" max="100" step="1" name="team_size" value="{{ old('team_size') }}" class="cleaner-apply-input mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                @error('team_size')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="business_logo" class="block text-sm font-bold text-slate-800">Business Logo <span class="font-semibold text-slate-400">(Optional)</span></label>
                                <input id="business_logo" type="file" name="business_logo" accept="image/jpeg,image/png,image/webp" class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-sky-50 file:px-3 file:py-2 file:text-sm file:font-bold file:text-sky-700">
                                @error('business_logo')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label id="email_label" for="email" class="block text-sm font-bold text-slate-800">Email Address *</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required class="cleaner-apply-input mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            @error('email')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-bold text-slate-800">Mobile Number *</label>
                            <input id="phone" name="phone" value="{{ old('phone') }}" inputmode="numeric" pattern="09[0-9]{9}" maxlength="11" autocomplete="tel" placeholder="09XXXXXXXXX" title="Enter an 11-digit Philippine mobile number starting with 09" required data-digits-only class="cleaner-apply-input mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            @error('phone')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>
                </div>

                <div data-step-panel="2" class="hidden space-y-8">
                <section class="space-y-5">
                    <div>
                        <div class="text-xs font-black uppercase tracking-[0.16em] text-blue-700">Step 2</div>
                        <h2 class="mt-1 text-xl font-black text-slate-950">Services &amp; Coverage</h2>
                    </div>
                    <div data-provider-location-shell class="rounded-lg border border-blue-100 bg-blue-50/40 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <label for="location_area" class="block text-sm font-bold text-slate-800">Provider base location *</label>
                                <p class="mt-1 text-xs leading-5 text-slate-600">Choose the city/municipality and click your operating base on the map. This exact pin is private to you and CleanFlow admins.</p>
                            </div>
                            <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-[11px] font-black text-blue-700 ring-1 ring-blue-100"><i class="fas fa-lock"></i> Admin-only pin</span>
                        </div>
                        <select id="location_area" name="location_area" required class="cleaner-apply-input mt-3 w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">Select your city/municipality</option>
                            @foreach($coverageAreas as $areaValue => $areaLabel)
                                <option value="{{ $areaValue }}" {{ old('location_area') === $areaValue ? 'selected' : '' }}>{{ $areaLabel }}</option>
                            @endforeach
                        </select>
                        @error('location_area')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        <div id="provider-location-map" data-provider-location-map data-area-input="location_area" data-latitude-input="location_latitude" data-longitude-input="location_longitude" class="mt-3 h-80 overflow-hidden rounded-lg border border-slate-200 bg-slate-100"></div>
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                            <p data-provider-location-status class="text-xs font-bold text-slate-500">Click the map to place your exact location.</p>
                            <button type="button" data-provider-location-current class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs font-black text-blue-700 transition hover:bg-blue-50"><i class="fas fa-location-crosshairs"></i> Use current location</button>
                        </div>
                        <input type="hidden" id="location_latitude" name="location_latitude" value="{{ old('location_latitude') }}">
                        <input type="hidden" id="location_longitude" name="location_longitude" value="{{ old('location_longitude') }}">
                        @error('location_latitude')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        @error('location_longitude')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="years_experience" class="block text-sm font-bold text-slate-800">Years of Cleaning Experience *</label>
                        <input id="years_experience" type="number" min="0" max="60" step="1" name="years_experience" value="{{ old('years_experience', 0) }}" required class="cleaner-apply-input mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        @error('years_experience')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-800">Service Coverage Area *</label>
                        <div class="mt-2 grid gap-3 sm:grid-cols-2">
                            <label class="cleaner-apply-choice flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50">
                                <input type="radio" name="coverage_mode" value="all" class="h-4 w-4 text-blue-600" {{ old('coverage_mode', 'all') === 'all' ? 'checked' : '' }}>
                                <span class="cleaner-apply-choice-icon flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs text-slate-500 transition"><i class="fas fa-map"></i></span>
                                <span>All Bukidnon cities/municipalities</span>
                            </label>
                            <label class="cleaner-apply-choice flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50">
                                <input type="radio" name="coverage_mode" value="specific" class="h-4 w-4 text-blue-600" {{ old('coverage_mode') === 'specific' ? 'checked' : '' }}>
                                <span class="cleaner-apply-choice-icon flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs text-slate-500 transition"><i class="fas fa-location-crosshairs"></i></span>
                                <span>Select city/municipality</span>
                            </label>
                        </div>
                        @error('coverage_mode')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        @error('coverage_barangays')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror

                        <div data-coverage-list class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <label for="coverage_barangays" class="text-xs font-bold uppercase text-slate-500">Bukidnon city/municipality</label>
                                <button type="button" data-coverage-picker-toggle aria-expanded="false" aria-controls="coverage-picker-panel" class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs font-black text-blue-700 transition hover:border-blue-400 hover:bg-blue-50">
                                    <i class="fas fa-location-dot"></i>
                                    <span data-coverage-picker-label>Select areas</span>
                                </button>
                            </div>
                            <div id="coverage-picker-panel" data-coverage-picker-panel hidden class="mt-3 rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <p id="coverage-help" class="text-xs leading-5 text-slate-500">Choose every city or municipality where you accept jobs.</p>
                                    <button type="button" data-coverage-select-all class="text-xs font-black text-blue-700 hover:text-blue-900">Select all</button>
                                </div>
                                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                    @foreach($coverageAreas as $areaValue => $areaLabel)
                                        <label class="cleaner-apply-choice flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-3 py-2.5 text-sm font-bold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50">
                                            <input type="checkbox" name="coverage_picker[]" value="{{ $areaValue }}" data-coverage-option class="h-4 w-4 rounded text-blue-600" {{ in_array($areaValue, old('coverage_barangays', []), true) ? 'checked' : '' }}>
                                            <span>{{ $areaLabel }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="mt-3 flex justify-end border-t border-slate-100 pt-3">
                                    <button type="button" data-coverage-picker-confirm class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-xs font-black text-white transition hover:bg-blue-700">
                                        <i class="fas fa-check"></i>
                                        Confirm selection
                                    </button>
                                </div>
                            </div>
                            <p id="coverage-selection-count" data-coverage-selection-count class="mt-2 text-xs font-bold text-blue-700" aria-live="polite">No areas selected</p>
                            <select id="coverage_barangays" name="coverage_barangays[]" multiple class="cleaner-apply-input mt-3 w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                @foreach($coverageAreas as $areaValue => $areaLabel)
                                    <option value="{{ $areaValue }}" {{ in_array($areaValue, old('coverage_barangays', []), true) ? 'selected' : '' }}>{{ $areaLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <label class="block text-sm font-bold text-slate-800">Services Offered *</label>
                            <span data-service-count class="text-xs font-bold text-slate-500" aria-live="polite">0 selected</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Select at least one service you can reliably provide.</p>
                        <div class="mt-2 grid gap-3 sm:grid-cols-2">
                            @foreach($serviceOfferings as $value => $label)
                                <label class="cleaner-apply-choice flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50">
                                    <input type="checkbox" name="services_offered[]" value="{{ $value }}" class="h-4 w-4 rounded text-blue-600" {{ in_array($value, old('services_offered', []), true) ? 'checked' : '' }}>
                                    <span class="cleaner-apply-choice-icon flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs text-slate-500 transition"><i class="fas fa-broom"></i></span>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('services_offered')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>
                </section>

                <section class="space-y-5 border-t border-slate-100 pt-7">
                    <div>
                        <div class="text-xs font-black uppercase tracking-[0.16em] text-blue-700">Availability</div>
                        <h2 class="mt-1 text-xl font-black text-slate-950">Work Schedule</h2>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <label class="block text-sm font-bold text-slate-800">Available Days *</label>
                            <span data-day-count class="text-xs font-bold text-slate-500" aria-live="polite">0 selected</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Select at least one day when you can accept bookings.</p>
                        <div class="mt-2 grid gap-3 sm:grid-cols-2">
                            @foreach($availableDays as $value => $label)
                                <label class="cleaner-apply-choice flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50">
                                    <input type="checkbox" name="available_days[]" value="{{ $value }}" class="h-4 w-4 rounded text-blue-600" {{ in_array($value, old('available_days', []), true) ? 'checked' : '' }}>
                                    <span class="cleaner-apply-choice-icon flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs text-slate-500 transition"><i class="fas fa-calendar-day"></i></span>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('available_days')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="max_daily_bookings" class="block text-sm font-bold text-slate-800">Maximum Bookings Per Day *</label>
                        <select id="max_daily_bookings" name="max_daily_bookings" required class="cleaner-apply-input mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            @foreach([1, 2, 3, 4, 5] as $capacity)
                                <option value="{{ $capacity }}" {{ (string) old('max_daily_bookings', 2) === (string) $capacity ? 'selected' : '' }}>{{ $capacity }}</option>
                            @endforeach
                        </select>
                        @error('max_daily_bookings')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>
                </section>
                </div>

                <div data-step-panel="3" class="hidden space-y-8">
                <section class="space-y-5">
                    <div>
                        <div class="text-xs font-black uppercase tracking-[0.16em] text-blue-700">Step 3</div>
                        <h2 class="mt-1 text-xl font-black text-slate-950">Verification</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Your ID, selfie, date of birth, and optional clearance details are collected only for cleaner verification and are restricted by role-based access. See our <a href="{{ route('legal.privacy') }}" class="font-bold text-blue-700 underline decoration-blue-300 underline-offset-2 hover:text-blue-900">Privacy Policy</a> for details.
                        </p>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="government_id_type" class="block text-sm font-bold text-slate-800">Government ID Type *</label>
                            <select id="government_id_type" name="government_id_type" required class="cleaner-apply-input mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                                <option value="">Choose ID type</option>
                                @foreach($governmentIdTypes as $value => $label)
                                    <option value="{{ $value }}" {{ old('government_id_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('government_id_type')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="government_id_number" class="block text-sm font-bold text-slate-800">Government ID Number *</label>
                            <input id="government_id_number" name="government_id_number" value="{{ old('government_id_number') }}" pattern="[A-Za-z0-9][A-Za-z0-9 -]{0,99}" maxlength="100" autocomplete="off" title="Use letters, numbers, spaces, and hyphens only" required class="cleaner-apply-input mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            @error('government_id_number')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="government_id_document" class="block text-sm font-bold text-slate-800">Upload ID *</label>
                            <input id="government_id_document" type="file" name="government_id_document" accept=".jpg,.jpeg,.png,.pdf" required class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-bold file:text-blue-700">
                            <p class="mt-1 text-xs font-semibold text-slate-500">JPG, PNG, or PDF. Maximum 5 MB.</p>
                            @error('government_id_document')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="nbi_clearance_number" class="block text-sm font-bold text-slate-800">NBI / Police Clearance Number</label>
                            <input id="nbi_clearance_number" name="nbi_clearance_number" value="{{ old('nbi_clearance_number') }}" class="cleaner-apply-input mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            @error('nbi_clearance_number')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="nbi_clearance_document" class="block text-sm font-bold text-slate-800">Upload Clearance</label>
                            <input id="nbi_clearance_document" type="file" name="nbi_clearance_document" accept=".jpg,.jpeg,.png,.pdf" class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-bold file:text-blue-700">
                            <p class="mt-1 text-xs font-semibold text-slate-500">Optional. JPG, PNG, or PDF up to 5 MB.</p>
                            @error('nbi_clearance_document')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="selfie_with_id" class="block text-sm font-bold text-slate-800">Upload Selfie Holding ID *</label>
                            <input id="selfie_with_id" type="file" name="selfie_with_id" accept="image/jpeg,image/png,image/webp" required class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-2.5 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-bold file:text-blue-700">
                            <p class="mt-1 text-xs font-semibold text-slate-500">Use a clear JPG or PNG image. Maximum 5 MB.</p>
                            @error('selfie_with_id')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <section class="space-y-5 border-t border-slate-100 pt-7">
                    <div>
                        <div class="text-xs font-black uppercase tracking-[0.16em] text-blue-700">Background Check</div>
                        <h2 class="mt-1 text-xl font-black text-slate-950">Background Information</h2>
                    </div>
                    @php
                        $backgroundQuestions = [
                            'worked_as_cleaner_before' => 'Have you worked as a cleaner before?',
                            'worked_for_cleaning_company_before' => 'Have you previously worked for a cleaning company?',
                            'has_cleaning_certifications' => 'Do you have any cleaning certifications?',
                            'owns_cleaning_equipment' => 'Do you own cleaning equipment?',
                        ];
                    @endphp
                    <div class="space-y-3">
                        @foreach($backgroundQuestions as $name => $question)
                            <div class="rounded-lg border border-slate-200 px-4 py-3">
                                <div class="text-sm font-bold text-slate-800">{{ $question }}</div>
                                <div class="mt-3 flex gap-3">
                                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                                        <input type="radio" name="{{ $name }}" value="1" required class="h-4 w-4 text-blue-600" {{ old($name) === '1' ? 'checked' : '' }}>
                                        Yes
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                                        <input type="radio" name="{{ $name }}" value="0" class="h-4 w-4 text-blue-600" {{ old($name) === '0' ? 'checked' : '' }}>
                                        No
                                    </label>
                                </div>
                                @error($name)<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>
                    <div>
                        <label for="verification_notes" class="block text-sm font-bold text-slate-800">Additional Background Notes</label>
                        <textarea id="verification_notes" name="verification_notes" rows="3" class="mt-2 w-full rounded-lg border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" placeholder="Optional references, certifications, or extra verification details.">{{ old('verification_notes') }}</textarea>
                        @error('verification_notes')<p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                    </div>
                </section>
                </div>

                <div data-step-panel="4" class="hidden space-y-8">
                <section class="space-y-5">
                    <div>
                        <div class="text-xs font-black uppercase tracking-[0.16em] text-blue-700">Step 4</div>
                        <h2 class="mt-1 text-xl font-black text-slate-950">Submit Application</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Review your information before submitting. CleanFlow admin will verify the application before approval.</p>
                    </div>
                    <div class="rounded-lg border border-blue-100 bg-blue-50/70 p-4 text-sm leading-6 text-slate-700">
                        <div class="font-black text-blue-900">Before you submit</div>
                        <div class="mt-1">Your application includes personal details, services and coverage, ID files, selfie verification, background answers, availability, and agreement to platform standards.</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="text-xs font-black uppercase tracking-[0.16em] text-slate-500">Final review</div>
                        <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                            <div><dt class="font-bold text-slate-500">Applicant</dt><dd data-summary-value="applicant" class="mt-1 font-semibold text-slate-900">—</dd></div>
                            <div><dt class="font-bold text-slate-500">Email</dt><dd data-summary-value="email" class="mt-1 break-words font-semibold text-slate-900">—</dd></div>
                            <div><dt class="font-bold text-slate-500">Coverage</dt><dd data-summary-value="coverage" class="mt-1 font-semibold text-slate-900">—</dd></div>
                            <div><dt class="font-bold text-slate-500">Services</dt><dd data-summary-value="services" class="mt-1 font-semibold text-slate-900">—</dd></div>
                            <div><dt class="font-bold text-slate-500">Available days</dt><dd data-summary-value="days" class="mt-1 font-semibold text-slate-900">—</dd></div>
                            <div><dt class="font-bold text-slate-500">Daily capacity</dt><dd data-summary-value="capacity" class="mt-1 font-semibold text-slate-900">—</dd></div>
                            <div class="sm:col-span-2"><dt class="font-bold text-slate-500">Files</dt><dd data-summary-value="files" class="mt-1 font-semibold text-slate-900">—</dd></div>
                        </dl>
                    </div>
                    <div class="space-y-3">
                        @foreach([
                            'terms_certify_accurate' => 'I certify that all information provided is accurate.',
                            'terms_agree_verification' => 'I agree to undergo verification by CleanFlow.',
                            'terms_approval_not_guaranteed' => 'I understand that approval is not guaranteed.',
                            'terms_service_standards' => "I agree to follow the platform's service standards.",
                        ] as $name => $label)
                            <label class="cleaner-apply-choice flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 px-4 py-3 text-sm font-bold text-slate-700 transition hover:border-blue-200 hover:bg-blue-50">
                                <input type="checkbox" name="{{ $name }}" value="1" class="mt-0.5 h-4 w-4 rounded text-blue-600" {{ old($name) ? 'checked' : '' }}>
                                <span>{{ $label }}</span>
                            </label>
                            @error($name)<p class="-mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>@enderror
                        @endforeach
                    </div>
                </section>

                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-700">
                    <i class="fas fa-paper-plane"></i>
                    Submit Application
                </button>
                </div>

                <div data-step-actions class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <button type="button" data-step-prev class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                        <i class="fas fa-arrow-left"></i>
                        Back
                    </button>
                    <button type="button" data-step-next class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-700">
                        <span data-step-next-label>Continue to Services</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
<script>
    window.cleanflowProviderMapConfig = @json(config('cleanflow.provider_map'));
    window.cleanflowProviderLocationCenters = @json($locationCenters);
</script>
<script src="{{ asset('js/provider-location-map.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('[data-multi-step-form]');
    const panels = Array.from(document.querySelectorAll('[data-step-panel]'));
    const stepButtons = Array.from(document.querySelectorAll('[data-step-target]'));
    const progressBar = document.querySelector('[data-step-progress-bar]');
    const progressConnector = document.querySelector('[data-step-connector]');
    const progressCopy = document.querySelector('[data-step-progress-copy]');
    const progressPercent = document.querySelector('[data-step-progress-percent]');
    const formWarning = document.querySelector('[data-form-warning]');
    const stepActions = document.querySelector('[data-step-actions]');
    const prevButton = document.querySelector('[data-step-prev]');
    const nextButton = document.querySelector('[data-step-next]');
    const typeInputs = document.querySelectorAll('input[name="applicant_type"]');
    const individualFields = document.querySelectorAll('[data-individual-fields]');
    const teamFields = document.querySelectorAll('[data-team-fields]');
    const emailLabel = document.getElementById('email_label');
    const coverageInputs = document.querySelectorAll('input[name="coverage_mode"]');
    const coverageList = document.querySelector('[data-coverage-list]');
    const coverageSelect = document.getElementById('coverage_barangays');
    const coverageOptions = document.querySelectorAll('[data-coverage-option]');
    const coveragePickerPanel = document.querySelector('[data-coverage-picker-panel]');
    const coveragePickerToggle = document.querySelector('[data-coverage-picker-toggle]');
    const coveragePickerConfirm = document.querySelector('[data-coverage-picker-confirm]');
    const coveragePickerSelectAll = document.querySelector('[data-coverage-select-all]');
    const coveragePickerLabel = document.querySelector('[data-coverage-picker-label]');
    const coverageSelectionCount = document.querySelector('[data-coverage-selection-count]');
    const providerLocationMap = document.querySelector('[data-provider-location-map]');
    const providerLocationArea = document.getElementById('location_area');
    const providerLocationLatitude = document.getElementById('location_latitude');
    const providerLocationLongitude = document.getElementById('location_longitude');
    const providerLocationStatus = document.querySelector('[data-provider-location-status]');
    const serviceInputs = document.querySelectorAll('input[name="services_offered[]"]');
    const dayInputs = document.querySelectorAll('input[name="available_days[]"]');
    const serviceCount = document.querySelector('[data-service-count]');
    const dayCount = document.querySelector('[data-day-count]');
    const digitsOnlyFields = document.querySelectorAll('[data-digits-only]');
    const uploadInputs = document.querySelectorAll('input[type="file"]');
    const summaryValues = document.querySelectorAll('[data-summary-value]');

    const individualRequired = ['individual_name', 'date_of_birth', 'individual_current_address'];
    const teamRequired = ['team_business_name', 'contact_person', 'business_address', 'team_size'];
    const individualFieldIds = [...individualRequired, 'profile_photo'];
    const teamFieldIds = [...teamRequired, 'business_logo'];
    const serverErrorFields = @json($errors->keys());
    const shouldClearDraft = @json(session()->has('success'));
    const hasServerOldInput = @json(session()->has('_old_input'));
    const draftKey = `cleanflow:cleaner-application-draft:${window.location.pathname}`;
    const draftExcludedFields = new Set([
        'individual_name',
        'date_of_birth',
        'individual_current_address',
        'team_business_name',
        'contact_person',
        'email',
        'phone',
        'business_address',
        'government_id_type',
        'government_id_number',
        'nbi_clearance_number',
        'worked_as_cleaner_before',
        'worked_for_cleaning_company_before',
        'has_cleaning_certifications',
        'owns_cleaning_equipment',
        'verification_notes',
    ]);
    const stepLabels = {
        1: 'Information',
        2: 'Services',
        3: 'Verification',
        4: 'Submit',
    };

    let currentStep = Math.max(1, Math.min(4, Number(form?.dataset.initialStep || 1)));
    let highestStepReached = currentStep;

    if (form) {
        form.noValidate = true;
    }

    if (coverageSelect) {
        coverageSelect.classList.add('sr-only');
        coverageSelect.setAttribute('aria-hidden', 'true');
        coverageSelect.tabIndex = -1;
    }

    function draftableFields() {
        return Array.from(form?.querySelectorAll('input, select, textarea') || [])
            .filter((field) => {
                const fieldName = field.name?.replace(/\[\]$/, '');

                return field.name
                    && !draftExcludedFields.has(fieldName)
                    && field.type !== 'file'
                    && field.type !== 'hidden'
                    && field.name !== '_token';
            });
    }

    function saveDraft() {
        if (!form) {
            return;
        }

        const values = {};

        draftableFields().forEach((field) => {
            if (field.type === 'radio') {
                if (field.checked) {
                    values[field.name] = field.value;
                } else if (!Object.prototype.hasOwnProperty.call(values, field.name)) {
                    values[field.name] = null;
                }

                return;
            }

            if (field.type === 'checkbox') {
                if (field.name.endsWith('[]')) {
                    values[field.name] = values[field.name] || [];

                    if (field.checked) {
                        values[field.name].push(field.value);
                    }
                } else {
                    values[field.name] = field.checked;
                }

                return;
            }

            if (field instanceof HTMLSelectElement && field.multiple) {
                values[field.name] = Array.from(field.selectedOptions).map((option) => option.value);
                return;
            }

            values[field.name] = field.value;
        });

        try {
            localStorage.setItem(draftKey, JSON.stringify({
                currentStep,
                highestStepReached,
                values,
            }));
        } catch (error) {
            // Some privacy modes block localStorage; the form should still work without draft saving.
        }
    }

    function restoreDraft() {
        if (shouldClearDraft) {
            try {
                localStorage.removeItem(draftKey);
            } catch (error) {
                // Ignore blocked localStorage.
            }
            return;
        }

        if (!form) {
            return;
        }

        let rawDraft = null;

        try {
            rawDraft = localStorage.getItem(draftKey);
        } catch (error) {
            return;
        }

        if (!rawDraft) {
            return;
        }

        let draft;

        try {
            draft = JSON.parse(rawDraft);
        } catch (error) {
            try {
                localStorage.removeItem(draftKey);
            } catch (storageError) {
                // Ignore blocked localStorage.
            }
            return;
        }

        const savedValues = draft && typeof draft === 'object' && draft.values && typeof draft.values === 'object'
            ? draft.values
            : {};
        const values = Object.fromEntries(
            Object.entries(savedValues).filter(([name]) => !draftExcludedFields.has(name.replace(/\[\]$/, '')))
        );

        try {
            localStorage.setItem(draftKey, JSON.stringify({ ...draft, values }));
        } catch (error) {
            // Ignore blocked localStorage.
        }

        if (hasServerOldInput) {
            return;
        }

        draftableFields().forEach((field) => {
            if (!Object.prototype.hasOwnProperty.call(values, field.name)) {
                return;
            }

            const savedValue = values[field.name];

            if (field.type === 'radio') {
                field.checked = savedValue === field.value;
                return;
            }

            if (field.type === 'checkbox') {
                field.checked = field.name.endsWith('[]')
                    ? Array.isArray(savedValue) && savedValue.includes(field.value)
                    : Boolean(savedValue);
                return;
            }

            if (field instanceof HTMLSelectElement && field.multiple) {
                Array.from(field.options).forEach((option) => {
                    option.selected = Array.isArray(savedValue) && savedValue.includes(option.value);
                });

                return;
            }

            field.value = savedValue ?? '';
        });

        digitsOnlyFields.forEach((field) => {
            field.value = field.value.replace(/\D/g, '');
        });

        currentStep = Math.max(1, Math.min(4, Number(draft.currentStep || currentStep)));
        highestStepReached = Math.max(currentStep, Math.min(4, Number(draft.highestStepReached || currentStep)));
    }

    function currentType() {
        return document.querySelector('input[name="applicant_type"]:checked')?.value || 'individual';
    }

    function setRequired(ids, required) {
        ids.forEach((id) => {
            const field = document.getElementById(id);
            if (field) {
                field.required = required;
            }
        });
    }

    function setDisabled(ids, disabled) {
        ids.forEach((id) => {
            const field = document.getElementById(id);
            if (field) {
                field.disabled = disabled;
            }
        });
    }

    function syncApplicantFields() {
        const isTeam = currentType() === 'team';

        individualFields.forEach((field) => field.classList.toggle('hidden', isTeam));
        teamFields.forEach((field) => field.classList.toggle('hidden', !isTeam));
        setRequired(individualRequired, !isTeam);
        setRequired(teamRequired, isTeam);
        setDisabled(individualFieldIds, isTeam);
        setDisabled(teamFieldIds, !isTeam);
        emailLabel.textContent = isTeam ? 'Business Email *' : 'Email Address *';
    }

    function syncCoverageFields() {
        const isSpecific = document.querySelector('input[name="coverage_mode"]:checked')?.value === 'specific';

        coverageList.classList.toggle('hidden', !isSpecific);
        coverageSelect.disabled = !isSpecific;
        coverageOptions.forEach((option) => {
            option.disabled = !isSpecific;
        });
        if (!isSpecific) {
            setCoveragePickerOpen(false);
        }
        syncCoverageSelectFromOptions();
        updateSelectionCounts();
    }

    function syncCoverageSelectFromOptions() {
        if (!coverageSelect) {
            return;
        }

        const selectedValues = new Set(
            Array.from(coverageOptions)
                .filter((option) => option.checked)
                .map((option) => option.value)
        );

        Array.from(coverageSelect.options).forEach((option) => {
            option.selected = selectedValues.has(option.value);
        });
    }

    function setCoveragePickerOpen(open) {
        if (!coveragePickerPanel || !coveragePickerToggle) {
            return;
        }

        coveragePickerPanel.hidden = !open;
        coveragePickerToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function updateSelectionCounts() {
        const isSpecific = document.querySelector('input[name="coverage_mode"]:checked')?.value === 'specific';

        if (coverageSelectionCount) {
            if (!isSpecific) {
                coverageSelectionCount.textContent = 'All areas selected';
                coverageSelectionCount.classList.remove('text-red-600');
                coverageSelectionCount.classList.add('text-blue-700');
                if (coveragePickerLabel) {
                    coveragePickerLabel.textContent = 'Select areas';
                }
            } else {
                const count = Array.from(coverageOptions).filter((option) => option.checked).length;
                coverageSelectionCount.textContent = count === 0
                    ? 'No areas selected'
                    : `${count} area${count === 1 ? '' : 's'} selected`;
                coverageSelectionCount.classList.toggle('text-red-600', count === 0);
                coverageSelectionCount.classList.toggle('text-blue-700', count > 0);
                if (coveragePickerLabel) {
                    coveragePickerLabel.textContent = count === 0 ? 'Select areas' : 'Change selection';
                }
            }
        }

        if (serviceCount) {
            const count = Array.from(serviceInputs).filter((input) => input.checked).length;
            serviceCount.textContent = `${count} selected`;
            serviceCount.classList.toggle('text-blue-700', count > 0);
            serviceCount.classList.toggle('text-slate-500', count === 0);
        }

        if (dayCount) {
            const count = Array.from(dayInputs).filter((input) => input.checked).length;
            dayCount.textContent = `${count} selected`;
            dayCount.classList.toggle('text-blue-700', count > 0);
            dayCount.classList.toggle('text-slate-500', count === 0);
        }
    }

    function fileSizeLabel(bytes) {
        return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
    }

    function updateUploadPreview(input) {
        let preview = input.parentElement.querySelector('[data-upload-preview]');

        if (!preview) {
            preview = document.createElement('p');
            preview.dataset.uploadPreview = 'true';
            preview.className = 'mt-2 text-xs font-bold text-slate-600';
            input.insertAdjacentElement('afterend', preview);
        }

        const file = input.files?.[0];

        if (!file) {
            preview.textContent = '';
            return;
        }

        preview.textContent = `Selected: ${file.name} (${fileSizeLabel(file.size)})`;
        preview.classList.toggle('text-red-600', file.size > 5 * 1024 * 1024);
        preview.classList.toggle('text-slate-600', file.size <= 5 * 1024 * 1024);
    }

    function updateFinalSummary() {
        const isTeam = currentType() === 'team';
        const individualName = document.getElementById('individual_name')?.value.trim();
        const teamName = document.getElementById('team_business_name')?.value.trim();
        const coverageMode = document.querySelector('input[name="coverage_mode"]:checked')?.value;
        const coverage = coverageMode === 'all'
            ? 'All Bukidnon cities and municipalities'
            : Array.from(coverageOptions).filter((option) => option.checked).map((option) => option.closest('label')?.textContent.trim()).filter(Boolean).join(', ') || 'No areas selected';
        const selectedServices = Array.from(document.querySelectorAll('input[name="services_offered[]"]:checked')).map((field) => field.closest('label')?.textContent.trim()).filter(Boolean);
        const selectedDays = Array.from(document.querySelectorAll('input[name="available_days[]"]:checked')).map((field) => field.closest('label')?.textContent.trim()).filter(Boolean);
        const selectedFiles = Array.from(uploadInputs).filter((input) => input.files?.length).map((input) => input.files[0].name);
        const summary = {
            applicant: isTeam ? (teamName || 'Not provided') : (individualName || 'Not provided'),
            email: document.getElementById('email')?.value.trim() || 'Not provided',
            coverage,
            services: selectedServices.join(', ') || 'None selected',
            days: selectedDays.join(', ') || 'None selected',
            capacity: `${document.getElementById('max_daily_bookings')?.value || '—'} booking(s) per day`,
            files: selectedFiles.join(', ') || 'No files selected',
        };

        summaryValues.forEach((element) => {
            element.textContent = summary[element.dataset.summaryValue] || '—';
        });
    }

    function clearValidationState(scope = form) {
        scope?.querySelectorAll('[data-client-error]').forEach((error) => error.remove());
        scope?.querySelectorAll('.cleaner-apply-invalid').forEach((field) => field.classList.remove('cleaner-apply-invalid'));
    }

    function showFormWarning(message = 'Fix the red warning fields before submitting.') {
        if (!formWarning) {
            return;
        }

        const wasHidden = formWarning.classList.contains('hidden');
        formWarning.textContent = message;
        formWarning.classList.remove('hidden');

        if (wasHidden) {
            formWarning.scrollIntoView({ behavior: 'smooth', block: 'center' });
            formWarning.focus({ preventScroll: true });
        }
    }

    function hideFormWarning() {
        formWarning?.classList.add('hidden');
    }

    function getFieldLabel(field) {
        const explicitLabel = field.id ? document.querySelector(`label[for="${field.id}"]`) : null;
        const labelText = explicitLabel?.textContent || field.closest('label')?.textContent || 'This field';

        return labelText.replace(/\s*\*\s*/g, '').replace(/\s*\(Optional\)\s*/i, '').trim() || 'This field';
    }

    function validationMessageFor(field) {
        const label = getFieldLabel(field);

        if (field.validity.valueMissing) {
            return `${label} is required.`;
        }

        if (field.validity.typeMismatch && field.type === 'email') {
            return 'Enter a valid email address.';
        }

        if (field.validity.patternMismatch && field.name === 'phone') {
            return 'Enter an 11-digit Philippine mobile number starting with 09.';
        }

        if (field.validity.patternMismatch && field.name === 'government_id_number') {
            return 'Use letters, numbers, spaces, and hyphens only.';
        }

        if (field.validity.patternMismatch) {
            return `${label} contains an unsupported format.`;
        }

        if (field.validity.rangeUnderflow) {
            return `${label} is too low.`;
        }

        if (field.validity.rangeOverflow) {
            return `${label} is too high.`;
        }

        return field.validationMessage || `${label} has a wrong input.`;
    }

    function addErrorMessage(anchor, message) {
        const error = document.createElement('p');
        error.className = 'mt-1 text-xs font-bold text-red-600';
        error.dataset.clientError = 'true';
        error.textContent = message;
        anchor.insertAdjacentElement('afterend', error);
    }

    function showFieldError(field, message = validationMessageFor(field)) {
        const choice = field.closest('.cleaner-apply-choice');

        if (choice) {
            choice.classList.add('cleaner-apply-invalid');
            addErrorMessage(choice, message);
            return;
        }

        field.classList.add('cleaner-apply-invalid');
        addErrorMessage(field, message);
    }

    function showGroupError(panel, name, message) {
        const fields = Array.from(panel.querySelectorAll(`input[name="${name}"]`));
        const group = fields[0]?.closest('.grid, .space-y-3, .mt-3, div');

        fields.forEach((field) => field.closest('.cleaner-apply-choice')?.classList.add('cleaner-apply-invalid'));

        if (group) {
            addErrorMessage(group, message);
        }
    }

    function validateCheckedGroup(panel, name, message) {
        const fields = Array.from(panel.querySelectorAll(`input[name="${name}"]`));

        if (fields.length === 0 || fields.some((field) => field.checked)) {
            return true;
        }

        showGroupError(panel, name, message);
        return false;
    }

    function validateStep(step, options = {}) {
        const { showWarnings = true } = options;
        const panel = document.querySelector(`[data-step-panel="${step}"]`);

        if (!panel) {
            return true;
        }

        clearValidationState(panel);

        const fields = Array.from(panel.querySelectorAll('input, select, textarea'))
            .filter((field) => !field.disabled && field.type !== 'hidden');

        for (const field of fields) {
            if (!field.checkValidity()) {
                if (showWarnings) {
                    showFieldError(field);
                    showFormWarning('Fix the red warning field before continuing.');
                }
                return false;
            }
        }

        if (step === 2) {
            if (!providerLocationArea?.value || !providerLocationLatitude?.value || !providerLocationLongitude?.value) {
                if (showWarnings) {
                    providerLocationMap?.classList.add('ring-2', 'ring-red-300');
                    if (providerLocationStatus) {
                        providerLocationStatus.textContent = 'Choose a city/municipality and place the exact pin on the map.';
                        providerLocationStatus.classList.remove('text-slate-500', 'text-emerald-700');
                        providerLocationStatus.classList.add('text-red-600');
                    }
                    showFormWarning('Add the provider base location before continuing.');
                }
                return false;
            }

            const isSpecificCoverage = document.querySelector('input[name="coverage_mode"]:checked')?.value === 'specific';

            if (isSpecificCoverage && !Array.from(coverageOptions).some((option) => option.checked)) {
                if (showWarnings) {
                    showGroupError(panel, 'coverage_picker[]', 'Choose at least one city or municipality.');
                    showFormWarning('Choose at least one city or municipality before continuing.');
                }
                return false;
            }

            if (!validateCheckedGroup(panel, 'services_offered[]', 'Choose at least one service.')) {
                if (showWarnings) {
                    showFormWarning('Choose at least one service before continuing.');
                }
                return false;
            }

            if (!validateCheckedGroup(panel, 'available_days[]', 'Choose at least one available day.')) {
                if (showWarnings) {
                    showFormWarning('Choose at least one available day before continuing.');
                }
                return false;
            }
        }

        if (step === 4) {
            const terms = [
                'terms_certify_accurate',
                'terms_agree_verification',
                'terms_approval_not_guaranteed',
                'terms_service_standards',
            ];

            for (const name of terms) {
                const field = panel.querySelector(`input[name="${name}"]`);

                if (field && !field.checked) {
                    if (showWarnings) {
                        showFieldError(field, 'This agreement is required before submitting.');
                        showFormWarning('Check every agreement before submitting.');
                    }
                    return false;
                }
            }
        }

        if (step === 3) {
            for (const input of uploadInputs) {
                const file = input.files?.[0];

                if (file && file.size > 5 * 1024 * 1024) {
                    if (showWarnings) {
                        showFieldError(input, 'Each file must be 5 MB or smaller.');
                        showFormWarning('Remove files larger than 5 MB before continuing.');
                    }
                    return false;
                }
            }
        }

        if (showWarnings) {
            hideFormWarning();
        }

        return true;
    }

    function validateAllStepsBeforeSubmit() {
        clearValidationState(form);

        for (let step = 1; step <= 4; step += 1) {
            if (!validateStep(step)) {
                highestStepReached = Math.max(highestStepReached, step);
                showStep(step);
                showFormWarning('Some required information is missing or wrong. Fix the red warning fields before submitting.');
                form?.scrollIntoView({ behavior: 'smooth', block: 'start' });

                return false;
            }
        }

        hideFormWarning();
        return true;
    }

    function showServerErrors() {
        if (!serverErrorFields.length) {
            return;
        }

        serverErrorFields.forEach((name) => {
            const normalizedName = name.replace(/\.\d+$/, '');
            const field = form?.querySelector(`[name="${normalizedName}"], [name="${normalizedName}[]"]`);

            if (field) {
                showFieldError(field, 'This field is missing or has a wrong input.');
            }
        });

        showFormWarning('Some information is missing or wrong. Fix the red warning fields before submitting.');
    }

    function showStep(step) {
        currentStep = Math.max(1, Math.min(4, step));

        panels.forEach((panel) => {
            panel.classList.toggle('hidden', Number(panel.dataset.stepPanel) !== currentStep);
        });

        stepButtons.forEach((button) => {
            const target = Number(button.dataset.stepTarget);
            const isActive = target === currentStep;
            const isDone = target < highestStepReached;
            const canOpen = target <= highestStepReached;
            const number = button.querySelector('[data-step-number]');

            button.disabled = !canOpen;
            button.setAttribute('aria-current', isActive ? 'step' : 'false');
            button.classList.remove('border-blue-300', 'border-emerald-200', 'bg-blue-50', 'bg-emerald-50', 'ring-2', 'ring-blue-100');
            number.classList.remove('bg-blue-600', 'bg-emerald-500', 'bg-slate-100', 'text-white', 'text-emerald-700', 'text-slate-500');

            if (isActive) {
                button.classList.add('border-blue-300', 'bg-blue-50', 'ring-2', 'ring-blue-100');
                number.classList.add('bg-blue-600', 'text-white');
            } else if (isDone) {
                button.classList.add('border-emerald-200', 'bg-emerald-50');
                number.classList.add('bg-emerald-500', 'text-white');
            } else {
                number.classList.add('bg-slate-100', 'text-slate-500');
            }
        });

        if (progressBar) {
            progressBar.style.width = `${currentStep * 25}%`;
        }

        if (progressConnector) {
            progressConnector.style.width = `${((currentStep - 1) / 3) * 100}%`;
        }

        if (progressCopy) {
            progressCopy.textContent = `Step ${currentStep} of 4`;
        }

        if (progressPercent) {
            progressPercent.textContent = `${currentStep * 25}% complete`;
        }

        if (prevButton) {
            prevButton.classList.toggle('invisible', currentStep === 1);
        }

        if (nextButton) {
            const shouldHideNext = currentStep === 4;

            nextButton.classList.toggle('hidden', shouldHideNext);
            nextButton.hidden = shouldHideNext;
            nextButton.style.display = shouldHideNext ? 'none' : '';
        }

        if (stepActions) {
            stepActions.classList.toggle('sm:justify-start', currentStep === 4);
            stepActions.classList.toggle('sm:justify-between', currentStep !== 4);
        }

        const nextLabel = document.querySelector('[data-step-next-label]');

        if (nextLabel && currentStep < 4) {
            nextLabel.textContent = `Continue to ${stepLabels[currentStep + 1]}`;
        }

        if (currentStep === 2 && typeof window.cleanflowInvalidateProviderLocationMaps === 'function') {
            window.requestAnimationFrame(() => window.cleanflowInvalidateProviderLocationMaps());
        }

        if (currentStep === 4) {
            updateFinalSummary();
        }
    }

    function goToStep(targetStep) {
        if (targetStep > highestStepReached + 1) {
            return;
        }

        if (targetStep > currentStep && !validateStep(currentStep)) {
            return;
        }

        highestStepReached = Math.max(highestStepReached, targetStep);
        showStep(targetStep);
        saveDraft();
        form?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    typeInputs.forEach((input) => input.addEventListener('change', () => {
        syncApplicantFields();
        saveDraft();
    }));
    coverageInputs.forEach((input) => input.addEventListener('change', () => {
        syncCoverageFields();
        saveDraft();
    }));
    coveragePickerToggle?.addEventListener('click', () => {
        setCoveragePickerOpen(coveragePickerPanel?.hidden === true);
    });
    coveragePickerConfirm?.addEventListener('click', () => {
        syncCoverageSelectFromOptions();
        updateSelectionCounts();
        setCoveragePickerOpen(false);
        saveDraft();
    });
    coveragePickerSelectAll?.addEventListener('click', () => {
        coverageOptions.forEach((option) => {
            option.checked = true;
        });
        syncCoverageSelectFromOptions();
        updateSelectionCounts();
        saveDraft();
    });
    coverageOptions.forEach((option) => option.addEventListener('change', () => {
        syncCoverageSelectFromOptions();
        updateSelectionCounts();
        saveDraft();
    }));
    coverageSelect?.addEventListener('change', () => {
        updateSelectionCounts();
        saveDraft();
    });
    [...serviceInputs, ...dayInputs].forEach((input) => input.addEventListener('change', () => {
        updateSelectionCounts();
        saveDraft();
    }));
    digitsOnlyFields.forEach((field) => {
        field.addEventListener('input', () => {
            field.value = field.value.replace(/\D/g, '');
            saveDraft();
        });
    });
    draftableFields().forEach((field) => {
        field.addEventListener('input', saveDraft);
        field.addEventListener('change', saveDraft);
    });
    uploadInputs.forEach((input) => input.addEventListener('change', () => {
        updateUploadPreview(input);
        updateFinalSummary();
    }));
    stepButtons.forEach((button) => button.addEventListener('click', () => goToStep(Number(button.dataset.stepTarget))));
    prevButton?.addEventListener('click', () => goToStep(currentStep - 1));
    nextButton?.addEventListener('click', () => goToStep(currentStep + 1));
    form?.addEventListener('submit', function(event) {
        if (!validateAllStepsBeforeSubmit()) {
            event.preventDefault();
        }
    });
    restoreDraft();
    syncApplicantFields();
    syncCoverageFields();
    updateSelectionCounts();
    showStep(currentStep);
    showServerErrors();
});
</script>
@endpush
@endsection
