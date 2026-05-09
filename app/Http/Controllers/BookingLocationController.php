<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingLocation;
use Illuminate\Http\Request;

class BookingLocationController extends Controller
{
    public function current($id)
    {
        $booking = Booking::findOrFail($id);
        $user = auth()->user();

        // Only allow admin, or the client who owns this booking
        if ($user->role === 'client' && $booking->user_id !== $user->id) {
            abort(403, 'You are not allowed to view this location.');
        }

        // Only allow staff who is assigned to this booking
        if ($user->role === 'staff' && $booking->staff_id !== $user->id) {
            abort(403, 'You are not allowed to view this location.');
        }

        // Block everyone if booking is completed or cancelled
        if (in_array($booking->status, ['completed', 'cancelled'])) {
            return response()->json(['tracking' => false, 'reason' => 'Booking has ended.']);
        }

        // Block client from seeing location if booking is still pending
        if ($user->role === 'client' && $booking->status === 'pending') {
            return response()->json(['tracking' => false, 'reason' => 'Booking not yet confirmed.']);
        }

        // No location shared yet
        if (! $booking->current_latitude || ! $booking->current_longitude) {
            return response()->json(['tracking' => false]);
        }

        return response()->json([
            'tracking' => true,
            'latitude' => $booking->current_latitude,
            'longitude' => $booking->current_longitude,
            'updated_at' => $booking->location_updated_at
                ? \Carbon\Carbon::parse($booking->location_updated_at)->diffForHumans()
                : null,
            'is_admin' => $user->role === 'admin',
        ]);
    }

    public function history($id)
    {
        $booking = Booking::findOrFail($id);
        $user = auth()->user();

        // Only admin can see location history
        if ($user->role !== 'admin') {
            abort(403, 'Only admins can view location history.');
        }

        $history = BookingLocation::where('booking_id', $id)
            ->orderBy('created_at', 'asc')
            ->get(['latitude', 'longitude', 'created_at']);

        return response()->json($history);
    }

    public function update(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);
        $user = auth()->user();

        // Only the assigned staff can update location
        if ($user->role !== 'staff') {
            abort(403, 'Only staff can share location.');
        }

        if ($booking->staff_id !== $user->id) {
            abort(403, 'You are not assigned to this booking.');
        }

        // Only allow location sharing during active booking
        if (! in_array($booking->status, ['confirmed', 'in_progress'])) {
            return response()->json(['error' => 'Location sharing is only allowed during active bookings.'], 422);
        }

        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $booking->update([
            'current_latitude' => $request->latitude,
            'current_longitude' => $request->longitude,
            'location_updated_at' => now(),
        ]);

        BookingLocation::create([
            'booking_id' => $id,
            'staff_id' => $user->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'captured_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }
}
