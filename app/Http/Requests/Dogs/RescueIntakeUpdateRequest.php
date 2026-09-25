<?php

namespace App\Http\Requests\Dogs;

use Illuminate\Foundation\Http\FormRequest;

class RescueIntakeUpdateRequest extends FormRequest
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
            'date_received' => ['nullable', 'date'],
            'rescue_location' => ['nullable', 'string', 'max:255'],
            'how_found' => ['nullable', 'string'],
            'initial_condition' => ['nullable', 'string'],
            'source_of_intake' => ['nullable', 'string', 'max:255'],
            'previous_owner_surrender_details' => ['nullable', 'string'],
            'intake_date' => ['nullable', 'date'],
            'intake_notes' => ['nullable', 'string'],
            'rescue_story' => ['nullable', 'string'],
        ];
    }
}
