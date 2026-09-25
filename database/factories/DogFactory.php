<?php

namespace Database\Factories;

use App\Enums\DogStatus;
use App\Enums\EnergyLevel;
use App\Enums\Gender;
use App\Enums\Size;
use App\Enums\Species;
use App\Enums\TrainingLevel;
use App\Models\Dog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dog>
 */
class DogFactory extends Factory
{
    protected $model = Dog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $breeds = ['Saluki Mix', 'Desert Dog Mix', 'German Shepherd', 'Husky', 'Canaan', 'Mix Cross', 'Pointer/Hab'];
        $colours = ['Brown/Black', 'White/Beige', 'Tan', 'Black/Tan', 'Cream', 'Golden/White'];

        return [
            'name' => fake()->firstName(),
            'species' => Species::Canine,
            'breed' => fake()->randomElement($breeds),
            'colour_markings' => fake()->randomElement($colours),
            'gender' => fake()->randomElement(Gender::cases()),
            'size' => fake()->randomElement(Size::cases()),
            'date_of_birth' => fake()->dateTimeBetween('-8 years', '-3 months'),
            'date_of_birth_is_approximate' => false,
            'current_status' => DogStatus::AtScas,
            'current_location' => 'Ras Al Khaimah',
            'personality_description' => fake()->sentence(12),
            'energy_level' => fake()->randomElement(EnergyLevel::cases()),
            'temperament' => fake()->sentence(8),
            'good_with_dogs' => fake()->boolean(80),
            'good_with_cats' => fake()->boolean(50),
            'good_with_children' => fake()->boolean(70),
            'good_with_adults' => true,
            'training_level' => fake()->randomElement(TrainingLevel::cases()),
            'potty_trained' => fake()->boolean(70),
            'leash_trained' => fake()->boolean(60),
        ];
    }

    /**
     * A dog currently living at SCAS.
     */
    public function atScas(): static
    {
        return $this->state(fn (array $attributes): array => [
            'current_status' => DogStatus::AtScas,
            'current_location' => 'Ras Al Khaimah',
        ]);
    }

    /**
     * A dog currently in local foster care.
     */
    public function inLocalFoster(): static
    {
        return $this->state(fn (array $attributes): array => [
            'current_status' => DogStatus::LocalFoster,
            'current_location' => 'Dubai',
        ]);
    }

    /**
     * A dog adopted within the UAE.
     */
    public function adoptedInUae(): static
    {
        return $this->state(fn (array $attributes): array => [
            'current_status' => DogStatus::AdoptedUae,
            'current_location' => 'Abu Dhabi',
        ]);
    }

    /**
     * A dog with an incomplete profile, e.g. a legacy import with minimal data.
     */
    public function incomplete(): static
    {
        return $this->state(fn (array $attributes): array => [
            'breed' => null,
            'colour_markings' => null,
            'gender' => null,
            'size' => null,
            'date_of_birth' => null,
            'personality_description' => null,
            'energy_level' => null,
            'temperament' => null,
            'good_with_dogs' => null,
            'good_with_cats' => null,
            'good_with_children' => null,
            'good_with_adults' => null,
            'training_level' => null,
            'potty_trained' => null,
            'leash_trained' => null,
        ]);
    }
}
