<?php

namespace Database\Factories;

use App\Enums\PlacementType;
use App\Models\Dog;
use App\Models\DogAdoptionRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DogAdoptionRecord>
 */
class DogAdoptionRecordFactory extends Factory
{
    protected $model = DogAdoptionRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dog_id' => Dog::factory(),
            'adoption_type' => PlacementType::Local,
            'adopter_name' => fake()->name(),
            'adopter_location' => fake()->randomElement(['Dubai, UAE', 'Abu Dhabi, UAE', 'Al Ain, UAE']),
            'adopter_contact' => fake()->phoneNumber(),
            'adoption_date' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
