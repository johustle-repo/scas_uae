<?php

namespace Database\Factories;

use App\Enums\DocumentCategory;
use App\Models\Dog;
use App\Models\DogDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DogDocument>
 */
class DogDocumentFactory extends Factory
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
            'category' => fake()->randomElement(DocumentCategory::cases()),
            'title' => fake()->sentence(3),
            'file_path' => 'dog-documents/'.fake()->uuid().'.pdf',
            'original_name' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(10_000, 2_000_000),
        ];
    }
}
