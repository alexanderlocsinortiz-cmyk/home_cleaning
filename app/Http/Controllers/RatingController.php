<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Rating;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function store(Request $request, $bookingId)
    {
        $booking = Booking::with('rating')->findOrFail($bookingId);

        if ($booking->status !== 'completed') {
            return back()->with('error', 'You can only rate completed bookings.');
        }

        if ($booking->user_id !== auth()->id()) {
            abort(403);
        }

        if ($booking->rating) {
            return back()->with('error', 'You have already rated this booking.');
        }

        $request->validate([
            'stars'   => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        Rating::create([
            'booking_id' => $booking->id,
            'client_id'  => auth()->id(),
            'staff_id'   => $booking->staff_id,
            'stars'      => $request->stars,
            'comment'    => $request->comment,
        ]);

        return back()->with('success', 'Thank you for your feedback. Your rating has been submitted.');
    }
}
