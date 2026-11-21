<?php

namespace App\Http\Requests\Dogs;

use App\Enums\DogStatus;
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
     * Get the validation rules that apply to the request. Fields filled from
     * the Dog Register import (name, breed, sex, size, date of birth,
     * personality, energy and the good-with/training flags) are left out so
     * they cannot be changed by hand.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'species' => ['required', Rule::enum(Species::class)],
            'colour_markings' => ['nullable', 'string', 'max:255'],
            'estimated_age_notes' => ['nullable', 'string', 'max:255'],
            'current_status' => ['required', Rule::enum(DogStatus::class)],
            'current_location' => ['nullable', 'string', 'max:255'],
            'temperament' => ['nullable', 'string'],
            'good_with_adults' => ['nullable', 'boolean'],
            'training_level' => ['nullable', Rule::enum(TrainingLevel::class)],
            'behavioural_notes' => ['nullable', 'string'],
            'special_requirements' => ['nullable', 'string'],
        ];
    }
}
