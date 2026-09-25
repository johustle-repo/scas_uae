<?php

namespace Database\Factories;

use App\Models\Dog;
use App\Models\DogVaccination;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DogVaccination>
 */
class DogVaccinationFactory extends Factory
{
    protected $model = DogVaccination::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $administered = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'dog_id' => Dog::factory(),
            'vaccine_details' => fake()->randomElement(['Nobivac Rabies', 'Nobivac DHPPi', 'Nobivac Lepto', 'Nobivac KC', 'Canine Distemper']),
            'administered_date' => $administered,
            'next_due_date' => (clone $administered)->modify('+1 year'),
        ];
    }
}
