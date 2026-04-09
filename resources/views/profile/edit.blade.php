@extends('layouts.app')
@section('title', 'Edit Profile')

@section('content')
<section class="form-section" style="padding-top:2rem;">
    <div class="form-container" style="max-width:900px;">
        <div class="form-header">
            <div class="form-header-icon"><i class="fas fa-user-cog"></i></div>
            <h2>Edit Profile</h2>
            <p>Keep your contact and address details up to date.</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-section-title"><i class="fas fa-user"></i> Personal Information</div>
            <div class="form-row">
                <div class="form-group">
                    <label>First Name <span class="required">*</span></label>
                    <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $user->first_name) }}" required>
                    @error('first_name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label>Last Name <span class="required">*</span></label>
                    <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $user->last_name) }}" required>
                    @error('last_name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" placeholder="09XXXXXXXXX">
                    @error('phone')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label>Birthday</label>
                    <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', optional($user->date_of_birth)->format('Y-m-d')) }}">
                    @error('date_of_birth')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="form-group">
                <label>Gender</label>
                <select name="gender" class="form-control">
                    <option value="">Select gender</option>
                    <option value="male" {{ old('gender', $user->gender) == 'male' ? 'selected' : '' }}>Male</option>
                    <option value="female" {{ old('gender', $user->gender) == 'female' ? 'selected' : '' }}>Female</option>
                    <option value="prefer_not_to_say" {{ old('gender', $user->gender) == 'prefer_not_to_say' ? 'selected' : '' }}>Prefer not to say</option>
                </select>
                @error('gender')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="form-section-title"><i class="fas fa-map-marker-alt"></i> Address</div>
            <div class="form-group">
                <label>Street <span class="required">*</span></label>
                <input type="text" name="street" class="form-control" value="{{ old('street', $user->street) }}" required>
                @error('street')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Barangay <span class="required">*</span></label>
                    <select name="barangay" class="form-control" required>
                        <option value="">Select barangay</option>
                        @foreach($barangays as $value => $label)
                            <option value="{{ $value }}" {{ old('barangay', $user->barangay) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('barangay')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label>ZIP Code <span class="required">*</span></label>
                    <input type="text" name="zip_code" class="form-control" value="{{ old('zip_code', $user->zip_code) }}" required>
                    @error('zip_code')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="form-section-title"><i class="fas fa-lock"></i> Account</div>
            <div class="form-group">
                <label>Email (read only)</label>
                <input type="email" class="form-control" value="{{ $user->email }}" disabled>
            </div>

            <div class="form-section-title"><i class="fas fa-key"></i> Change Password (optional)</div>
            <div class="form-row">
                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" class="form-control">
                    @error('current_password')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" class="form-control">
                    @error('new_password')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="new_password_confirmation" class="form-control">
                @error('new_password_confirmation')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:1rem;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                <a href="{{ route('profile.show') }}" class="btn" style="background:#eee;color:var(--text-dark);"><i class="fas fa-arrow-left"></i> Cancel</a>
            </div>
        </form>
    </div>
</section>
@endsection

