@extends('layouts.client')
@section('title', 'Edit Profile - Home Cleaning Service')

@section('content')
@php
    $user = auth()->user();
    $initials = $user->initials;
    $barangayLabel = $user->barangay ? ucfirst(str_replace('_', ' ', $user->barangay)) : 'Not set';
    $barangayCenters = config('cleanflow.barangay_centers');
    $summaryItems = [
        ['icon' => 'fa-phone', 'label' => 'Phone', 'value' => $user->phone ?: 'Not set'],
        ['icon' => 'fa-location-dot', 'label' => 'Barangay', 'value' => $barangayLabel],
        ['icon' => 'fa-road', 'label' => 'Street', 'value' => $user->street ?: 'Not set'],
    ];
    $tips = [
        'Keep your address updated for accurate service delivery.',
        'A valid phone number helps our team reach you quickly when a cleaner is assigned.',
        'Complete profile details help speed up booking confirmation and manual review.',
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

        <section class="cleanflow-hero overflow-hidden px-6 py-7 text-white sm:px-8">
            <div class="cleanflow-hero-content flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl space-y-4">
                    <span class="cleanflow-kicker">
                        <i class="fas fa-id-card text-[0.75rem]"></i>
                        Client account
                    </span>
                    <div class="space-y-3">
                        <h1 class="max-w-2xl text-3xl font-black tracking-tight sm:text-4xl">
                            Keep your profile booking-ready
                        </h1>
                        <p class="max-w-2xl text-sm leading-7 text-white/80 sm:text-base">
                            Update your contact and address details so confirmations, cleaner assignments, and support
                            updates always reach the right place.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3 text-sm text-white/85">
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-phone-volume text-xs"></i>
                            Faster client support
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-location-dot text-xs"></i>
                            Accurate service visits
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-2 backdrop-blur-sm">
                            <i class="fas fa-shield-heart text-xs"></i>
                            Smoother booking review
                        </span>
                    </div>
                </div>

                <a href="{{ route('client.dashboard') }}" class="cleanflow-ghost-button self-start xl:self-auto">
                    <i class="fas fa-arrow-left text-xs"></i>
                    Back to dashboard
                </a>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <form action="{{ route('client.profile.update') }}" method="POST" class="space-y-6">
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
                                <p class="mt-1 text-sm text-slate-500">These details are used for contact, verification, and age checks.</p>
                            </div>
                        </div>
                        <div class="rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">
                            Required
                        </div>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="space-y-2">
                            <label for="first_name" class="text-sm font-semibold text-slate-700">First name</label>
                            <input
                                id="first_name"
                                type="text"
                                name="first_name"
                                class="client-profile-input"
                                value="{{ old('first_name', $user->first_name) }}"
                                placeholder="First name"
                                required
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
                                class="client-profile-input"
                                value="{{ old('last_name', $user->last_name) }}"
                                placeholder="Last name"
                                required
                            >
                            @error('last_name')
                                <p class="text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="phone" class="text-sm font-semibold text-slate-700">Phone number</label>
                            <input
                                id="phone"
                                type="text"
                                name="phone"
                                class="client-profile-input"
                                value="{{ old('phone', $user->phone) }}"
                                placeholder="09XXXXXXXXX"
                                inputmode="numeric"
                                pattern="[0-9]{11}"
                                maxlength="11"
                                required
                            >
                            @error('phone')
                                <p class="text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="email_preview" class="text-sm font-semibold text-slate-700">Email address</label>
                            <input
                                id="email_preview"
                                type="email"
                                class="client-profile-input"
                                value="{{ $user->email }}"
                                disabled
                            >
                            <p class="text-xs text-slate-500">Email is locked to protect account access.</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-2">
                        <label for="date_of_birth" class="text-sm font-semibold text-slate-700">Date of birth</label>
                        <input
                            id="date_of_birth"
                            type="date"
                            name="date_of_birth"
                            class="client-profile-input"
                            value="{{ old('date_of_birth', optional($user->date_of_birth)->format('Y-m-d')) }}"
                            max="{{ now(config('cleanflow.attendance_timezone', config('app.timezone')))->subYears(18)->toDateString() }}"
                            required
                        >
                        @error('date_of_birth')
                            <p class="text-xs font-medium text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-slate-500">Clients must be at least 18 years old to book services.</p>
                    </div>
                </section>

                <section class="cleanflow-panel p-6 md:p-7">
                    <div class="mb-6 flex items-start gap-4 border-b border-slate-100 pb-5">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-700">
                            <i class="fas fa-location-dot text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Address information</h2>
                            <p class="mt-1 text-sm text-slate-500">This is the address we use for service scheduling and arrival guidance.</p>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div class="rounded-2xl border border-blue-100 bg-blue-50/70 px-4 py-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div class="text-sm font-bold text-slate-900">Use your current location</div>
                                    <div class="mt-1 text-xs leading-5 text-slate-500">Allow browser location access to detect the nearest covered barangay.</div>
                                </div>
                                <button type="button" id="use-profile-current-location" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300">
                                    <i class="fas fa-location-crosshairs text-xs"></i>
                                    Use my current location
                                </button>
                            </div>
                            <p id="profile-location-status" class="mt-3 text-xs text-slate-500">Your street address stays editable after detection.</p>
                        </div>

                        <div class="space-y-2">
                            <label for="street" class="text-sm font-semibold text-slate-700">Street address</label>
                            <input
                                id="street"
                                type="text"
                                name="street"
                                class="client-profile-input"
                                value="{{ old('street', $user->street) }}"
                                placeholder="e.g. 123 Rizal Street"
                                required
                            >
                            @error('street')
                                <p class="text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="barangay" class="text-sm font-semibold text-slate-700">Barangay</label>
                            <select id="barangay" name="barangay" class="client-profile-input" required>
                                <option value="">Select barangay</option>
                                @foreach ($barangays as $b)
                                    <option value="{{ $b }}" {{ old('barangay', $user->barangay) === $b ? 'selected' : '' }}>
                                        {{ $b }}
                                    </option>
                                @endforeach
                            </select>
                            @error('barangay')
                                <p class="text-xs font-medium text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </section>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-emerald-200/70 transition hover:-translate-y-0.5 hover:bg-blue-700"
                    >
                        <i class="fas fa-floppy-disk text-xs"></i>
                        Save changes
                    </button>
                    <a
                        href="{{ route('client.dashboard') }}"
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
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-600 text-sm font-bold text-white shadow-md">
                            {{ $initials }}
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Current profile</h2>
                            <p class="text-sm text-slate-500">A quick view of the details clients and staff rely on.</p>
                        </div>
                    </div>

                    <div class="rounded-[1.4rem] border border-slate-100 bg-slate-50/80 p-5 text-center">
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-blue-600 text-2xl font-black text-white shadow-lg">
                            {{ $initials }}
                        </div>
                        <div class="mt-3 text-base font-bold text-slate-900">{{ $user->display_name }}</div>
                        <div class="mt-1 text-sm text-slate-500">{{ $user->email }}</div>
                    </div>

                    <div class="mt-5 space-y-3">
                        @foreach ($summaryItems as $item)
                            <div class="client-profile-summary-row">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-white text-slate-500 shadow-sm">
                                        <i class="fas {{ $item['icon'] }} text-sm"></i>
                                    </span>
                                    <span class="text-sm font-medium text-slate-500">{{ $item['label'] }}</span>
                                </div>
                                <span class="client-profile-summary-value text-sm">{{ $item['value'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="cleanflow-panel border border-amber-100 bg-amber-50/80 p-6">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-amber-600 shadow-sm">
                            <i class="fas fa-lightbulb text-base"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Quick tips</h2>
                            <p class="text-sm text-slate-500">Small updates here can save time later in the booking flow.</p>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('use-profile-current-location');
    const status = document.getElementById('profile-location-status');
    const streetInput = document.getElementById('street');
    const barangaySelect = document.getElementById('barangay');
    const barangayCenters = @json($barangayCenters);

    function setProfileLocationStatus(message, state = 'neutral') {
        if (!status) {
            return;
        }

        const classes = {
            neutral: 'mt-3 text-xs text-slate-500',
            success: 'mt-3 text-xs font-medium text-emerald-700',
            warning: 'mt-3 text-xs font-medium text-amber-700',
            error: 'mt-3 text-xs font-medium text-red-600',
        };

        status.className = classes[state] || classes.neutral;
        status.textContent = message;
    }

    function resetProfileLocationButton() {
        if (!button) {
            return;
        }

        button.disabled = false;
        button.innerHTML = '<i class="fas fa-location-crosshairs text-xs"></i> Use my current location';
    }

    function distanceKm(lat1, lng1, lat2, lng2) {
        const earthRadiusKm = 6371;
        const toRadians = (degrees) => degrees * Math.PI / 180;
        const dLat = toRadians(lat2 - lat1);
        const dLng = toRadians(lng2 - lng1);
        const a = Math.sin(dLat / 2) ** 2
            + Math.cos(toRadians(lat1)) * Math.cos(toRadians(lat2)) * Math.sin(dLng / 2) ** 2;

        return earthRadiusKm * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    }

    function nearestBarangay(lat, lng) {
        return Object.entries(barangayCenters)
            .map(([name, center]) => ({
                name,
                distance: distanceKm(lat, lng, Number(center.lat), Number(center.lng)),
            }))
            .sort((a, b) => a.distance - b.distance)[0] || null;
    }

    function streetAddressFromOpenStreetMap(payload) {
        const address = payload?.address || {};
        const road = address.road || address.neighbourhood || address.suburb || address.village || '';
        const parts = [address.house_number, road].filter(Boolean);

        return parts.length ? parts.join(', ') : '';
    }

    async function reverseGeocodeStreet(lat, lng) {
        const url = new URL('https://nominatim.openstreetmap.org/reverse');
        url.searchParams.set('format', 'jsonv2');
        url.searchParams.set('lat', String(lat));
        url.searchParams.set('lon', String(lng));
        url.searchParams.set('zoom', '18');
        url.searchParams.set('addressdetails', '1');

        const response = await fetch(url.toString(), {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            return '';
        }

        return streetAddressFromOpenStreetMap(await response.json());
    }

    function selectBarangay(name) {
        if (!barangaySelect || !name) {
            return false;
        }

        const option = Array.from(barangaySelect.options).find((item) => item.value === name);

        if (!option) {
            return false;
        }

        barangaySelect.value = name;
        barangaySelect.dispatchEvent(new Event('change', { bubbles: true }));

        return true;
    }

    button?.addEventListener('click', function () {
        if (!navigator.geolocation) {
            setProfileLocationStatus('Current location is not supported by this browser.', 'error');
            return;
        }

        button.disabled = true;
        button.innerHTML = '<i class="fas fa-circle-notch fa-spin text-xs"></i> Locating...';
        setProfileLocationStatus('Waiting for browser location permission...', 'neutral');

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = Number(position.coords.latitude);
                const lng = Number(position.coords.longitude);
                const nearest = nearestBarangay(lat, lng);
                const barangaySelected = nearest ? selectBarangay(nearest.name) : false;

                reverseGeocodeStreet(lat, lng)
                    .then((street) => {
                        if (street && streetInput && !streetInput.value.trim()) {
                            streetInput.value = street;
                        }

                        if (!nearest || !barangaySelected) {
                            setProfileLocationStatus('Location found, but no covered barangay matched. Select your barangay manually.', 'warning');
                            return;
                        }

                        const distanceText = nearest.distance < 1
                            ? `${Math.round(nearest.distance * 1000)}m`
                            : `${nearest.distance.toFixed(1)}km`;
                        const streetText = street ? ' Street was filled when available.' : ' Type your street or purok manually.';

                        setProfileLocationStatus(`Detected nearest barangay: ${nearest.name} (${distanceText} from its center).${streetText}`, 'success');
                    })
                    .catch(() => {
                        if (nearest && barangaySelected) {
                            setProfileLocationStatus(`Detected nearest barangay: ${nearest.name}. Type your street or purok manually.`, 'success');
                            return;
                        }

                        setProfileLocationStatus('Location found, but address lookup failed. Select your barangay manually.', 'warning');
                    })
                    .finally(resetProfileLocationButton);
            },
            (error) => {
                resetProfileLocationButton();

                const message = error.code === error.PERMISSION_DENIED
                    ? 'Location permission was denied. Allow location access, then try again.'
                    : 'Could not get your current location. Make sure GPS/location services are enabled.';

                setProfileLocationStatus(message, 'error');
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0,
            }
        );
    });
});
</script>
@endpush
