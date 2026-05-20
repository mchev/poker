<?php

namespace App\Http\Requests;

class ResendAccessLinkRequest extends PokerFormRequest
{
    public function authorize(): bool
    {
        return $this->participant() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
