<?php

namespace App\Support;

use App\Models\Participant;
use Illuminate\Validation\Rule;

class ProposedDateLocation
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(bool $required = true): array
    {
        $presence = $required ? 'required' : 'sometimes';

        return [
            'location_type' => [$presence, Rule::in(['mine', 'member', 'fabrique', 'custom'])],
            'location_participant_id' => ['nullable', 'required_if:location_type,member', 'integer', 'exists:participants,id'],
            'location_custom' => ['nullable', 'required_if:location_type,custom', 'string', 'max:80'],
        ];
    }

    public static function label(
        string $locationType,
        Participant $actingParticipant,
        ?int $hostParticipantId = null,
        ?string $custom = null,
    ): string {
        return match ($locationType) {
            'mine' => 'Chez '.$actingParticipant->name,
            'member' => 'Chez '.Participant::query()
                ->whereKey($hostParticipantId)
                ->value('name'),
            'fabrique' => 'La fabrique',
            'custom' => trim((string) $custom),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'location_type.required' => 'Choisis un lieu.',
            'location_type.in' => 'Choisis un lieu valide.',
            'location_participant_id.required_if' => 'Choisis chez qui on joue.',
            'location_participant_id.exists' => 'Ce membre n’existe pas.',
            'location_custom.required_if' => 'Indique le lieu.',
            'location_custom.max' => 'Le lieu doit faire moins de 80 caractères.',
        ];
    }
}
