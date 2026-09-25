<?php

namespace App\Http\Requests\Dogs;

use App\Enums\DocumentCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentStoreRequest extends FormRequest
{
    /**
     * File types accepted for dog documents.
     */
    public const ALLOWED_MIMES = 'pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,txt';

    /**
     * Maximum document size in kilobytes (10 MB).
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
            'category' => ['required', Rule::enum(DocumentCategory::class)],
            'title' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:'.self::ALLOWED_MIMES, 'max:'.self::MAX_KILOBYTES],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.mimes' => 'Upload a PDF, image, Word, Excel, CSV or text file.',
            'file.max' => 'Documents must be 10 MB or smaller.',
        ];
    }
}
