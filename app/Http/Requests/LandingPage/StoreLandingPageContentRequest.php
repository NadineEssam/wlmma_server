<?php

namespace App\Http\Requests\LandingPage;

use Illuminate\Foundation\Http\FormRequest;

class StoreLandingPageContentRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'page_id' => 'required|integer|unique:landing_page_content,page_id',
            'content' => 'required|array',
        ];
    }

    public function messages()
    {
        return [
            'page_id.required' => 'The page ID is required.',
            'page_id.unique' => 'The page ID must be unique.',
            'content.required' => 'The content field is required.',
            'content.array' => 'The content field must be a valid array.',
        ];
    }
}
