<?php

namespace App\Http\Requests\Dogs;

use App\Enums\PlacementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdoptionRecordStoreRequest extends FormRequest
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
            'adoption_type' => ['required', Rule::enum(PlacementType::class)],
            'adopter_name' => ['required', 'string', 'max:255'],
            'adopter_location' => ['nullable', 'string', 'max:255'],
            'adopter_contact' => ['nullable', 'string', 'max:255'],
            'adoption_date' => ['nullable', 'date'],
            'return_date' => ['nullable', 'date', 'after_or_equal:adoption_date'],
            'return_reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
