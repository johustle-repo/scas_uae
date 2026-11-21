<?php

namespace App\Http\Requests\Dogs;

use Illuminate\Foundation\Http\FormRequest;

class DogRegisterImportRequest extends FormRequest
{
    /**
     * Maximum workbook size in kilobytes (10 MB).
     */
    public const MAX_KILOBYTES = 10240;

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
            'file' => ['required', 'file', 'extensions:xlsx,csv', 'mimes:xlsx,csv,txt', 'max:'.self::MAX_KILOBYTES],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.extensions' => 'Upload the Dog Register as an Excel (.xlsx) or CSV (.csv) file.',
            'file.mimes' => 'Upload the Dog Register as an Excel (.xlsx) or CSV (.csv) file.',
            'file.max' => 'The file must be 10 MB or smaller.',
        ];
    }
}
