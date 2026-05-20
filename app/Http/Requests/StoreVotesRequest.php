<?php

namespace App\Http\Requests;

use App\Enums\Availability;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StoreVotesRequest extends PokerFormRequest
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
            'votes' => ['required', 'array'],
            'votes.*' => ['required', Rule::enum(Availability::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'votes.required' => 'Choisis au moins une réponse.',
        ];
    }
}
