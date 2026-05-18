<?php

namespace App\Http\Requests\Activity;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateActivityTypes extends FormRequest
{
    public function authorize()
    {
        return true; // Adjust based on your authorization logic
    }

    public function rules()
    {
        return [
            'name_en' => 'string|max:255',
            'name_ar' => 'string|max:255',
            // 'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048', // Optional image
        ];
    }
}
