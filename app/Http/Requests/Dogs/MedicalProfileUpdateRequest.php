<?php

namespace App\Http\Requests\Dogs;

use Illuminate\Foundation\Http\FormRequest;

class MedicalProfileUpdateRequest extends FormRequest
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
            'spayed_neutered' => ['nullable', 'boolean'],
            'spayed_neutered_date' => ['nullable', 'date'],
            'last_vet_check_date' => ['nullable', 'date'],
            'veterinary_clinic' => ['nullable', 'string', 'max:255'],
            'initial_health_assessment' => ['nullable', 'string'],
            'medical_concerns' => ['nullable', 'string'],
            'treatment_required' => ['nullable', 'string'],
            'medications' => ['nullable', 'string'],
            'medical_notes' => ['nullable', 'string'],
        ];
    }
}
