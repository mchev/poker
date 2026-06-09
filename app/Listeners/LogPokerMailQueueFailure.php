<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;

class LogPokerMailQueueFailure
{
    public function handle(JobFailed $event): void
    {
        $displayName = $event->job->payload()['displayName'] ?? '';

        if (! str_starts_with($displayName, 'App\\Mail\\')) {
            return;
        }

        Log::error('Poker mail queue job failed.', [
            'job' => $displayName,
            'connection' => $event->connectionName,
            'queue' => $event->job->getQueue(),
            'exception' => $event->exception->getMessage(),
        ]);
    }
}
