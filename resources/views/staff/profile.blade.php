@extends('layouts.staff')
@section('title','My Profile')
@section('page-title','Profile')
@section('page-subtitle','Manage your contact information')

@section('content')
<div class="max-w-3xl">
    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-4 flex items-center gap-3">
            <i class="fas fa-check-circle text-green-500"></i>
            <span class="text-green-700 text-sm">{{ session('success') }}</span>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-xl font-bold text-slate-800 mb-4">Personal Information</h2>
        <form action="{{ route('staff.profile.update') }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">First Name</label>
                    <input type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-emerald-500">
                    @error('first_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Last Name</label>
                    <input type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-emerald-500">
                    @error('last_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                    <input type="email" value="{{ $user->email }}" class="w-full border border-gray-200 bg-gray-100 rounded-lg px-3 py-2" disabled>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-emerald-500">
                    @error('phone')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Barangay</label>
                <select name="barangay" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-emerald-500">
                    @foreach($barangays as $value => $label)
                        <option value="{{ $value }}" {{ old('barangay', $user->barangay) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('barangay')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3 flex-wrap">
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-lg font-semibold">Save Changes</button>
                <a href="{{ route('staff.dashboard') }}" class="border border-gray-300 text-gray-700 hover:bg-gray-100 px-5 py-2 rounded-lg font-semibold">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

