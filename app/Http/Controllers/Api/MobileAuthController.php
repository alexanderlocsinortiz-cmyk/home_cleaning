<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileApiToken;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Support\StrongPassword;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MobileAuthController extends Controller
{
    private const LOGIN_MAX_ATTEMPTS = 5;

    private const LOGIN_DECAY_SECONDS = 900;

    private const TOKEN_EXPIRY_DAYS = 60;

    public function register(Request $request): JsonResponse
    {
        $minimumBirthDate = now(config('cleanflow.attendance_timezone', config('app.timezone')))
            ->subYears(18)
            ->toDateString();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'min:2', 'max:255'],
            'last_name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'regex:/^[0-9]{11}$/'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:'.$minimumBirthDate],
            'password' => ['required', 'confirmed', StrongPassword::rule()],
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

        $token = $this->createTokenFor($user);
        SecurityEvent::record('mobile_registration', $user, [
            'email_verified' => $user->hasVerifiedEmail(),
        ]);

        return response()->json([
            'message' => 'Registration successful.',
            'token' => $token,
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

            SecurityEvent::record('mobile_login_failed', $user, [
                'identifier_hash' => hash('sha256', $email),
            ]);

            throw ValidationException::withMessages([
                'email' => ['Invalid email or password.'],
            ]);
        }

        RateLimiter::clear($rateLimitKey);

        if ($user->hasActiveAccessRestriction()) {
            SecurityEvent::record('mobile_login_blocked', $user, [
                'reason' => $user->access_restriction_reason,
            ]);

            return response()->json([
                'message' => 'This account is temporarily restricted.',
                'restricted_until' => $user->access_restricted_until?->toISOString(),
                'reason' => $user->access_restriction_reason,
            ], 403);
        }

        $token = $this->createTokenFor($user, $validated['device_name'] ?? 'mobile');
        SecurityEvent::record('mobile_login_succeeded', $user, [
            'device_name' => $validated['device_name'] ?? 'mobile',
        ]);

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
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

    public function sendVerificationCode(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email is already verified.',
                'email_verified' => true,
            ]);
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            Log::error('Mobile email verification code could not be sent.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'We could not send a verification code right now. Please try again.',
            ], 503);
        }

        return response()->json([
            'message' => 'A new verification code was sent to your email.',
            'email_verified' => false,
            'expires_in_minutes' => (int) config('auth.verification.expire', 15),
        ]);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email is already verified.',
                'email_verified' => true,
                'user' => $this->userPayload($user),
            ]);
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        if ($user->email_verification_code === null || $user->email_verification_code_expires_at === null) {
            throw ValidationException::withMessages([
                'code' => ['No verification code is active right now. Request a new code to continue.'],
            ]);
        }

        if ($user->emailVerificationCodeExpired()) {
            throw ValidationException::withMessages([
                'code' => ['The verification code has expired. Request a new code to continue.'],
            ]);
        }

        if (! $user->hasMatchingEmailVerificationCode($validated['code'])) {
            throw ValidationException::withMessages([
                'code' => ['The verification code is invalid. Please try again.'],
            ]);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        $user->clearEmailVerificationCode();
        $user->refresh();

        return response()->json([
            'message' => 'Email verified successfully.',
            'email_verified' => true,
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('mobile_api_token')?->delete();
        SecurityEvent::record('mobile_logout', $request->user());

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $revokedCount = MobileApiToken::where('user_id', $request->user()->id)->delete();
        SecurityEvent::record('mobile_logout_all', $request->user(), [
            'revoked_count' => $revokedCount,
        ]);

        return response()->json([
            'message' => 'All mobile devices have been logged out.',
            'revoked_count' => $revokedCount,
        ]);
    }

    private function createTokenFor(User $user, string $name = 'mobile'): string
    {
        return DB::transaction(function () use ($user, $name): string {
            // Lock the parent row so concurrent logins cannot both exceed the
            // per-user session ceiling when the user has few or no tokens.
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            MobileApiToken::query()
                ->where('user_id', $user->id)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->delete();

            $activeTokens = MobileApiToken::query()
                ->where('user_id', $user->id)
                ->orderByRaw('COALESCE(last_used_at, created_at) asc')
                ->orderBy('id')
                ->get();
            $maxActiveTokens = (int) config('auth.mobile.max_active_tokens', 5);
            $tokensToRemove = max(0, $activeTokens->count() - $maxActiveTokens + 1);

            $activeTokens->take($tokensToRemove)->each->delete();

            $plainToken = Str::random(64);
            $token = MobileApiToken::create([
                'user_id' => $user->id,
                'name' => $name,
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => now()->addDays(self::TOKEN_EXPIRY_DAYS),
            ]);

            return $token->id.'|'.$plainToken;
        });
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
