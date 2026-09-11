<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\DailyVideoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class MobileBookingLiveController extends Controller
{
    public function video(Request $request, Booking $booking, DailyVideoService $dailyVideo): JsonResponse
    {
        $user = $request->user();
        $booking->loadMissing(['user', 'staff', 'service']);

        abort_unless($booking->canAccessLiveVideo($user), 403);

        if ($user->role === 'client' && ! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Please verify your email before using live video.',
                'requires_email_verification' => true,
            ], 403);
        }

        try {
            if (in_array($user->role, ['staff', 'admin'], true)) {
                $booking = $dailyVideo->ensureRoomForBooking($booking);
            }

            if (! $booking->dailyRoomIsActive()) {
                return response()->json(['message' => 'The cleaner has not started the live video room yet.'], 409);
            }

            return response()->json([
                'room_url' => $booking->daily_room_url,
                'meeting_token' => $dailyVideo->createMeetingToken($booking, $user),
                'expires_at' => $booking->daily_room_expires_at?->toISOString(),
            ]);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }
    }

    public function end(Request $request, Booking $booking, DailyVideoService $dailyVideo): JsonResponse
    {
        abort_unless($booking->canManageLiveVideo($request->user()), 403);

        try {
            $dailyVideo->endRoomForBooking($booking);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }

        return response()->json(['message' => 'Live video has been ended.']);
    }
}
