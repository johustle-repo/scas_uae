<?php

namespace App\Http\Requests\Dogs;

use Illuminate\Foundation\Http\FormRequest;

class DogBulkDestroyRequest extends FormRequest
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
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:dogs,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Select at least one dog to delete.',
            'ids.max' => 'Delete up to 100 dogs at a time.',
            'ids.*.exists' => 'One of the selected dogs no longer exists.',
        ];
    }
}
