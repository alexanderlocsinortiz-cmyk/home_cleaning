@extends('layouts.app')

@section('title', 'Activate Cleaner Account')

@section('content')
<section class="min-h-screen bg-slate-50 px-5 py-8 sm:py-12">
    <div class="mx-auto grid max-w-5xl overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10 lg:grid-cols-[0.85fr_1.15fr]">
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-950 via-blue-900 to-blue-700 p-7 text-white sm:p-10">
            <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-cyan-300/15 blur-3xl"></div>
            <div class="relative z-10 flex h-full flex-col">
                <img src="{{ asset('images/logo.png') }}?v=20260510-logo4" alt="Home Cleaning Service" class="h-16 w-auto object-contain object-left brightness-0 invert sm:h-20">
                <span class="mt-10 inline-flex w-fit items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-black uppercase tracking-[0.14em] text-blue-100 ring-1 ring-white/15">
                    <i class="fas fa-shield-halved"></i>
                    Approved provider
                </span>
                <h1 class="mt-5 text-3xl font-black tracking-tight sm:text-4xl">Your cleaner account starts here.</h1>
                <p class="mt-4 text-sm leading-7 text-blue-100">Create a secure login for <strong class="text-white">{{ $application->business_name }}</strong> and manage assignments, proof of service, and payouts.</p>
                <div class="mt-8 space-y-4 text-sm font-semibold text-blue-50">
                    <div class="flex items-start gap-3"><i class="fas fa-check mt-1 text-emerald-300"></i><span>Your approved application is already connected.</span></div>
                    <div class="flex items-start gap-3"><i class="fas fa-check mt-1 text-emerald-300"></i><span>You will enter the cleaner portal after activation.</span></div>
                    <div class="flex items-start gap-3"><i class="fas fa-check mt-1 text-emerald-300"></i><span>Never share your password or activation link.</span></div>
                </div>
                <div class="mt-auto hidden border-t border-white/15 pt-8 text-xs leading-5 text-blue-200 lg:block">Need help? Contact CleanFlow admin using the details in your approval email.</div>
            </div>
        </div>

        <div class="p-7 sm:p-10">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.14em] text-blue-700">Step 1 of 1</p>
                <h2 class="mt-2 text-3xl font-black tracking-tight text-slate-950">Create your login</h2>
                <p class="mt-3 text-sm leading-7 text-slate-500">Set a password to activate access for <strong class="text-slate-800">{{ $application->business_name }}</strong>.</p>
            </div>

            @if($errors->any())
                <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="mt-6 rounded-2xl border border-blue-100 bg-blue-50/70 p-4 text-sm">
                <div class="flex items-center gap-2 font-black text-blue-800"><i class="fas fa-circle-check"></i> Approved application</div>
                <div class="mt-3 grid gap-2 text-slate-700 sm:grid-cols-2">
                    <div><span class="font-semibold">Email:</span><br>{{ $application->email }}</div>
                    <div><span class="font-semibold">Provider type:</span><br>{{ $application->isTeam() ? 'Cleaning Team / Business' : 'Individual Cleaner' }}</div>
                    <div class="sm:col-span-2"><span class="font-semibold">Service area:</span> {{ $application->service_area }}</div>
                </div>
            </div>

            <form method="POST" action="{{ route('provider.activate.store', ['token' => $token]) }}" class="mt-7 space-y-5" data-activation-form>
                @csrf

                <div>
                    <label for="username" class="block text-sm font-bold text-slate-800">Username</label>
                    <input id="username" name="username" value="{{ old('username') }}" minlength="5" maxlength="20" autocomplete="username" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" placeholder="Optional">
                    <div class="mt-1 text-xs text-slate-500">Optional. Use 5–20 characters if you add one.</div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-bold text-slate-800">Password</label>
                    <div class="relative mt-2">
                        <input id="password" type="password" name="password" required minlength="12" autocomplete="new-password" aria-describedby="password-help" class="w-full rounded-xl border border-slate-200 px-4 py-3 pr-12 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <button type="button" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-slate-400 transition hover:text-blue-700" data-password-toggle="password" aria-label="Show password"><i class="fas fa-eye"></i></button>
                    </div>
                    <div id="password-help" class="mt-1 text-xs text-slate-500">Use at least 12 characters with uppercase, lowercase, a number, and a symbol.</div>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-bold text-slate-800">Confirm Password</label>
                    <div class="relative mt-2">
                        <input id="password_confirmation" type="password" name="password_confirmation" required minlength="12" autocomplete="new-password" class="w-full rounded-xl border border-slate-200 px-4 py-3 pr-12 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <button type="button" class="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-slate-400 transition hover:text-blue-700" data-password-toggle="password_confirmation" aria-label="Show password"><i class="fas fa-eye"></i></button>
                    </div>
                </div>

                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-700">
                    <i class="fas fa-user-check"></i>
                    Activate Cleaner Account
                </button>
            </form>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
    button.addEventListener('click', function () {
        const input = document.getElementById(button.dataset.passwordToggle);
        const icon = button.querySelector('i');
        const visible = input.type === 'text';

        input.type = visible ? 'password' : 'text';
        button.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
        icon.classList.toggle('fa-eye', visible);
        icon.classList.toggle('fa-eye-slash', !visible);
    });
});
</script>
@endpush
