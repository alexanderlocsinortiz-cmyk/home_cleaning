<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_ATTEMPT_DECAY_SECONDS = 3600;
    private const LOGIN_LOCKOUT_ROUND_DECAY_SECONDS = 86400;
    private const LOGIN_BASE_LOCKOUT_SECONDS = 60;

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $minimumBirthDate = now()->subYears(18)->toDateString();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'min:2'],
            'last_name' => ['required', 'string', 'min:2'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['required', 'regex:/^[0-9]{11}$/'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:'.$minimumBirthDate],
            'password' => ['required', 'confirmed', 'min:8'],
        ], [
            'date_of_birth.before_or_equal' => 'Clients must be at least 18 years old to register.',
            'phone.regex' => 'Phone number must contain exactly 11 digits.',
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'date_of_birth' => $validated['date_of_birth'],
            'role' => 'client',
            'password' => Hash::make($validated['password']),
        ]);

        // ✅ Proper email error handling
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            // Network/SMTP transport error - temporary issue
            Log::warning('Email transport failed during registration', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->withErrors([
                'email' => 'Email system temporarily unavailable. Please try again in a moment.',
            ]);

        } catch (\Exception $e) {
            // Unknown error
            Log::error('Unexpected error during email send', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()->withInput()->withErrors([
                'system' => 'An error occurred. Please try again or contact support.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('verification.notice')
            ->with('success', 'Registration successful. Enter the verification code sent to your email.');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function showVerifyEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->redirectByRole($request->user());
        }

        return view('auth.verify-email', [
            'codeExpiresInMinutes' => (int) config('auth.verification.expire', 15),
        ]);
    }

    public function verifyEmail(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->redirectByRole($user);
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        if ($user->email_verification_code === null || $user->email_verification_code_expires_at === null) {
            return back()->withErrors([
                'code' => 'No verification code is active right now. Request a new code to continue.',
            ]);
        }

        if ($user->emailVerificationCodeExpired()) {
            return back()->withErrors([
                'code' => 'The verification code has expired. Request a new code to continue.',
            ]);
        }

        if (! $user->hasMatchingEmailVerificationCode($validated['code'])) {
            return back()
                ->withErrors(['code' => 'The verification code is invalid. Please try again.'])
                ->onlyInput('code');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        $user->clearEmailVerificationCode();

        return $this->redirectByRole($user)->with('success', 'Email verified successfully!');
    }

    public function sendVerificationCode(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->redirectByRole($user);
        }

        // ✅ Error handling for verification email
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Exception $e) {
            Log::error('Failed to send verification code', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'email' => 'Failed to send verification code. Please try again.',
            ]);
        }

        return back()->with('success', 'Verification code sent!');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $this->ensureLoginIsNotLocked($request);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $this->clearLoginRateLimit($request);
            $request->session()->regenerate();
            $user = Auth::user();

            if ($user->hasActiveAccessRestriction()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'This account is restricted until '.$user->access_restricted_until->format('M d, Y h:i A').'.',
                ])->onlyInput('email');
            }

            if ($user->role === 'client' && ! $user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice')->with('success', 'Please verify your email before continuing.');
            }

            return $this->redirectByRole($user)->with('success', 'Welcome back, '.$user->first_name.'.');
        }

        $this->recordFailedLoginAttempt($request);

        return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have been logged out successfully.');
    }

    private function redirectByRole(User $user)
    {
        return redirect()->route(match ($user->role) {
            'admin' => 'admin.dashboard',
            'staff' => 'staff.dashboard',
            default => 'client.dashboard',
        });
    }

    private function ensureLoginIsNotLocked(Request $request): void
    {
        $key = $this->loginLockoutKey($request);

        if (! RateLimiter::tooManyAttempts($key, 1)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);
        $this->flashLoginLockout($request, $seconds);

        throw ValidationException::withMessages([
            'email' => $this->loginLockoutMessage($seconds),
        ]);
    }

    private function recordFailedLoginAttempt(Request $request): void
    {
        $attempts = RateLimiter::hit($this->loginAttemptKey($request), self::LOGIN_ATTEMPT_DECAY_SECONDS);

        if ($attempts < self::LOGIN_MAX_ATTEMPTS) {
            return;
        }

        RateLimiter::clear($this->loginAttemptKey($request));

        $lockoutRound = RateLimiter::hit(
            $this->loginLockoutRoundKey($request),
            self::LOGIN_LOCKOUT_ROUND_DECAY_SECONDS
        );
        $seconds = $lockoutRound === 1
            ? self::LOGIN_BASE_LOCKOUT_SECONDS
            : ($lockoutRound + 1) * self::LOGIN_BASE_LOCKOUT_SECONDS;

        RateLimiter::hit($this->loginLockoutKey($request), $seconds);
        $this->flashLoginLockout($request, $seconds);

        throw ValidationException::withMessages([
            'email' => $this->loginLockoutMessage($seconds),
        ]);
    }

    private function flashLoginLockout(Request $request, int $seconds): void
    {
        $request->session()->flash('login_lockout', [
            'email' => $this->normalizedLoginEmail($request),
            'ends_at' => now()->addSeconds($seconds)->timestamp,
        ]);
    }

    private function clearLoginRateLimit(Request $request): void
    {
        RateLimiter::clear($this->loginAttemptKey($request));
        RateLimiter::clear($this->loginLockoutKey($request));
        RateLimiter::clear($this->loginLockoutRoundKey($request));
    }

    private function loginAttemptKey(Request $request): string
    {
        return 'login-attempts:'.$this->normalizedLoginEmail($request).'|'.$request->ip();
    }

    private function loginLockoutKey(Request $request): string
    {
        return 'login-lockout:'.$this->normalizedLoginEmail($request).'|'.$request->ip();
    }

    private function loginLockoutRoundKey(Request $request): string
    {
        return 'login-lockout-rounds:'.$this->normalizedLoginEmail($request).'|'.$request->ip();
    }

    private function normalizedLoginEmail(Request $request): string
    {
        return strtolower((string) $request->input('email'));
    }

    private function loginLockoutMessage(int $seconds): string
    {
        $minutes = max(1, (int) ceil($seconds / 60));

        return 'Too many incorrect login attempts. Please try again in '
            .$minutes.' minute'.($minutes === 1 ? '' : 's').'.';
    }
}
