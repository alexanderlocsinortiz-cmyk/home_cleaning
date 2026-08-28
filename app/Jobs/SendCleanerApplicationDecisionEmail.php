<?php

namespace App\Jobs;

use App\Mail\CleanerApplicationDecision;
use App\Models\CleanerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendCleanerApplicationDecisionEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public int $applicationId,
        public ?string $activationToken = null,
    ) {
        $this->queue = 'emails';
        $this->backoff = [10, 30, 60];
    }

    public function handle(): void
    {
        $application = CleanerApplication::find($this->applicationId);

        if (! $application || ! $application->email) {
            return;
        }

        Mail::to($application->email)->send(new CleanerApplicationDecision($application, $this->activationToken));
    }
}
