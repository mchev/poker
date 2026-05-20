<?php

namespace Database\Factories;

use App\Models\Participant;
use App\Models\ProposedDate;
use App\Models\SchedulingRound;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProposedDate>
 */
class ProposedDateFactory extends Factory
{
    protected $model = ProposedDate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'scheduling_round_id' => SchedulingRound::factory(),
            'starts_at' => fake()->dateTimeBetween('+3 days', '+3 weeks'),
            'proposed_by_participant_id' => Participant::factory(),
        ];
    }
}
