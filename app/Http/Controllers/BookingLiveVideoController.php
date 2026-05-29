<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\DailyVideoService;
use Illuminate\Http\Request;
use RuntimeException;

class BookingLiveVideoController extends Controller
{
    public function show(Request $request, Booking $booking, DailyVideoService $dailyVideo)
    {
        $user = $request->user();
        abort_unless($user, 403);

        $booking->loadMissing(['user', 'staff', 'service']);

        if (! $booking->canUseLiveVideo()) {
            return redirect()
                ->route('bookings.show', $booking->id)
                ->with('info', 'Live video is available only while an assigned booking is in progress.');
        }

        if (! $booking->canAccessLiveVideo($user)) {
            abort(403);
        }

        try {
            if (in_array($user->role, ['admin', 'staff'], true)) {
                $booking = $dailyVideo->ensureRoomForBooking($booking);
            }

            if (! $booking->dailyRoomIsActive()) {
                return redirect()
                    ->route('bookings.show', $booking->id)
                    ->with('info', 'The cleaner has not started the live video room yet.');
            }

            $meetingToken = $dailyVideo->createMeetingToken($booking, $user);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('bookings.show', $booking->id)
                ->with('error', $exception->getMessage());
        }

        return view('bookings.live-video', [
            'booking' => $booking,
            'meetingToken' => $meetingToken,
            'roomUrl' => $booking->daily_room_url,
            'viewerName' => $user->display_name,
            'canBroadcast' => in_array($user->role, ['admin', 'staff'], true),
        ]);
    }

    public function end(Request $request, Booking $booking, DailyVideoService $dailyVideo)
    {
        $user = $request->user();
        abort_unless($user, 403);

        $booking->loadMissing(['user', 'staff', 'service']);

        if (! $booking->canManageLiveVideo($user)) {
            abort(403);
        }

        try {
            $dailyVideo->endRoomForBooking($booking);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('bookings.show', $booking->id)
            ->with('success', 'Live video has been ended for this booking.');
    }
}
