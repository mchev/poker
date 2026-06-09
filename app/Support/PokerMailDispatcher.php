<?php

namespace App\Support;

use App\Models\Participant;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

final class PokerMailDispatcher
{
    public static function queue(string $email, Mailable $mailable, bool $redirectInLocal = true): void
    {
        $pending = Mail::to(self::resolveRecipient($email, $redirectInLocal));

        if (self::shouldSendSynchronously()) {
            $pending->sendNow($mailable);

            return;
        }

        $pending->queue($mailable);
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
}
