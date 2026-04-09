@csrf

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label for="first_name" class="mb-1 block text-sm font-semibold text-gray-700">First Name</label>
        <input id="first_name" type="text" name="first_name" autocomplete="given-name" value="{{ old('first_name', optional($staff)->first_name) }}" placeholder="Enter first name" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
        @error('first_name')
            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label for="last_name" class="mb-1 block text-sm font-semibold text-gray-700">Last Name</label>
        <input id="last_name" type="text" name="last_name" autocomplete="family-name" value="{{ old('last_name', optional($staff)->last_name) }}" placeholder="Enter last name" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
        @error('last_name')
            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label for="email" class="mb-1 block text-sm font-semibold text-gray-700">Email Address</label>
        <input id="email" type="email" name="email" autocomplete="email" value="{{ old('email', optional($staff)->email) }}" placeholder="name@example.com" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
        @error('email')
            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label for="phone" class="mb-1 block text-sm font-semibold text-gray-700">Phone Number</label>
        <input id="phone" type="text" name="phone" autocomplete="tel" value="{{ old('phone', optional($staff)->phone) }}" placeholder="09XXXXXXXXX" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
        @error('phone')
            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label for="barangay" class="mb-1 block text-sm font-semibold text-gray-700">Assigned Barangay</label>
        <select id="barangay" name="barangay" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
            <option value="">Select barangay</option>
            @foreach($barangays as $value => $label)
                <option value="{{ $value }}" {{ old('barangay', optional($staff)->barangay) === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('barangay')
            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label for="username" class="mb-1 block text-sm font-semibold text-gray-700">Username</label>
        <input id="username" type="text" name="username" autocomplete="username" value="{{ old('username', optional($staff)->username) }}" placeholder="Choose a username" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none" minlength="5" maxlength="20">
        @error('username')
            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div>
        <label for="password" class="mb-1 block text-sm font-semibold text-gray-700">
            Password @if(!isset($staff))<span class="text-red-500">*</span>@endif
        </label>
        <input id="password" type="password" name="password" autocomplete="new-password" class="w-full rounded-xl border border-gray-300 px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none" @if(!isset($staff)) required @endif>
        @error('password')
            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
        @enderror
        @isset($staff)
            <p class="mt-1 text-xs text-gray-500">Leave this blank if you want to keep the current password.</p>
        @endisset
    </div>
    <div>
        <label class="mb-1 block text-sm font-semibold text-gray-700">Account Role</label>
        <input type="text" value="Staff" class="w-full rounded-xl border border-gray-200 bg-gray-100 px-3 py-2.5 text-sm text-gray-600" disabled>
        <input type="hidden" name="role" value="staff">
    </div>
</div>

<div class="flex gap-3 pt-2">
    <button type="submit" class="rounded-xl bg-emerald-600 px-5 py-2.5 font-semibold text-white hover:bg-emerald-700">
        {{ isset($staff) ? 'Save Staff Changes' : 'Create Staff Member' }}
    </button>
    <a href="{{ route('admin.staff.index') }}" class="rounded-xl border border-gray-300 px-5 py-2.5 font-semibold text-gray-700 hover:bg-gray-100">
        Back to Staff
    </a>
</div>
