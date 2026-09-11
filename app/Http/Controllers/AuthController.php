<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\ResetPasswordOtp;
use App\Support\StrongPassword;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class AuthController extends Controller
{
    private const LOGIN_MAX_ATTEMPTS = 5;

    private const LOGIN_ATTEMPT_DECAY_SECONDS = 86400;

    private const LOGIN_BASE_LOCKOUT_SECONDS = 60;

    private const LOGIN_ESCALATED_LOCKOUT_STEP_SECONDS = 300;

    private const LOGIN_MAX_LOCKOUT_SECONDS = 3600;

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $minimumBirthDate = now(config('cleanflow.attendance_timezone', config('app.timezone')))
            ->subYears(18)
            ->toDateString();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'min:2', 'max:100'],
            'last_name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'regex:/^09[0-9]{9}$/'],
            'date_of_birth' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$minimumBirthDate],
            'password' => ['required', 'confirmed', StrongPassword::rule()],
        ], [
            'date_of_birth.before_or_equal' => 'Clients must be at least 18 years old to register.',
            'date_of_birth.date_format' => 'Date of birth must use YYYY-MM-DD format.',
            'phone.regex' => 'Phone number must start with 09 and contain exactly 11 digits.',
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
        } catch (TransportExceptionInterface $e) {
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

    public function showForgotPassword()
    {
        return view('auth.forgot-password', [
            'codeExpiresInMinutes' => $this->passwordResetCodeExpiresInMinutes(),
        ]);
    }

    public function sendPasswordResetCode(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower($validated['email']);
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user) {
            try {
                $this->issuePasswordResetCode($user);
            } catch (\Exception $e) {
                Log::error('Failed to send password reset code', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);

                return back()
                    ->withInput()
                    ->withErrors(['email' => 'Failed to send reset code. Please try again.']);
            }
        }

        $request->session()->put('password_reset_email', $email);

        return redirect()
            ->route('password.reset.verify')
            ->with('success', 'If that email exists, a password reset code was sent.');
    }

    public function resendPasswordResetCode(Request $request)
    {
        $email = strtolower((string) $request->session()->get('password_reset_email'));

        if ($email === '') {
            return redirect()->route('password.request');
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user) {
            try {
                $this->issuePasswordResetCode($user);
            } catch (\Exception $e) {
                Log::error('Failed to resend password reset code', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);

                return redirect()
                    ->route('password.reset.verify')
                    ->withErrors(['code' => 'Failed to send reset code. Please try again.']);
            }
        }

        return redirect()
            ->route('password.reset.verify')
            ->with('success', 'If that email exists, a new password reset code was sent.');
    }

    public function showResetPassword(Request $request)
    {
        if (! $request->session()->has('password_reset_email')) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password', [
            'email' => $request->session()->get('password_reset_email'),
            'codeExpiresInMinutes' => $this->passwordResetCodeExpiresInMinutes(),
        ]);
    }

    public function resetPasswordWithCode(Request $request)
    {
        $sessionEmail = $request->session()->get('password_reset_email');

        if (! $sessionEmail) {
            return redirect()->route('password.request');
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', StrongPassword::rule()],
        ]);

        $user = User::whereRaw('LOWER(email) = ?', [strtolower($sessionEmail)])->first();
        $reset = DB::table('password_reset_tokens')->where('email', $user?->email ?? $sessionEmail)->first();

        if (! $user || ! $reset) {
            return back()
                ->withErrors(['code' => 'No active reset code was found. Request a new code to continue.'])
                ->onlyInput('code');
        }

        $createdAt = $reset->created_at ? Carbon::parse($reset->created_at) : null;

        if (! $createdAt || $createdAt->addMinutes($this->passwordResetCodeExpiresInMinutes())->isPast()) {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();

            return back()
                ->withErrors(['code' => 'The reset code has expired. Request a new code to continue.'])
                ->onlyInput('code');
        }

        if (! Hash::check($validated['code'], $reset->token)) {
            return back()
                ->withErrors(['code' => 'The reset code is invalid. Please try again.'])
                ->onlyInput('code');
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => null,
        ])->save();

        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        $request->session()->forget('password_reset_email');

        return redirect()
            ->route('login')
            ->with('success', 'Password reset successful. You can now sign in.');
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
                    'email' => 'This account is restricted until '.$user->access_restricted_until->copy()->timezone(config('cleanflow.attendance_timezone', 'Asia/Manila'))->format('M d, Y h:i A').'.',
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
            'provider' => 'provider.dashboard',
            default => 'client.dashboard',
        });
    }

    private function issuePasswordResetCode(User $user): void
    {
        $expiresInMinutes = $this->passwordResetCodeExpiresInMinutes();
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($code),
                'created_at' => now(),
            ]
        );

        $user->notify(new ResetPasswordOtp($code, $expiresInMinutes));
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

        $seconds = $this->loginLockoutSecondsForAttempt($attempts);
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
    }

    private function loginAttemptKey(Request $request): string
    {
        return 'login-attempts:'.$this->normalizedLoginEmail($request).'|'.$request->ip();
    }

    private function loginLockoutKey(Request $request): string
    {
        return 'login-lockout:'.$this->normalizedLoginEmail($request).'|'.$request->ip();
    }

    private function normalizedLoginEmail(Request $request): string
    {
        return strtolower((string) $request->input('email'));
    }

    private function loginLockoutSecondsForAttempt(int $attempts): int
    {
        if ($attempts <= self::LOGIN_MAX_ATTEMPTS) {
            return self::LOGIN_BASE_LOCKOUT_SECONDS;
        }

        return min(
            self::LOGIN_MAX_LOCKOUT_SECONDS,
            ($attempts - self::LOGIN_MAX_ATTEMPTS) * self::LOGIN_ESCALATED_LOCKOUT_STEP_SECONDS
        );
    }

    private function loginLockoutMessage(int $seconds): string
    {
        $minutes = max(1, (int) ceil($seconds / 60));

        return 'Too many incorrect login attempts. Please try again in '
            .$minutes.' minute'.($minutes === 1 ? '' : 's').'.';
    }

    private function passwordResetCodeExpiresInMinutes(): int
    {
        return max(1, (int) config('auth.passwords.users.expire', 60));
    }
}
