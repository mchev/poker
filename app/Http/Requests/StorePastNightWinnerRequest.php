<?php

namespace App\Http\Requests;

use App\Enums\Availability;
use App\Models\ProposedDate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StorePastNightWinnerRequest extends PokerFormRequest
{
    public function authorize(): bool
    {
        return $this->participant() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var ProposedDate $proposedDate */
        $proposedDate = $this->route('proposedDate');

        return [
            'winner_participant_id' => [
                'nullable',
                'integer',
                Rule::exists('participants', 'id'),
                Rule::in(
                    $proposedDate->votes()
                        ->where('availability', Availability::Yes)
                        ->pluck('participant_id')
                        ->all(),
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'winner_participant_id.in' => 'Le gagnant doit faire partie des participants présents.',
        ];
    }
}
