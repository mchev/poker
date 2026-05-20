<?php

namespace Database\Factories;

use App\Enums\Availability;
use App\Models\Participant;
use App\Models\ProposedDate;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vote>
 */
class VoteFactory extends Factory
{
    protected $model = Vote::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'participant_id' => Participant::factory(),
            'proposed_date_id' => ProposedDate::factory(),
            'availability' => fake()->randomElement(Availability::cases()),
        ];
    }
}
