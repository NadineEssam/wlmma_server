<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterCompanyRequest extends FormRequest
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
            'TRN' => [
                'required',
                'string',
                'min:3',
                'max:50' // Adjusting max length to accommodate longer TRNs
            ],
            'CR' => [
                'required',
                'string',
                'min:3',
                'max:50' // Adjusting max length to accommodate longer CRs
            ],
            'name' => [
                'required',
                'string',
                'min:3',
                'max:25'
            ],
        ];


    }
}
