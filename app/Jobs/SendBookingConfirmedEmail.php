<?php

namespace App\Jobs;

use App\Mail\BookingConfirmed;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBookingConfirmedEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $bookingId
    ) {
        $this->queue = 'emails';
        $this->tries = 3;
        $this->timeout = 60;
        $this->backoff = [10, 30, 60];  // Exponential backoff: 10s, 30s, 60s
    }

    public function handle(): void
    {
        try {
            $booking = Booking::with(['user', 'staff', 'service', 'preferredStaff'])->find($this->bookingId);

            if (!$booking) {
                Log::warning('Booking not found for SendBookingConfirmedEmail', ['booking_id' => $this->bookingId]);
                return;
            }

            Mail::to($booking->user->email)
                ->send(new BookingConfirmed($booking));

            Log::info('Booking confirmed email sent', [
                'booking_id' => $booking->id,
                'user_email' => $booking->user->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Error sending booking confirmed email', [
                'booking_id' => $this->bookingId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;  // Rethrow to trigger retry mechanism
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed job: SendBookingConfirmedEmail after max retries', [
            'booking_id' => $this->bookingId,
            'error' => $exception->getMessage(),
        ]);

        // Optional: Send alert to admin
        // Notification::send(Admin::all(), new FailedJobAlert($exception));
    }
}
