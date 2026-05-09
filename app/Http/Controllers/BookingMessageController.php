<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class BookingMessageController extends Controller
{
    public function store(Request $request, Booking $booking)
    {
        $user = $request->user();

        if (! $this->canParticipate($booking, $user)) {
            abort(403);
        }

        if (! $booking->staff_id) {
            return back()->with('error', 'Messaging is available only after a cleaner has been assigned.');
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $message = $booking->messages()->create([
            'sender_id' => $user->id,
            'message' => trim(strip_tags($validated['message'])),
        ]);

        $recipientId = $user->id === (int) $booking->user_id
            ? $booking->staff_id
            : $booking->user_id;

        $this->createNotification([
            'user_id' => $recipientId,
            'booking_id' => $booking->id,
            'title' => 'New booking message',
            'message' => $user->display_name.' sent a message on booking CF-'.str_pad($booking->id, 5, '0', STR_PAD_LEFT).'.',
            'type' => 'info',
            'link' => route('bookings.show', $booking->id).'#booking-messages',
        ]);

        return back()->with('success', 'Message sent.');
    }

    private function canParticipate(Booking $booking, $user): bool
    {
        if (! $user || ! in_array($user->role, ['client', 'staff'], true)) {
            return false;
        }

        if ($user->role === 'client') {
            return (int) $booking->user_id === (int) $user->id;
        }

        return (int) $booking->staff_id === (int) $user->id;
    }
}
