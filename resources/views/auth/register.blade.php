@extends('layouts.app')
@section('title', 'Register')

@section('content')
<script>
    // Redirect to unified login page with register tab
    window.location.href = "{{ route('login') }}?tab=signup";
</script>

<div class="flex items-center justify-center min-h-screen bg-slate-950">
    <div class="text-center">
        <p class="text-white/80 mb-2">Redirecting to registration...</p>
        <a href="{{ route('login') }}" class="text-primary-600 hover:text-primary-700 text-sm">Click here if not redirected</a>
    </div>
</div>
@endsection
        [
            'icon' => 'fa-calendar-check',
            'title' => 'Easy Online Booking',
            'text' => 'Create your account and request cleaning services with less friction.',
        ],
        [
            'icon' => 'fa-shield-halved',
            'title' => 'Secure Client Access',
            'text' => 'Email-based sign in keeps your account simple and consistent.',
        ],
        [
            'icon' => 'fa-route',
            'title' => 'Booking Progress Tracking',
            'text' => 'Monitor booking updates and service progress from your portal.',
        ],
        [
            'icon' => 'fa-location-dot',
            'title' => 'Valencia City Service Coverage',
            'text' => 'Built around local service areas and client support in Bukidnon.',
        ],
    ];
@endphp

<div class="min-h-screen bg-slate-950">
    <div class="relative mx-auto flex min-h-screen max-w-7xl items-center px-4 py-5 sm:px-6 lg:overflow-hidden lg:px-8 lg:py-4">
        <div class="grid w-full gap-4 lg:h-[calc(100vh-2rem)] lg:grid-cols-[minmax(0,1.08fr)_460px]">
            <aside class="hidden h-full min-h-0 flex-col justify-between overflow-hidden rounded-[32px] border border-white/15 bg-white/10 p-8 text-white shadow-[0_28px_80px_rgba(15,23,42,0.22)] backdrop-blur-md lg:flex">
                <div>
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/20">
                            <img src="{{ asset('images/logo.png') }}" alt="Home Cleaning Service" class="h-11 w-11 object-contain">
                        </div>
                        <div>
                            <div class="text-xl font-bold tracking-tight">Home Cleaning Service</div>
                            <div class="mt-1 text-sm text-white/75">Valencia City, Bukidnon</div>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-2 text-xs font-semibold text-white/85">
                        <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5">Email-Based Sign Up</span>
                        <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5">Local Booking Platform</span>
                    </div>

                    <div class="mt-8 max-w-xl">
                        <h1 class="text-4xl font-bold leading-tight tracking-tight">Create a cleaner way to manage bookings.</h1>
                        <p class="mt-4 max-w-lg text-sm leading-7 text-white/80">
                            Join the platform to book home cleaning services, track requests, and manage your client account through one professional portal.
                        </p>
                    </div>
                </div>

                <div class="grid gap-3">
                    @foreach($features as $feature)
                        <div class="flex items-start gap-3 rounded-2xl border border-white/15 bg-white/10 px-4 py-3.5">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15 text-white">
                                <i class="fas {{ $feature['icon'] }}"></i>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-white">{{ $feature['title'] }}</div>
                                <div class="mt-1 text-xs leading-5 text-white/75">{{ $feature['text'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="rounded-2xl border border-white/15 bg-slate-900/15 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-white/70">Account Setup</div>
                            <div class="mt-2 text-lg font-semibold text-white">Start with trusted contact details.</div>
                            <div class="mt-1 text-xs leading-5 text-white/75">Client registration now includes your phone number and birth date so bookings can be reviewed more reliably.</div>
                        </div>
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/15 text-white">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                </div>
            </aside>

            <section class="flex items-center justify-center">
                <div class="w-full max-w-md">
                    <div class="mb-4 text-center lg:hidden">
                        <a href="{{ url('/') }}" class="inline-flex flex-col items-center gap-3 text-center no-underline">
                            <div class="inline-flex rounded-full bg-white/20 p-2">
                                <img src="{{ asset('images/logo.png') }}" alt="Home Cleaning Service" class="h-14 w-14 object-contain drop-shadow-[0_8px_20px_rgba(0,0,0,0.22)]">
                            </div>
                            <div>
                                <div class="text-lg font-bold text-white">Home Cleaning Service</div>
                                <div class="mt-0.5 text-sm text-white/70">Valencia City, Bukidnon</div>
                            </div>
                        </a>
                    </div>

                    <div class="rounded-[30px] border border-white/80 bg-white/95 p-6 shadow-[0_26px_70px_rgba(15,23,42,0.20)] ring-1 ring-black/5 backdrop-blur-sm sm:p-7">
                        <div class="flex justify-center">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-600 text-lg text-white shadow-sm">
                                <i class="fas fa-user-plus"></i>
                            </div>
                        </div>

                        <div class="mt-5 text-center">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-primary-600">Client Registration</div>
                            <h2 class="mt-3 text-2xl font-bold text-slate-900">Create your account</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Sign up to book home cleaning services in Valencia City.
                            </p>
                        </div>

                        @if ($errors->any())
                            <div class="mt-5 flex items-center gap-2 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600 shadow-sm">
                                <i class="fas fa-exclamation-circle text-red-500"></i>
                                <span>{{ $errors->first() }}</span>
                            </div>
                        @endif

                        <form action="{{ route('register.store') }}" method="POST" class="mt-6 space-y-4">
                            @csrf

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-800">First Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="first_name" value="{{ old('first_name') }}" placeholder="First name" required autocomplete="given-name" class="{{ $errors->has('first_name') ? $inputBase . ' border-red-400 bg-red-900/20' : $defaultInput }}">
                                    @error('first_name')<p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-800">Last Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="last_name" value="{{ old('last_name') }}" placeholder="Last name" required autocomplete="family-name" class="{{ $errors->has('last_name') ? $inputBase . ' border-red-400 bg-red-900/20' : $defaultInput }}">
                                    @error('last_name')<p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-800">Email Address <span class="text-red-500">*</span></label>
                                <input type="email" name="email" value="{{ old('email') }}" placeholder="your@email.com" required autocomplete="email" class="{{ $errors->has('email') ? $inputBase . ' border-red-400 bg-red-900/20' : $defaultInput }}">
                                @error('email')<p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>@enderror
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-800">Phone Number <span class="text-red-500">*</span></label>
                                    <input type="text" name="phone" value="{{ old('phone') }}" placeholder="09XXXXXXXXX" required autocomplete="tel" class="{{ $errors->has('phone') ? $inputBase . ' border-red-400 bg-red-900/20' : $defaultInput }}">
                                    @error('phone')<p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-800">Date of Birth <span class="text-red-500">*</span></label>
                                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" required autocomplete="bday" class="{{ $errors->has('date_of_birth') ? $inputBase . ' border-red-400 bg-red-900/20' : $defaultInput }}">
                                    @error('date_of_birth')<p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-800">Password <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="password" name="password" id="pw" placeholder="Minimum 8 characters" required autocomplete="new-password" class="{{ $errors->has('password') ? $inputBase . ' border-red-400 bg-red-900/20 pr-12' : $defaultInput . ' pr-12' }}">
                                        <button type="button" onclick="togglePw('pw', this)" class="absolute inset-y-0 right-0 flex items-center pr-4 text-sm text-slate-400 transition hover:text-slate-300" aria-label="Toggle password visibility">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    @error('password')<p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-800">Confirm Password <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <input type="password" name="password_confirmation" id="pw2" placeholder="Repeat your password" required autocomplete="new-password" class="{{ $defaultInput }} pr-12">
                                        <button type="button" onclick="togglePw('pw2', this)" class="absolute inset-y-0 right-0 flex items-center pr-4 text-sm text-slate-400 transition hover:text-slate-300" aria-label="Toggle password visibility">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-600 bg-slate-800 px-4 py-3 text-xs leading-5 text-slate-300">
                                Your email will be used for sign in and account updates. Clients must be at least 18 years old to register.
                            </div>

                            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-primary-600 py-3 text-sm font-semibold text-white shadow-md transition hover:bg-primary-700">
                                <i class="fas fa-user-plus"></i>
                                <span>Create Account</span>
                            </button>

                            <div class="pt-2 text-center text-sm text-slate-600">
                                Already have an account?
                                <a href="{{ route('login') }}" class="font-semibold text-primary-600 hover:underline">Sign in here</a>
                            </div>
                        </form>
                    </div>

                    <div class="mt-4 text-center">
                        <a href="{{ url('/') }}" class="inline-flex items-center gap-1 text-sm text-white/80 transition hover:text-white">
                            <i class="fas fa-arrow-left text-xs"></i>
                            <span>Back to Home</span>
                        </a>
                    </div>
                </div>
            </section>
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
