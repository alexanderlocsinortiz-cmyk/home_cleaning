<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileApiToken;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Notifications\ResetPasswordOtp;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class MobilePasswordResetController extends Controller
{
    private const RESET_TOKEN_TTL_MINUTES = 10;

    public function requestCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower($validated['email']);
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user) {
            $expiresInMinutes = $this->codeExpiresInMinutes();
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($code),
                    'created_at' => now(),
                ]
            );

            try {
                $user->notify(new ResetPasswordOtp($code, $expiresInMinutes));
            } catch (\Throwable $exception) {
                Log::error('Failed to send mobile password reset code', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $exception->getMessage(),
                ]);

                return response()->json([
                    'message' => 'We could not send the reset code right now. Please try again.',
                ], 503);
            }
        }

        return response()->json([
            'expires_in_minutes' => $this->codeExpiresInMinutes(),
            'message' => 'If that email exists, a password reset code was sent.',
            'resend_after_seconds' => 45,
        ]);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
            'email' => ['required', 'email'],
        ]);

        $email = strtolower($validated['email']);
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        $reset = DB::table('password_reset_tokens')
            ->where('email', $user?->email ?? $email)
            ->first();

        if (! $user || ! $reset) {
            $this->invalidCode();
        }

        $createdAt = $reset->created_at ? Carbon::parse($reset->created_at) : null;

        if (! $createdAt || $createdAt->addMinutes($this->codeExpiresInMinutes())->isPast()) {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            $this->invalidCode('The reset code has expired. Request a new code to continue.');
        }

        if (! Hash::check($validated['code'], $reset->token)) {
            $this->invalidCode('The reset code is invalid. Please try again.');
        }

        $resetToken = bin2hex(random_bytes(32));
        Cache::put(
            $this->resetTokenKey($resetToken),
            ['email' => $user->email],
            now()->addMinutes(self::RESET_TOKEN_TTL_MINUTES)
        );

        return response()->json([
            'expires_in_seconds' => self::RESET_TOKEN_TTL_MINUTES * 60,
            'message' => 'Reset code verified.',
            'reset_token' => $resetToken,
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'reset_token' => ['required', 'string', 'size:64'],
        ]);

        $resetSession = Cache::get($this->resetTokenKey($validated['reset_token']));
        $email = is_array($resetSession) ? ($resetSession['email'] ?? null) : null;
        $user = $email ? User::where('email', $email)->first() : null;

        if (! $user) {
            throw ValidationException::withMessages([
                'reset_token' => ['The password reset session is invalid or expired.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => null,
        ])->save();

        // A password reset is a security boundary: invalidate every existing
        // mobile session so a previously stolen bearer token cannot survive it.
        MobileApiToken::where('user_id', $user->id)->delete();
        SecurityEvent::record('mobile_password_reset', $user);

        DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        Cache::forget($this->resetTokenKey($validated['reset_token']));

        return response()->json([
            'message' => 'Password reset successful. You can now sign in.',
        ]);
    }

    private function invalidCode(string $message = 'No active reset code was found. Request a new code to continue.'): never
    {
        throw ValidationException::withMessages([
            'code' => [$message],
        ]);
    }

    private function codeExpiresInMinutes(): int
    {
        return max(1, (int) config('auth.passwords.users.expire', 60));
    }

    private function resetTokenKey(string $token): string
    {
        return 'mobile-password-reset:'.$token;
    }
}
