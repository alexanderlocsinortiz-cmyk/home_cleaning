<?php

namespace App\Jobs;

use App\Mail\ProviderPayoutPaid;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendProviderPayoutPaidEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public int $bookingId)
    {
        $this->queue = 'emails';
        $this->backoff = [10, 30, 60];
    }

    public function handle(): void
    {
        $booking = Booking::with(['service', 'cleanerApplication'])->find($this->bookingId);
        $provider = $booking?->cleanerApplication;

        if (! $booking || ! $provider || ! $provider->email) {
            return;
        }

        Mail::to($provider->email)->send(new ProviderPayoutPaid($booking, $provider));
    }
}
