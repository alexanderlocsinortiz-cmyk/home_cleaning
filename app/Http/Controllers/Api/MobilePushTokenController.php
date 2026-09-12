<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobilePushToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobilePushTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_name' => ['nullable', 'string', 'max:120'],
            'platform' => ['required', 'in:android,ios'],
            'token' => [
                'required',
                'string',
                'max:255',
                'regex:/^(Expo|Exponent)PushToken\[[A-Za-z0-9_-]+\]$/',
            ],
        ]);

        $pushToken = MobilePushToken::updateOrCreate(
            ['token' => $validated['token']],
            [
                'user_id' => $request->user()->id,
                'platform' => $validated['platform'],
                'device_name' => $validated['device_name'] ?? null,
                'last_used_at' => now(),
            ],
        );

        return response()->json([
            'message' => 'Push notifications enabled for this device.',
            'push_token_id' => $pushToken->id,
        ], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => [
                'required',
                'string',
                'max:255',
                'regex:/^(Expo|Exponent)PushToken\[[A-Za-z0-9_-]+\]$/',
            ],
        ]);

        $deleted = MobilePushToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token', $validated['token'])
            ->delete();

        return response()->json([
            'message' => 'Push notifications disabled for this device.',
            'deleted_count' => $deleted,
        ]);
    }
}
