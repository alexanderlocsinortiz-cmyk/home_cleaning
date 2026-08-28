<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileApiToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class MobileAuthController extends Controller
{
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_DECAY_SECONDS = 900;
    private const TOKEN_EXPIRY_DAYS = 60;

    public function register(Request $request): JsonResponse
    {
        $minimumBirthDate = now()->subYears(18)->toDateString();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'min:2', 'max:255'],
            'last_name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'regex:/^[0-9]{11}$/'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:'.$minimumBirthDate],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'date_of_birth.before_or_equal' => 'Clients must be at least 18 years old to register.',
            'phone.regex' => 'Phone number must contain exactly 11 digits.',
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => strtolower($validated['email']),
            'phone' => $validated['phone'],
            'date_of_birth' => $validated['date_of_birth'],
            'role' => 'client',
            'password' => Hash::make($validated['password']),
        ]);

        $verificationNotice = null;

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            Log::warning('Mobile registration verification email failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            $verificationNotice = 'Account created, but the verification email could not be sent right now.';
        }

        return response()->json([
            'message' => 'Registration successful.',
            'token' => $this->createTokenFor($user),
            'token_type' => 'Bearer',
            'expires_in_days' => self::TOKEN_EXPIRY_DAYS,
            'requires_email_verification' => ! $user->hasVerifiedEmail(),
            'verification_notice' => $verificationNotice,
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        $email = strtolower($validated['email']);
        $rateLimitKey = 'mobile-login:'.$email.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, self::LOGIN_MAX_ATTEMPTS)) {
            return response()->json([
                'message' => 'Too many login attempts. Please try again later.',
                'retry_after_seconds' => RateLimiter::availableIn($rateLimitKey),
            ], 429);
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($rateLimitKey, self::LOGIN_DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => ['Invalid email or password.'],
            ]);
        }

        RateLimiter::clear($rateLimitKey);

        if ($user->hasActiveAccessRestriction()) {
            return response()->json([
                'message' => 'This account is temporarily restricted.',
                'restricted_until' => $user->access_restricted_until?->toISOString(),
                'reason' => $user->access_restriction_reason,
            ], 403);
        }

        return response()->json([
            'message' => 'Login successful.',
            'token' => $this->createTokenFor($user, $validated['device_name'] ?? 'mobile'),
            'token_type' => 'Bearer',
            'expires_in_days' => self::TOKEN_EXPIRY_DAYS,
            'requires_email_verification' => $user->role === 'client' && ! $user->hasVerifiedEmail(),
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('mobile_api_token')?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    private function createTokenFor(User $user, string $name = 'mobile'): string
    {
        $plainToken = Str::random(64);

        $token = MobileApiToken::create([
            'user_id' => $user->id,
            'name' => $name,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(self::TOKEN_EXPIRY_DAYS),
        ]);

        return $token->id.'|'.$plainToken;
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'date_of_birth' => $user->date_of_birth?->toDateString(),
            'barangay' => $user->barangay,
            'city' => $user->city,
            'email_verified' => $user->hasVerifiedEmail(),
        ];
    }
}
