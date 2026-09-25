<?php

namespace App\Http\Requests\Dogs;

use App\Enums\RegistrationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IdentificationUpdateRequest extends FormRequest
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
            'microchip_number' => ['nullable', 'string', 'max:255'],
            'microchip_date' => ['nullable', 'date'],
            'microchip_location' => ['nullable', 'string', 'max:255'],
            'microchip_registration_status' => ['nullable', Rule::enum(RegistrationStatus::class)],
            'passport_number' => ['nullable', 'string', 'max:255'],
            'passport_issue_date' => ['nullable', 'date'],
            'passport_expiry_date' => ['nullable', 'date'],
            'passport_issuing_authority' => ['nullable', 'string', 'max:255'],
            'passport_status' => ['nullable', Rule::enum(RegistrationStatus::class)],
            'pcc_number' => ['nullable', 'string', 'max:255'],
            'pcc_registration_date' => ['nullable', 'date'],
            'pcc_registration_status' => ['nullable', Rule::enum(RegistrationStatus::class)],
            'supporting_documents_notes' => ['nullable', 'string'],
        ];
    }
}
