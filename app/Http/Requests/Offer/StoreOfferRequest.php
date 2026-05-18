<?php

namespace App\Http\Requests\Offer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOfferRequest extends FormRequest
{
    public function authorize()
    {
        return true;  // Adjust based on your authorization logic
    }

    public function rules()
    {
        return [
            'title_en' => 'required|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'link' => 'nullable|url',
            // Single image validation
            'image' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ];
    }
}
