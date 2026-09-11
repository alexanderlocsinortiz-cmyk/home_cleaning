<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MobileBookingLocationController extends Controller
{
    public function current(Request $request, Booking $booking): JsonResponse
    {
        $user = $request->user();
        $allowed = $user->role === 'admin'
            || ($user->role === 'client' && (int) $booking->user_id === (int) $user->id)
            || ($user->role === 'staff' && $booking->isAssignedToStaff((int) $user->id));
        abort_unless($allowed, 403);

        if ($user->role === 'client' && ! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Please verify your email before viewing booking location.',
                'requires_email_verification' => true,
            ], 403);
        }

        if (in_array($booking->status, ['pending', 'completed', 'cancelled'], true)) {
            return response()->json(['tracking' => false]);
        }

        $hasCompleteLocation = $booking->current_latitude !== null
            && $booking->current_longitude !== null;

        if (! $hasCompleteLocation) {
            return response()->json(['tracking' => false]);
        }

        return response()->json([
            'tracking' => true,
            'latitude' => $booking->current_latitude,
            'longitude' => $booking->current_longitude,
            'updated_at' => $booking->location_updated_at?->toISOString(),
        ]);
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        abort_unless($request->user()->role === 'staff' && $booking->isAssignedToStaff((int) $request->user()->id), 403);
        if (! in_array($booking->status, ['confirmed', 'in_progress'], true)) {
            return response()->json(['message' => 'Location sharing is only allowed during an active booking.'], 422);
        }

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        try {
            DB::transaction(function () use ($booking, $validated, $request): void {
                $capturedAt = now();

                $booking->update([
                    'current_latitude' => $validated['latitude'],
                    'current_longitude' => $validated['longitude'],
                    'location_updated_at' => $capturedAt,
                ]);
                BookingLocation::create([
                    'booking_id' => $booking->id,
                    'staff_id' => $request->user()->id,
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                    'captured_at' => $capturedAt,
                ]);
            });
        } catch (Throwable $exception) {
            Log::error('Mobile booking live location update failed.', [
                'booking_id' => $booking->id,
                'staff_id' => $request->user()->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'We could not save your location right now. Please retry in a few seconds.',
            ], 503);
        }

        return response()->json(['success' => true]);
    }
}
