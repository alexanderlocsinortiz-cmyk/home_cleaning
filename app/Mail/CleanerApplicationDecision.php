<?php

namespace App\Mail;

use App\Models\CleanerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CleanerApplicationDecision extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CleanerApplication $application,
        public ?string $activationToken = null,
        public ?string $trackingToken = null,
    ) {}

    public function envelope(): Envelope
    {
        $decision = $this->application->status === CleanerApplication::STATUS_APPROVED
            ? 'Approved'
            : 'Application Update';

        return new Envelope(subject: 'CleanFlow Service Provider Application - '.$decision);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.cleaner-application-decision', with: [
            'activationUrl' => $this->activationToken
                ? route('provider.activate.show', ['token' => $this->activationToken])
                : null,
            'trackingUrl' => $this->trackingToken
                ? route('cleaner-applications.status', ['token' => $this->trackingToken])
                : null,
        ]);
    }
}
