<?php

namespace Database\Factories;

use App\Enums\AdoptionApplicationStatus;
use App\Enums\HomeType;
use App\Models\AdoptionApplication;
use App\Models\Dog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdoptionApplication>
 */
class AdoptionApplicationFactory extends Factory
{
    protected $model = AdoptionApplication::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dog_id' => Dog::factory()->atScas(),
            'full_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('+9715########'),
            'city' => fake()->randomElement(['Dubai', 'Abu Dhabi', 'Sharjah', 'Ras Al Khaimah']),
            'home_type' => fake()->randomElement(HomeType::cases()),
            'has_garden' => fake()->boolean(),
            'has_children' => fake()->boolean(),
            'other_pets' => fake()->optional()->sentence(),
            'experience' => fake()->optional()->sentence(),
            'message' => fake()->paragraph(),
            'status' => AdoptionApplicationStatus::New,
        ];
    }
}
