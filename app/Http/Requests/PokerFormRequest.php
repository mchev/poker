<?php

namespace App\Http\Requests;

use App\Models\Participant;
use Illuminate\Foundation\Http\FormRequest;

abstract class PokerFormRequest extends FormRequest
{
    public function participant(): ?Participant
    {
        /** @var Participant|null $participant */
        $participant = $this->attributes->get('poker_participant');

        return $participant;
    }
}
