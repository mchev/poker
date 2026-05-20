<?php

namespace Database\Factories;

use App\Enums\SchedulingRoundStatus;
use App\Models\SchedulingRound;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchedulingRound>
 */
class SchedulingRoundFactory extends Factory
{
    protected $model = SchedulingRound::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => SchedulingRoundStatus::Polling,
            'confirmed_proposed_date_id' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => [
            'status' => SchedulingRoundStatus::Confirmed,
        ]);
    }
}
