<?php

namespace App\Http\Requests;

use App\Models\Participant;
use App\Support\ProposedDateLocation;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreProposedDateRequest extends PokerFormRequest
{
    public function authorize(): bool
    {
        return $this->participant() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'beginners_welcome' => $this->boolean('beginners_welcome', true),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge([
            'date' => ['required', 'date', 'after:today'],
            'time' => ['required', 'date_format:H:i'],
            'theme' => ['nullable', 'string', 'max:80'],
            'beginners_welcome' => ['boolean'],
            'game_ids' => ['nullable', 'array'],
            'game_ids.*' => ['integer', 'exists:games,id'],
        ], ProposedDateLocation::rules());
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
        return array_merge([
            'date.required' => 'Choisis une date.',
            'date.after' => 'La date doit être dans le futur.',
            'time.required' => 'Indique une heure.',
            'time.date_format' => 'L\'heure doit être au format HH:MM.',
            'theme.max' => 'Le thème doit faire moins de 80 caractères.',
        ], ProposedDateLocation::messages());
    }
}
