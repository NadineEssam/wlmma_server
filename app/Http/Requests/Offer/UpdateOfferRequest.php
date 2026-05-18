<?php

namespace App\Http\Requests\Offer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOfferRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Adjust based on your authorization logic
    }

    public function rules()
    {
        return [
            'title_en' => 'string|max:255',
            'title_ar' => 'string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048', // Optional image
        ];
    }
}
