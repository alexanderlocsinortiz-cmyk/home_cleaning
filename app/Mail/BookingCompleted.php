<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingCompleted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Service Completed - Home Cleaning Service #CF-' . str_pad($this->booking->id, 5, '0', STR_PAD_LEFT));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking-completed');
    }
}
