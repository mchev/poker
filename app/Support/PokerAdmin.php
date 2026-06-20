<?php

namespace App\Support;

use App\Models\Participant;

final class PokerAdmin
{
    /**
     * @return list<string>
     */
    public static function allowedEmails(): array
    {
        return config('horizon.allowed_emails', []);
    }

    public static function isAdmin(?Participant $participant): bool
    {
        return $participant instanceof Participant
            && in_array($participant->email, self::allowedEmails(), true);
    }
}
