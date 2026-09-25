<?php

namespace Database\Factories;

use App\Enums\PlacementType;
use App\Models\Dog;
use App\Models\DogFosterRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DogFosterRecord>
 */
class DogFosterRecordFactory extends Factory
{
    protected $model = DogFosterRecord::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'dog_id' => Dog::factory(),
            'foster_type' => PlacementType::Local,
            'foster_family_name' => fake()->lastName().' Family',
            'location' => fake()->randomElement(['Dubai', 'Abu Dhabi', 'Sharjah']),
            'start_date' => $start,
            'notes' => 'Currently in foster home.',
        ];
    }
}
