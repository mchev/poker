<?php

namespace App\Http\Requests;

use App\Enums\Availability;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends PokerFormRequest
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
            'attending' => ['required', Rule::in([Availability::Yes->value, Availability::No->value])],
        ];
    }
}
