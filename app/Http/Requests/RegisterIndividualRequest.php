<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class RegisterIndividualRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'IBAN' => [
                'required',
                'string',
                // 'size:26', // IBAN should be exactly 26 characters to match the typical maximum length
                'regex:/^[A-Z0-9]+$/', // IBAN must be alphanumeric, in uppercase
            ],
            'name' => [
                'required',
                'string',
                'min:3',
                'max:25'
            ],
            'live_photo' => [
                'required',
                'image', // Validates that the uploaded file is an image
                'mimes:jpeg,png,jpg,gif', // Accepted image formats
                'max:2048' // Maximum size in kilobytes (2MB)
            ],
            'national_id' => [
                'required',
                'string',
                'size:10', // IBAN should be exactly 10 characters to match the typical maximum length
                'regex:/^[A-Z0-9]+$/', // IBAN must be alphanumeric, in uppercase
            ],
            'gender' => [
                'required',
                'string',
                'string',
                Rule::in(['male', 'female']), // accepted values
            ],
        ];
    }


    /**
     * Get custom error messages for validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'live_photo.required' => 'A live photo is required.',
            'live_photo.image' => 'The live photo must be an image file.',
            'live_photo.mimes' => 'The live photo must be a file of type: jpeg, png, jpg, gif.',
            'live_photo.max' => 'The live photo must not be greater than 2MB.',
            // Add more custom messages if needed
        ];
    }

}
