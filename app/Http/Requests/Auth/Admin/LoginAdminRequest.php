<?php

namespace App\Http\Requests\Auth\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LoginAdminRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            // 'phone_number' => 'required|phone:SA',
            'email' => 'required|email',
            'password' => 'required',
        ];
    }

    public function messages()
    {
        return [
            // 'phone_number.phone' => 'The phone number format is invalid. Please enter a valid Saudi Arabian phone number.',
            'phone_number.phone' => __('WRONG_PHONE_NUMBER_FORMAT'),
        ];
    }
}
