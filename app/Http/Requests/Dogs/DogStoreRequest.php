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

class DogStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * New dogs are at SCAS unless the form says otherwise.
     */
    protected function prepareForValidation(): void
    {
        if (blank($this->input('current_status'))) {
            $this->merge(['current_status' => DogStatus::AtScas->value]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Basic Information
            'name' => ['required', 'string', 'max:255'],
            'species' => ['required', Rule::enum(Species::class)],
            'breed' => ['required', 'string', 'max:255'],
            'colour_markings' => ['nullable', 'string', 'max:255'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'size' => ['required', Rule::enum(Size::class)],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'date_of_birth_is_approximate' => ['boolean'],
            'estimated_age_notes' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],

            // Placement
            'current_status' => ['required', Rule::enum(DogStatus::class)],
            'current_location' => ['nullable', 'string', 'max:255'],

            // Personality & Behaviour
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

            // Rescue & Intake
            'rescue_intake.date_received' => ['nullable', 'date'],
            'rescue_intake.rescue_location' => ['nullable', 'string', 'max:255'],
            'rescue_intake.how_found' => ['nullable', 'string'],
            'rescue_intake.initial_condition' => ['nullable', 'string'],
            'rescue_intake.source_of_intake' => ['nullable', 'string', 'max:255'],
            'rescue_intake.previous_owner_surrender_details' => ['nullable', 'string'],
            'rescue_intake.intake_date' => ['nullable', 'date'],
            'rescue_intake.intake_notes' => ['nullable', 'string'],
            'rescue_intake.rescue_story' => ['nullable', 'string'],

            // Initial Medical Information
            'medical.spayed_neutered' => ['nullable', 'boolean'],
            'medical.spayed_neutered_date' => ['nullable', 'date'],
            'medical.veterinary_clinic' => ['nullable', 'string', 'max:255'],
            'medical.initial_health_assessment' => ['nullable', 'string'],
            'medical.medical_concerns' => ['nullable', 'string'],
            'medical.treatment_required' => ['nullable', 'string'],

            // Identification
            'identification.microchip_number' => ['nullable', 'string', 'max:255'],
            'identification.microchip_date' => ['nullable', 'date'],
            'identification.microchip_location' => ['nullable', 'string', 'max:255'],
        ];
    }
}
