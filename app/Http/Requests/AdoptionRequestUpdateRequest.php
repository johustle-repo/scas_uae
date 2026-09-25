<?php

namespace App\Http\Requests;

use App\Enums\AdoptionApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdoptionRequestUpdateRequest extends FormRequest
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
            'status' => ['required', Rule::enum(AdoptionApplicationStatus::class)],
            'staff_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
