<?php

namespace Database\Factories;

use App\Models\Dog;
use App\Models\DogMedicalProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DogMedicalProfile>
 */
class DogMedicalProfileFactory extends Factory
{
    protected $model = DogMedicalProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dog_id' => Dog::factory(),
            'spayed_neutered' => fake()->boolean(70),
            'spayed_neutered_date' => fake()->optional()->dateTimeBetween('-2 years', 'now'),
            'last_vet_check_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'veterinary_clinic' => fake()->company().' Veterinary Clinic',
            'medications' => 'None',
            'medical_notes' => 'Healthy, no current issues.',
        ];
    }
}
