<?php

namespace Database\Factories;

use App\Enums\RegistrationStatus;
use App\Models\Dog;
use App\Models\DogIdentification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DogIdentification>
 */
class DogIdentificationFactory extends Factory
{
    protected $model = DogIdentification::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dog_id' => Dog::factory(),
            'microchip_number' => fake()->unique()->numerify('9002##########'),
            'microchip_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'microchip_registration_status' => RegistrationStatus::Registered,
            'pcc_number' => (string) fake()->unique()->numberBetween(30000, 39999),
            'pcc_registration_status' => RegistrationStatus::Registered,
        ];
    }
}
