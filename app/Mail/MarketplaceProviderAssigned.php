<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\CleanerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MarketplaceProviderAssigned extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public CleanerApplication $provider,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'CleanFlow Booking Assignment - CF-'.str_pad($this->booking->id, 5, '0', STR_PAD_LEFT));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.marketplace-provider-assigned');
    }
}
