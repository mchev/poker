<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

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
        ];
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
        ];
    }
}
