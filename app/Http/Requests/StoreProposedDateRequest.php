<?php

namespace App\Http\Requests;

use App\Models\Participant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StoreProposedDateRequest extends PokerFormRequest
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
        return [
            'date' => ['required', 'date', 'after:today'],
            'time' => ['required', 'date_format:H:i'],
            'location_type' => ['required', Rule::in(['mine', 'member', 'fabrique', 'custom'])],
            'location_participant_id' => ['nullable', 'required_if:location_type,member', 'integer', 'exists:participants,id'],
            'location_custom' => ['nullable', 'required_if:location_type,custom', 'string', 'max:80'],
            'theme' => ['nullable', 'string', 'max:80'],
        ];
    }

    public function locationLabel(): string
    {
        /** @var Participant $participant */
        $participant = $this->participant();

        return match ($this->validated('location_type')) {
            'mine' => 'Chez '.$participant->name,
            'member' => 'Chez '.Participant::query()
                ->whereKey($this->validated('location_participant_id'))
                ->value('name'),
            'fabrique' => 'La fabrique',
            'custom' => trim((string) $this->validated('location_custom')),
        };
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required' => 'Choisis une date.',
            'date.after' => 'La date doit être dans le futur.',
            'time.required' => 'Indique une heure.',
            'time.date_format' => 'L\'heure doit être au format HH:MM.',
            'location_type.required' => 'Choisis un lieu.',
            'location_type.in' => 'Choisis un lieu valide.',
            'location_participant_id.required_if' => 'Choisis chez qui on joue.',
            'location_participant_id.exists' => 'Ce membre n’existe pas.',
            'location_custom.required_if' => 'Indique le lieu.',
            'location_custom.max' => 'Le lieu doit faire moins de 80 caractères.',
            'theme.max' => 'Le thème doit faire moins de 80 caractères.',
        ];
    }
}
