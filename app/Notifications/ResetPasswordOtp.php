<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordOtp extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public string $code,
        public int $expiresInMinutes = 60,
    ) {
        $this->queue = 'emails';
        $this->backoff = [10, 30, 60];
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Password Reset Code - Home Cleaning Service')
            ->view('emails.reset-password-otp', [
                'user' => $notifiable,
                'code' => $this->code,
                'expiresInMinutes' => $this->expiresInMinutes,
            ]);
    }
}
