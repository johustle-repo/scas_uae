<?php

namespace Database\Factories;

use App\Enums\ImportFlagType;
use App\Models\Dog;
use App\Models\DogImportFlag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DogImportFlag>
 */
class DogImportFlagFactory extends Factory
{
    protected $model = DogImportFlag::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dog_id' => Dog::factory(),
            'flag_type' => fake()->randomElement(ImportFlagType::cases()),
            'message' => fake()->sentence(),
            'created_at' => now(),
        ];
    }
}
