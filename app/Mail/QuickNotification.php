<?php

namespace App\Mail;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuickNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Notification $notification) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->notification->subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.quick-notification', with: [
            'notification' => $this->notification,
            'subject' => $this->notification->subject,
            'notificationMessage' => $this->notification->message,
            'type' => $this->notification->type,
            'recipient_name' => $this->notification->recipient->first_name,
        ]);
    }
}
