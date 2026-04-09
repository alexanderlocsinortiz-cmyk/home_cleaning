@extends('layouts.app')
@section('title', 'Verify Email - Home Cleaning Service')

@section('content')
<div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-lg p-8 text-center">
        <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-envelope text-emerald-600 text-3xl"></i>
        </div>

        <h2 class="text-2xl font-bold text-gray-800 mb-2">Verify Your Email</h2>
        <p class="text-gray-500 mb-6">
            Enter the 6-digit verification code sent to
            <strong class="text-gray-800">{{ auth()->user()->email }}</strong>
        </p>

        @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 mb-4 text-sm">
            {{ session('success') }}
        </div>
        @endif

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-4 text-sm text-left">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('verification.verify') }}" class="text-left">
            @csrf
            <label for="code" class="block text-sm font-semibold text-gray-700 mb-2">Verification Code</label>
            <input
                id="code"
                type="text"
                name="code"
                value="{{ old('code') }}"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="6"
                placeholder="Enter 6-digit code"
                class="w-full rounded-lg border border-gray-300 px-4 py-3 text-center text-lg font-semibold tracking-[0.35em] text-gray-800 focus:border-emerald-500 focus:outline-none"
            >
            <div class="mt-2 mb-4 text-xs text-gray-500">
                Codes expire in {{ $codeExpiresInMinutes ?? config('auth.verification.expire', 15) }} minutes.
            </div>
            <button type="submit"
                class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 rounded-lg transition">
                <i class="fas fa-shield-check mr-2"></i>Verify Email
            </button>
        </form>

        <form method="POST" action="{{ route('verification.send') }}" class="mt-4">
            @csrf
            <button type="submit"
                class="w-full bg-white hover:bg-gray-50 text-emerald-700 font-semibold py-3 rounded-lg border border-emerald-200 transition mb-4">
                <i class="fas fa-paper-plane mr-2"></i>Send New Verification Code
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-gray-400 hover:text-gray-600 text-sm transition">
                <i class="fas fa-sign-out-alt mr-1"></i>Logout
            </button>
        </form>
    </div>
</div>
@endsection
