<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'min:2'],
            'last_name' => ['required', 'string', 'min:2'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'role' => 'client',
            'password' => Hash::make($validated['password']),
        ]);

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Exception $e) {
            // silently ignore mail failures
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

        $user->sendEmailVerificationNotification();

        return back()->with('success', 'Verification code sent!');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
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

            return $this->redirectByRole($user)->with('success', 'Welcome back, ' . $user->first_name . '.');
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
            'admin'  => 'admin.dashboard',
            'staff'  => 'staff.dashboard',
            default  => 'client.dashboard',
        });
    }
}
