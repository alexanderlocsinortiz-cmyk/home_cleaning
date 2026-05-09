@csrf

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <div>
        <label for="first_name" class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">First Name</label>
        <input id="first_name" type="text" name="first_name" autocomplete="given-name" value="{{ old('first_name', optional($staff)->first_name) }}" placeholder="Enter first name" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
        @error('first_name')
            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label for="last_name" class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Last Name</label>
        <input id="last_name" type="text" name="last_name" autocomplete="family-name" value="{{ old('last_name', optional($staff)->last_name) }}" placeholder="Enter last name" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
        @error('last_name')
            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <div>
        <label for="email" class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Email Address</label>
        <input id="email" type="email" name="email" autocomplete="email" value="{{ old('email', optional($staff)->email) }}" placeholder="name@example.com" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
        @error('email')
            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
        @enderror
    </div>
    <div>
        <label for="phone" class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Phone Number</label>
        <input id="phone" type="text" name="phone" autocomplete="tel" value="{{ old('phone', optional($staff)->phone) }}" placeholder="09XXXXXXXXX" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
        @error('phone')
            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <div>
        <label for="barangay" class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Assigned Barangay</label>
        <select id="barangay" name="barangay" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100">
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
        <label for="username" class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Username</label>
        <input id="username" type="text" name="username" autocomplete="username" value="{{ old('username', optional($staff)->username) }}" placeholder="Choose a username" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100" minlength="5" maxlength="20">
        @error('username')
            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <div>
        <label for="password" class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">
            Password @if(!isset($staff))<span class="text-red-500">*</span>@endif
        </label>
        <input id="password" type="password" name="password" autocomplete="new-password" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm transition focus:border-emerald-500 focus:outline-hidden focus:ring-4 focus:ring-emerald-100" @if(!isset($staff)) required @endif>
        @error('password')
            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
        @enderror
        @isset($staff)
            <p class="mt-2 text-xs text-slate-500">Leave this blank if you want to keep the current password.</p>
        @endisset
    </div>
    <div>
        <label class="mb-2 block text-xs font-extrabold uppercase tracking-[0.16em] text-slate-500">Account Role</label>
        <input type="text" value="Staff" class="w-full rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-500" disabled>
        <input type="hidden" name="role" value="staff">
    </div>
</div>

<div class="flex flex-wrap gap-3 border-t border-slate-100 pt-4">
    <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700">
        <i class="fas fa-save"></i>
        {{ isset($staff) ? 'Save Staff Changes' : 'Create Staff Member' }}
    </button>
    <a href="{{ route('admin.staff.index') }}" class="inline-flex items-center gap-2 rounded-full border border-slate-300 px-5 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-100">
        <i class="fas fa-arrow-left"></i>
        Back to Staff
    </a>
</div>
