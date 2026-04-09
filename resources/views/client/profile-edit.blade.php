@extends('layouts.client')
@section('title', 'Edit Profile - Home Cleaning Service')

@section('content')
<style>
    .form-input { transition: all 0.15s ease; }
    .form-input:focus { border-color: #1D9E75 !important; box-shadow: 0 0 0 3px rgba(37,99,235,0.1) !important; outline: none !important; }
    .save-btn { transition: all 0.2s ease; }
    .save-btn:hover { background: #0F6E56 !important; transform: translateY(-2px); box-shadow: 0 6px 16px rgba(37,99,235,0.35) !important; }
    .cancel-btn { transition: all 0.15s ease; }
    .cancel-btn:hover { background: #e2e8f0 !important; color: #1e293b !important; }
</style>
<style>
@media (max-width: 767px) {
    .profile-padding { padding: 1rem !important; }
    .profile-grid { grid-template-columns: 1fr !important; }
    .profile-form-grid { grid-template-columns: 1fr !important; }
    .profile-addr-grid { grid-template-columns: 1fr !important; }
    .profile-actions { flex-direction: column !important; }
    .profile-actions a, .profile-actions button { width: 100%; justify-content: center; }
}
</style>

<div class="profile-padding" style='background: #f1f5f9; min-height: calc(100vh - 73px); padding: 1.75rem 2rem; font-family: DM Sans, sans-serif;'>
<div style='max-width: 900px; margin: 0 auto;'>

    @if(session('success'))
    <div style='background: #dcfce7; border: 1px solid #86efac; color: #16a34a; border-radius: 10px; padding: 12px 16px; margin-bottom: 1.25rem; font-size: 14px; display: flex; align-items: center; gap: 8px;'>✅ {{ session('success') }}</div>
    @endif

    @php
        $user = auth()->user();
        $initials = strtoupper(substr($user->first_name,0,1).substr($user->last_name,0,1));
        $barangayLabel = $user->barangay ? ucfirst(str_replace('_', ' ', $user->barangay)) : 'Not set';
    @endphp

    {{-- HEADER --}}
    <div style='margin-bottom: 1.25rem; padding-top: 0.5rem;'>
        <div style='display: flex; align-items: center; gap: 8px; margin-bottom: 8px;'>
            <a href="{{ route('client.dashboard') }}" style='background: white; color: #64748b; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px 14px; font-size: 13px; font-weight: 600; text-decoration: none; transition: all 0.15s;'>← Back</a>
            <span style='color: #94a3b8; font-size: 13px;'>/</span>
            <span style='color: #94a3b8; font-size: 13px;'>Edit Profile</span>
        </div>
        <h1 style='font-size: 26px; font-weight: 800; color: #1e293b; margin-bottom: 4px;'>✏️ Edit Profile</h1>
        <p style='font-size: 13px; color: #94a3b8;'>Update your personal and contact information</p>
    </div>

    <div class="profile-grid" style='display: grid; grid-template-columns: 1fr 300px; gap: 1.25rem;'>

        {{-- FORM --}}
        <div style='display: flex; flex-direction: column; gap: 1.25rem;'>

            <form action="{{ route('client.profile.update') }}" method="POST">
            @csrf @method('PUT')

            {{-- PERSONAL INFO --}}
            <div style='background: #ffffff; border-radius: 16px; padding: 1.75rem; box-shadow: 0 1px 4px rgba(0,0,0,0.06); border: 1px solid #f1f5f9;'>
                <div style='display: flex; align-items: center; gap: 10px; margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid #f8fafc;'>
                    <div style='width: 36px; height: 36px; border-radius: 10px; background: #E1F5EE; display: flex; align-items: center; justify-content: center; font-size: 18px;'>👤</div>
                    <div>
                        <div style='font-size: 15px; font-weight: 700; color: #1e293b;'>Personal Information</div>
                        <div style='font-size: 12px; color: #94a3b8;'>Your basic profile details</div>
                    </div>
                </div>

                <div class="profile-form-grid" style='display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem;'>
                    <div>
                        <label style='font-size: 13px; font-weight: 600; color: #374151; display: block; margin-bottom: 6px;'>First Name <span style='color: #ef4444;'>*</span></label>
                        <input type='text' name='first_name' class='form-input' value='{{ old("first_name", $user->first_name) }}' placeholder='First name' style='width: 100%; border: 1.5px solid #dde3ed; border-radius: 10px; padding: 11px 14px; font-size: 14px; box-sizing: border-box;' required>
                        @error('first_name')<p style='color: #dc2626; font-size: 12px; margin-top: 4px;'>{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label style='font-size: 13px; font-weight: 600; color: #374151; display: block; margin-bottom: 6px;'>Last Name <span style='color: #ef4444;'>*</span></label>
                        <input type='text' name='last_name' class='form-input' value='{{ old("last_name", $user->last_name) }}' placeholder='Last name' style='width: 100%; border: 1.5px solid #dde3ed; border-radius: 10px; padding: 11px 14px; font-size: 14px; box-sizing: border-box;' required>
                        @error('last_name')<p style='color: #dc2626; font-size: 12px; margin-top: 4px;'>{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="profile-form-grid" style='display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;'>
                    <div>
                        <label style='font-size: 13px; font-weight: 600; color: #374151; display: block; margin-bottom: 6px;'>📱 Phone Number <span style='color: #ef4444;'>*</span></label>
                        <input type='text' name='phone' class='form-input' value='{{ old("phone", $user->phone) }}' placeholder='09XXXXXXXXX' style='width: 100%; border: 1.5px solid #dde3ed; border-radius: 10px; padding: 11px 14px; font-size: 14px; box-sizing: border-box;' required>
                        @error('phone')<p style='color: #dc2626; font-size: 12px; margin-top: 4px;'>{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label style='font-size: 13px; font-weight: 600; color: #374151; display: block; margin-bottom: 6px;'>📧 Email Address</label>
                        <input type='email' value='{{ $user->email }}' disabled style='width: 100%; border: 1.5px solid #dde3ed; border-radius: 10px; padding: 11px 14px; font-size: 14px; box-sizing: border-box; background: #f8fafc; color: #94a3b8; cursor: not-allowed;'>
                        <p style='font-size: 11px; color: #94a3b8; margin-top: 4px;'>Email cannot be changed</p>
                    </div>
                </div>
            </div>

            {{-- ADDRESS INFO --}}
            <div style='background: #ffffff; border-radius: 16px; padding: 1.75rem; box-shadow: 0 1px 4px rgba(0,0,0,0.06); border: 1px solid #f1f5f9;'>
                <div style='display: flex; align-items: center; gap: 10px; margin-bottom: 1.25rem; padding-bottom: 1rem; border-bottom: 1px solid #f8fafc;'>
                    <div style='width: 36px; height: 36px; border-radius: 10px; background: #fefce8; display: flex; align-items: center; justify-content: center; font-size: 18px;'>📍</div>
                    <div>
                        <div style='font-size: 15px; font-weight: 700; color: #1e293b;'>Address Information</div>
                        <div style='font-size: 12px; color: #94a3b8;'>Your service location details</div>
                    </div>
                </div>

                <div style='margin-bottom: 1.25rem;'>
                    <label style='font-size: 13px; font-weight: 600; color: #374151; display: block; margin-bottom: 6px;'>Street Address <span style='color: #ef4444;'>*</span></label>
                    <input type='text' name='street' class='form-input' value='{{ old("street", $user->street) }}' placeholder='e.g. 123 Rizal Street' style='width: 100%; border: 1.5px solid #dde3ed; border-radius: 10px; padding: 11px 14px; font-size: 14px; box-sizing: border-box;' required>
                    @error('street')<p style='color: #dc2626; font-size: 12px; margin-top: 4px;'>{{ $message }}</p>@enderror
                </div>

                <div class="profile-addr-grid" style='display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;'>
                    <div>
                        <label style='font-size: 13px; font-weight: 600; color: #374151; display: block; margin-bottom: 6px;'>Barangay <span style='color: #ef4444;'>*</span></label>
                        <select name='barangay' class='form-input' style='width: 100%; border: 1.5px solid #dde3ed; border-radius: 10px; padding: 11px 14px; font-size: 14px; background: white; appearance: none; cursor: pointer; box-sizing: border-box;' required>
                            <option value=''>Select barangay</option>
                            @foreach($barangays as $b)
                            <option value='{{ $b }}' {{ old('barangay', $user->barangay) === $b ? 'selected' : '' }}>{{ $b }}</option>
                            @endforeach
                        </select>
                        @error('barangay')<p style='color: #dc2626; font-size: 12px; margin-top: 4px;'>{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label style='font-size: 13px; font-weight: 600; color: #374151; display: block; margin-bottom: 6px;'>ZIP Code <span style='color: #ef4444;'>*</span></label>
                        <input type='text' name='zip_code' class='form-input' value='{{ old("zip_code", $user->zip_code) }}' placeholder='8504' maxlength='4' style='width: 100%; border: 1.5px solid #dde3ed; border-radius: 10px; padding: 11px 14px; font-size: 14px; box-sizing: border-box;' required>
                        @error('zip_code')<p style='color: #dc2626; font-size: 12px; margin-top: 4px;'>{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- SUBMIT BUTTONS --}}
            <div class="profile-actions" style='display: flex; gap: 10px;'>
                <button type='submit' class='save-btn' style='background: #1D9E75; color: white; border: none; border-radius: 12px; padding: 13px 32px; font-size: 15px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 8px rgba(37,99,235,0.25);'>
                    💾 Save Changes
                </button>
                <a href="{{ route('client.dashboard') }}" class='cancel-btn' style='background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; border-radius: 12px; padding: 13px 24px; font-size: 15px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 6px;'>
                    ← Cancel
                </a>
            </div>

            </form>
        </div>

        {{-- RIGHT SIDEBAR - CURRENT INFO --}}
        <div style='display: flex; flex-direction: column; gap: 1.25rem;'>

            {{-- PROFILE PREVIEW --}}
            <div style='background: #ffffff; border-radius: 16px; padding: 1.5rem; box-shadow: 0 1px 4px rgba(0,0,0,0.06); border: 1px solid #f1f5f9;'>
                <div style='font-size: 15px; font-weight: 700; color: #1e293b; margin-bottom: 1rem;'>👤 Current Profile</div>
                <div style='text-align: center; padding: 1rem 0; border-bottom: 1px solid #f8fafc; margin-bottom: 1rem;'>
                    <div style='width: 70px; height: 70px; border-radius: 50%; background: linear-gradient(135deg, #1D9E75, #1D9E75); display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: 24px; margin: 0 auto 10px;'>{{ $initials }}</div>
                    <div style='font-size: 16px; font-weight: 700; color: #1e293b;'>{{ $user->first_name }} {{ $user->last_name }}</div>
                    <div style='font-size: 12px; color: #94a3b8; margin-top: 2px;'>{{ $user->email }}</div>
                </div>
                <div style='display: flex; flex-direction: column; gap: 10px;'>
                    <div style='display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; background: #f8fafc; border-radius: 8px;'>
                        <span style='font-size: 12px; color: #94a3b8;'>📱 Phone</span>
                        <span style='font-size: 13px; color: #1e293b; font-weight: 600;'>{{ $user->phone ?? 'Not set' }}</span>
                    </div>
                    <div style='display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; background: #f8fafc; border-radius: 8px;'>
                        <span style='font-size: 12px; color: #94a3b8;'>📍 Barangay</span>
                        <span style='font-size: 13px; color: #1e293b; font-weight: 600;'>{{ $barangayLabel }}</span>
                    </div>
                    <div style='display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; background: #f8fafc; border-radius: 8px;'>
                        <span style='font-size: 12px; color: #94a3b8;'>🏠 Street</span>
                        <span style='font-size: 13px; color: #1e293b; font-weight: 600;'>{{ $user->street ?? 'Not set' }}</span>
                    </div>
                    <div style='display: flex; justify-content: space-between; align-items: center; padding: 8px 10px; background: #f8fafc; border-radius: 8px;'>
                        <span style='font-size: 12px; color: #94a3b8;'>📮 ZIP Code</span>
                        <span style='font-size: 13px; color: #1e293b; font-weight: 600;'>{{ $user->zip_code ?? 'Not set' }}</span>
                    </div>
                </div>
            </div>

            {{-- TIPS --}}
            <div style='background: #e8f0fe; border-radius: 16px; padding: 1.5rem; border: 1px solid #b8d0fb;'>
                <div style='font-size: 15px; font-weight: 700; color: #0F6E56; margin-bottom: 10px;'>💡 Tips</div>
                <div style='display: flex; flex-direction: column; gap: 8px;'>
                    <div style='display: flex; align-items: flex-start; gap: 8px;'>
                        <span style='color: #1D9E75; font-size: 14px; flex-shrink: 0;'>✓</span>
                        <span style='font-size: 12px; color: #0F6E56; line-height: 1.5;'>Keep your address updated for accurate service delivery</span>
                    </div>
                    <div style='display: flex; align-items: flex-start; gap: 8px;'>
                        <span style='color: #1D9E75; font-size: 14px; flex-shrink: 0;'>✓</span>
                        <span style='font-size: 12px; color: #0F6E56; line-height: 1.5;'>A valid phone number helps our staff contact you</span>
                    </div>
                    <div style='display: flex; align-items: flex-start; gap: 8px;'>
                        <span style='color: #1D9E75; font-size: 14px; flex-shrink: 0;'>✓</span>
                        <span style='font-size: 12px; color: #0F6E56; line-height: 1.5;'>Complete profiles get faster booking confirmations</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
</div>
@endsection

