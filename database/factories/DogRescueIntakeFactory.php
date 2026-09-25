<?php

namespace Database\Factories;

use App\Models\Dog;
use App\Models\DogRescueIntake;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DogRescueIntake>
 */
class DogRescueIntakeFactory extends Factory
{
    protected $model = DogRescueIntake::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $received = fake()->dateTimeBetween('-2 years', '-1 month');

        return [
            'dog_id' => Dog::factory(),
            'date_received' => $received,
            'rescue_location' => fake()->randomElement(['Sharjah Industrial Area', 'Ras Al Khaimah farm', 'Dubai streets', 'Al Ain desert']),
            'how_found' => fake()->sentence(10),
            'initial_condition' => fake()->randomElement(['Underweight, dehydrated, skin issues.', 'Thin and confused.', 'Healthy but abandoned.']),
            'source_of_intake' => fake()->randomElement(['Public rescue call', 'Surrendered by owner', 'Found stray']),
            'intake_date' => (clone $received)->modify('+1 day'),
            'intake_notes' => 'Received initial vet check and treatment. In good health.',
            'rescue_story' => fake()->paragraph(),
        ];
    }
}
