<?php

namespace App\Jobs;

use App\Mail\QuickNotification;
use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBookingReminderEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $notificationId)
    {
        $this->queue = 'emails';
        $this->tries = 3;
        $this->timeout = 60;
        $this->backoff = [10, 30, 60];
    }

    public function handle(): void
    {
        $notification = Notification::with('recipient')->find($this->notificationId);

        if (! $notification || $notification->sent_at || ! $notification->recipient?->email) {
            return;
        }

        Mail::to($notification->recipient->email)->send(new QuickNotification($notification));

        $notification->forceFill(['sent_at' => now()])->save();
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Failed job: SendBookingReminderEmail after max retries', [
            'notification_id' => $this->notificationId,
            'error' => $exception->getMessage(),
        ]);
    }
}
