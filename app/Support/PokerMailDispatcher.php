<?php

namespace App\Support;

use App\Models\Participant;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class PokerMailDispatcher
{
    public static function queue(string $email, Mailable $mailable, bool $redirectInLocal = true): void
    {
        $recipient = self::resolveRecipient($email, $redirectInLocal);
        $context = self::logContext($email, $recipient, $mailable);

        try {
            $pending = Mail::to($recipient);

            if (self::shouldSendSynchronously()) {
                $pending->sendNow($mailable);
                Log::info('Poker mail sent synchronously.', $context);

                return;
            }

            $pending->queue($mailable);
            Log::info('Poker mail queued.', $context);
        } catch (Throwable $exception) {
            Log::error('Poker mail dispatch failed.', [
                ...$context,
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public static function queueToParticipant(Participant $participant, Mailable $mailable): void
    {
        self::queue($participant->email, $mailable);
    }

    public static function resolveRecipient(string $email, bool $redirectInLocal = true): string
    {
        if ($redirectInLocal && self::shouldRedirectInLocal()) {
            return config('poker.local_mail_redirect');
        }

        return $email;
    }

    public static function shouldRedirectInLocal(): bool
    {
        return config('poker.redirect_mail_in_local')
            && app()->environment('local');
    }

    public static function shouldSendSynchronously(): bool
    {
        return app()->environment('local');
    }

    /**
     * @return array<string, mixed>
     */
    private static function logContext(string $intendedEmail, string $recipient, Mailable $mailable): array
    {
        return [
            'mailable' => $mailable::class,
            'intended_recipient' => $intendedEmail,
            'recipient' => $recipient,
            'queue_connection' => config('queue.default'),
            'mail_mailer' => config('mail.default'),
        ];
    }
}
