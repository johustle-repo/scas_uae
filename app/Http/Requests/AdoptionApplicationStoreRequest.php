<?php

namespace App\Http\Requests;

use App\Enums\HomeType;
use App\Http\Controllers\WelcomeController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdoptionApplicationStoreRequest extends FormRequest
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
            'dog_id' => [
                'required',
                'integer',
                Rule::in(WelcomeController::listedDogs(WelcomeController::ADOPTABLE_STATUSES)->pluck('id')),
            ],
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{7,30}$/'],
            'city' => ['required', 'string', 'max:120'],
            'home_type' => ['required', Rule::enum(HomeType::class)],
            'has_garden' => ['boolean'],
            'has_children' => ['boolean'],
            'other_pets' => ['nullable', 'string', 'max:1000'],
            'experience' => ['nullable', 'string', 'max:1000'],
            'message' => ['required', 'string', 'min:20', 'max:3000'],
            'agree' => ['accepted'],
            // Honeypot: hidden from people, filled in by bots.
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dog_id.in' => 'This dog is no longer available for adoption.',
            'phone.regex' => 'Enter a valid phone number.',
            'message.min' => 'Please tell us a little more (at least 20 characters).',
            'agree.accepted' => 'Please confirm that SCAS may contact you about this application.',
        ];
    }
}
