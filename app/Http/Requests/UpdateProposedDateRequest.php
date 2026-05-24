<?php

namespace App\Http\Requests;

use App\Models\Participant;
use App\Models\ProposedDate;
use App\Services\PokerSchedulingService;
use App\Support\ProposedDateLocation;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateProposedDateRequest extends PokerFormRequest
{
    public function authorize(): bool
    {
        $participant = $this->participant();

        if ($participant === null) {
            return false;
        }

        if (! $this->has('location_type') && ! $this->has('note')) {
            return false;
        }

        $proposedDate = $this->route('proposedDate');

        if (! $proposedDate instanceof ProposedDate) {
            return false;
        }

        $scheduling = app(PokerSchedulingService::class);

        if (! $scheduling->canParticipantEditProposedDate($participant, $proposedDate)) {
            return false;
        }

        if ($this->has('note') && ! $scheduling->canParticipantEditNote($participant, $proposedDate)) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [];

        if ($this->has('location_type')) {
            $rules = array_merge($rules, ProposedDateLocation::rules(required: true));
        }

        if ($this->has('note')) {
            $rules['note'] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    public function locationLabel(): string
    {
        /** @var Participant $participant */
        $participant = $this->participant();

        return ProposedDateLocation::label(
            $this->validated('location_type'),
            $participant,
            $this->validated('location_participant_id'),
            $this->validated('location_custom'),
        );
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge(ProposedDateLocation::messages(), [
            'note.max' => 'La note doit faire moins de 500 caractères.',
        ]);
    }
}
