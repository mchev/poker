<?php

namespace App\Services;

use App\Models\Participant;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoContactService
{
    public function isConfigured(): bool
    {
        return filled(config('brevo.api_key')) && config('brevo.list_id') > 0;
    }

    public function syncParticipant(Participant $participant): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        try {
            Http::withHeaders([
                'api-key' => config('brevo.api_key'),
                'accept' => 'application/json',
            ])
                ->post(config('brevo.base_url').'/contacts', [
                    'email' => $participant->email,
                    'attributes' => [
                        'FNAME' => $participant->name,
                    ],
                    'listIds' => [config('brevo.list_id')],
                    'updateEnabled' => true,
                ])
                ->throw();
        } catch (RequestException|ConnectionException $exception) {
            $status = $exception instanceof RequestException
                ? $exception->response?->status()
                : null;

            $body = $exception instanceof RequestException
                ? $exception->response?->json()
                : null;

            Log::warning('Brevo contact sync failed.', [
                'participant_id' => $participant->id,
                'email' => $participant->email,
                'status' => $status,
                'body' => $body,
            ]);
        }
    }
}
