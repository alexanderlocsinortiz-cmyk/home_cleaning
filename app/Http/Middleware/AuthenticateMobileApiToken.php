<?php

namespace App\Http\Middleware;

use App\Models\MobileApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobileApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = (string) $request->bearerToken();

        if ($plainToken === '') {
            return response()->json([
                'message' => 'Missing bearer token.',
            ], 401);
        }

        $token = $this->findToken($plainToken);

        if (! $token || $token->expired()) {
            return response()->json([
                'message' => 'Invalid or expired bearer token.',
            ], 401);
        }

        // Authentication can happen on every API request. Avoid turning
        // read-heavy mobile traffic into a database write on every call.
        if (! $token->last_used_at || $token->last_used_at->lte(now()->subMinutes(5))) {
            $token->forceFill(['last_used_at' => now()])->save();
        }
        $token->loadMissing('user');

        $user = $token->user;

        if (! $user) {
            return response()->json([
                'message' => 'This account is no longer available.',
            ], 401);
        }

        if ($user->role !== 'admin' && $user->hasActiveAccessRestriction()) {
            return response()->json([
                'message' => 'This account is temporarily restricted.',
                'restricted_until' => $user->access_restricted_until?->toISOString(),
                'reason' => $user->access_restriction_reason,
            ], 403);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);
        $request->attributes->set('mobile_api_token', $token);

        return $next($request);
    }

    private function findToken(string $plainToken): ?MobileApiToken
    {
        if (str_contains($plainToken, '|')) {
            [$id, $tokenValue] = explode('|', $plainToken, 2);

            if (ctype_digit($id) && $tokenValue !== '') {
                $token = MobileApiToken::find((int) $id);

                if ($token && hash_equals($token->token_hash, hash('sha256', $tokenValue))) {
                    return $token;
                }
            }
        }

        return MobileApiToken::where('token_hash', hash('sha256', $plainToken))->first();
    }
}
