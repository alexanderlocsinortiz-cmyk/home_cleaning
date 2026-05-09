<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
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
            'phone' => ['required', 'string', 'max:30'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:'.$minimumBirthDate],
            'password' => ['required', 'confirmed', 'min:8'],
        ], [
            'date_of_birth.before_or_equal' => 'Clients must be at least 18 years old to register.',
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

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $user = Auth::user();

            if ($user->role === 'client' && ! $user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice')->with('success', 'Please verify your email before continuing.');
            }

            return $this->redirectByRole($user)->with('success', 'Welcome back, '.$user->first_name.'.');
        }

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
}
