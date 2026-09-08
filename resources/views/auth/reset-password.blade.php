@extends('layouts.app')
@section('title', 'Reset Password')

@section('content')
<div class="auth-shell min-h-screen bg-slate-50 text-slate-950">
    <div class="flex min-h-screen items-center justify-center px-5 py-8">
        <div class="w-full max-w-[520px]">
            <div class="relative rounded-[28px] border border-white bg-white/95 px-7 py-9 shadow-[0_24px_70px_rgba(37,99,235,0.12)] sm:px-12">
                <a href="{{ route('password.request') }}" class="absolute right-5 top-5 flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-blue-600 shadow-sm transition hover:border-blue-200 hover:bg-blue-50" title="Request new code" aria-label="Request new code">
                    <i class="fas fa-arrow-left"></i>
                </a>

                <div class="text-center">
                    <img src="{{ asset('images/logo.png') }}?v=20260510-logo4" alt="Home Cleaning Service" class="mx-auto h-24 w-auto object-contain">
                    <h2 class="mt-6 text-3xl font-black tracking-tight text-slate-950">Reset Password</h2>
                    <p class="mt-3 text-base leading-7 text-slate-500">
                        Enter the 6-digit code sent to <strong class="font-bold text-slate-800">{{ $email }}</strong>, then choose a new password.
                    </p>
                </div>

                @if(session('success'))
                    <div class="mt-7 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        <i class="fas fa-circle-check mt-0.5 text-emerald-600"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mt-7 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <i class="fas fa-circle-exclamation mt-0.5 text-red-500"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-6">
                    @csrf

                    <div>
                        <label for="code" class="mb-3 block text-sm font-bold text-slate-800">
                            Reset Code <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-5 text-blue-600">
                                <i class="fas fa-key"></i>
                            </span>
                            <input
                                id="code"
                                type="text"
                                name="code"
                                value="{{ old('code') }}"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                maxlength="6"
                                pattern="[0-9]{6}"
                                placeholder="Enter 6-digit code"
                                required
                                autofocus
                                oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6)"
                                class="h-16 w-full rounded-2xl border border-slate-200 bg-white pl-14 pr-5 text-center text-lg font-bold tracking-[0.28em] text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                            >
                        </div>
                        <div class="mt-2 text-sm text-slate-500">
                            Codes expire in {{ $codeExpiresInMinutes }} minutes.
                        </div>
                    </div>

                    <div>
                        <label for="password" class="mb-3 block text-sm font-bold text-slate-800">
                            New Password <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-5 text-blue-600">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                autocomplete="new-password"
                                placeholder="Enter new password"
                                minlength="12"
                                aria-describedby="password-help"
                                required
                                class="h-16 w-full rounded-2xl border border-slate-200 bg-white pl-14 pr-14 text-base text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                            >
                            <button type="button" onclick="togglePw('password', this)" class="absolute inset-y-0 right-0 flex items-center pr-5 text-slate-400 transition hover:text-slate-700" aria-label="Toggle password visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <p id="password-help" class="mt-2 text-xs text-slate-500">Use at least 12 characters with uppercase, lowercase, a number, and a symbol.</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-3 block text-sm font-bold text-slate-800">
                            Confirm Password <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-5 text-blue-600">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                autocomplete="new-password"
                                placeholder="Confirm new password"
                                minlength="12"
                                required
                                class="h-16 w-full rounded-2xl border border-slate-200 bg-white pl-14 pr-14 text-base text-slate-900 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                            >
                            <button type="button" onclick="togglePw('password_confirmation', this)" class="absolute inset-y-0 right-0 flex items-center pr-5 text-slate-400 transition hover:text-slate-700" aria-label="Toggle password visibility">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="flex h-16 w-full items-center justify-center gap-3 rounded-2xl bg-blue-600 text-base font-bold text-white shadow-[0_14px_28px_rgba(37,99,235,0.28)] transition hover:bg-blue-700">
                        <i class="fas fa-shield-halved"></i>
                        <span>Reset Password</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function togglePw(id, btn) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
    btn.innerHTML = input.type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
}
</script>
@endsection
