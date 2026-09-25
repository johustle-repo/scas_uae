<?php

namespace Database\Factories;

use App\Enums\PhotoKind;
use App\Models\Dog;
use App\Models\DogPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DogPhoto>
 */
class DogPhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dog_id' => Dog::factory(),
            'kind' => PhotoKind::Gallery,
            'file_path' => 'dog-photos/'.fake()->uuid().'.jpg',
            'caption' => fake()->optional()->sentence(4),
        ];
    }

    /**
     * A photo taken at rescue or intake.
     */
    public function rescue(): static
    {
        return $this->state(fn (array $attributes): array => [
            'kind' => PhotoKind::Rescue,
        ]);
    }
}
