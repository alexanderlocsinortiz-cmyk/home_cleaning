@extends('layouts.client')
@section('title','My Profile')

@section('content')
@php
    $birthday = optional($user->date_of_birth)->format('M d, Y') ?: 'Not set';
    $gender = $user->gender ? ucfirst(str_replace('_', ' ', $user->gender)) : 'Not set';
    $address = $user->street && $user->barangay && $user->zip_code
        ? $user->street . ', ' . ucwords(str_replace('_', ' ', $user->barangay)) . ', ' . $user->city . ' ' . $user->zip_code
        : 'Not set';
@endphp
<section class="py-8 px-8">
    <div class="max-w-4xl mx-auto">
        @if(session('success'))
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 flex items-center gap-3">
                <i class="fas fa-check-circle text-green-500"></i>
                <span class="text-green-700">{{ session('success') }}</span>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex justify-between items-center gap-3 flex-wrap mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 mb-1">Profile</h2>
                    <p class="text-gray-600">Your account details</p>
                </div>
                <a href="{{ route('client.profile.edit') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2 transition-colors">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-600">
                <div><strong class="text-gray-800">Full Name:</strong> {{ $user->first_name }} {{ $user->last_name }}</div>
                <div><strong class="text-gray-800">Email:</strong> {{ $user->email }}</div>
                <div><strong class="text-gray-800">Phone:</strong> {{ $user->phone ?? 'Not set' }}</div>
                <div><strong class="text-gray-800">Birthday:</strong> {{ $birthday }}</div>
                <div><strong class="text-gray-800">Gender:</strong> {{ $gender }}</div>
                <div><strong class="text-gray-800">Address:</strong> {{ $address }}</div>
                <div><strong class="text-gray-800">Member Since:</strong> {{ optional($user->created_at)->format('M d, Y') }}</div>
            </div>
        </div>
    </div>
</section>
@endsection

