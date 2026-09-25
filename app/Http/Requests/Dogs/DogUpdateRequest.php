<?php

namespace App\Http\Requests\Dogs;

use App\Enums\DogStatus;
use App\Enums\EnergyLevel;
use App\Enums\Gender;
use App\Enums\Size;
use App\Enums\Species;
use App\Enums\TrainingLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DogUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'species' => ['required', Rule::enum(Species::class)],
            'breed' => ['nullable', 'string', 'max:255'],
            'colour_markings' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'size' => ['nullable', Rule::enum(Size::class)],
            'date_of_birth' => ['nullable', 'date'],
            'date_of_birth_is_approximate' => ['boolean'],
            'estimated_age_notes' => ['nullable', 'string', 'max:255'],
            'current_status' => ['required', Rule::enum(DogStatus::class)],
            'current_location' => ['nullable', 'string', 'max:255'],
            'personality_description' => ['nullable', 'string'],
            'energy_level' => ['nullable', Rule::enum(EnergyLevel::class)],
            'temperament' => ['nullable', 'string'],
            'good_with_dogs' => ['nullable', 'boolean'],
            'good_with_cats' => ['nullable', 'boolean'],
            'good_with_children' => ['nullable', 'boolean'],
            'good_with_adults' => ['nullable', 'boolean'],
            'training_level' => ['nullable', Rule::enum(TrainingLevel::class)],
            'potty_trained' => ['nullable', 'boolean'],
            'leash_trained' => ['nullable', 'boolean'],
            'basic_commands_notes' => ['nullable', 'string'],
            'behavioural_notes' => ['nullable', 'string'],
            'special_requirements' => ['nullable', 'string'],
        ];
    }
}
