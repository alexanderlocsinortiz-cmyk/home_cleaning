<?php

namespace App\Jobs;

use App\Mail\BookingStaffAssigned;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBookingStaffAssignedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $bookingId
    ) {
        $this->queue = 'emails';
        $this->tries = 3;
        $this->timeout = 60;
        $this->backoff = [10, 30, 60];
    }

    public function handle(): void
    {
        try {
            $booking = Booking::with(['user', 'staff', 'service', 'preferredStaff'])->find($this->bookingId);

            if (!$booking) {
                Log::warning('Booking not found for SendBookingStaffAssignedEmail', ['booking_id' => $this->bookingId]);
                return;
            }

            Mail::to($booking->user->email)
                ->send(new BookingStaffAssigned($booking));

            Log::info('Booking staff assigned email sent', [
                'booking_id' => $booking->id,
                'user_email' => $booking->user->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending booking staff assigned email', [
                'booking_id' => $this->bookingId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed job: SendBookingStaffAssignedEmail after max retries', [
            'booking_id' => $this->bookingId,
            'error' => $exception->getMessage(),
        ]);
    }
}
