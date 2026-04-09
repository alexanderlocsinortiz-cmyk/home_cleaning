<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingLocation;
use Illuminate\Http\Request;

class StaffLocationController extends Controller
{
    public function store(Request $request, Booking $booking)
    {
        $user = $request->user();

        if ($user->role !== 'staff' || $booking->staff_id !== $user->id) {
            abort(403);
        }

        if ($booking->status !== 'in_progress') {
            return response()->json([
                'ok' => false,
                'message' => 'Booking not in progress',
            ], 422);
        }

        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric'],
            'speed' => ['nullable', 'numeric'],
            'heading' => ['nullable', 'numeric'],
        ]);

        $capturedAt = now();

        BookingLocation::create([
            'booking_id' => $booking->id,
            'staff_id' => $user->id,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'accuracy' => $data['accuracy'] ?? null,
            'speed' => $data['speed'] ?? null,
            'heading' => $data['heading'] ?? null,
            'captured_at' => $capturedAt,
        ]);

        $booking->update([
            'current_latitude' => $data['latitude'],
            'current_longitude' => $data['longitude'],
            'location_updated_at' => $capturedAt,
        ]);

        return response()->json(['ok' => true]);
    }

    public function current(Request $request, Booking $booking)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            // Admin can view all live locations.
        } elseif ($user->role === 'client') {
            if ($booking->user_id !== $user->id) {
                abort(403);
            }

            if (! in_array($booking->status, ['confirmed', 'in_progress'], true)) {
                return response()->json(['tracking' => false]);
            }
        } else {
            abort(403);
        }

        if (is_null($booking->current_latitude) || is_null($booking->current_longitude)) {
            return response()->json(['tracking' => false]);
        }

        return response()->json([
            'tracking' => true,
            'latitude' => $booking->current_latitude,
            'longitude' => $booking->current_longitude,
            'updated_at' => $booking->location_updated_at?->diffForHumans(),
            'is_admin' => $user->role === 'admin',
        ]);
    }

    public function history(Booking $booking)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        $locations = $booking->locations()
            ->orderBy('captured_at')
            ->get(['latitude', 'longitude', 'captured_at']);

        return response()->json($locations);
    }
}
