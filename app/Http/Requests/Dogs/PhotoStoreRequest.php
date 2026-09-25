<?php

namespace App\Http\Requests\Dogs;

use App\Enums\PhotoKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PhotoStoreRequest extends FormRequest
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
            'kind' => ['required', Rule::enum(PhotoKind::class)],
            'caption' => ['nullable', 'string', 'max:255'],
            'photos' => ['required', 'array', 'min:1', 'max:10'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photos.max' => 'Upload up to 10 photos at a time.',
            'photos.*.image' => 'Each photo must be a JPG, PNG or WebP image.',
            'photos.*.mimes' => 'Each photo must be a JPG, PNG or WebP image.',
            'photos.*.max' => 'Each photo must be 8 MB or smaller.',
        ];
    }
}
