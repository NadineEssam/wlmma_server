<?php

namespace App\Http\Requests\LandingPage;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLandingPageContentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            'page_id' => 'required|integer|exists:landing_page_content,page_id',
            'content' => 'required|array',
        ];
    }

    public function messages()
    {
        return [
            'page_id.required' => 'The page ID is required.',
            'page_id.exists' => 'The page ID does not exist.',
            'content.required' => 'The content field is required.',
            'content.array' => 'The content field must be a valid array.',
        ];
    }
}
