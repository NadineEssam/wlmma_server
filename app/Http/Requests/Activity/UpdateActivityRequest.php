<?php

namespace App\Http\Requests\Activity;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Adjust based on your authorization logic
    }

    public function rules()
    {
        return [
            'title_ar' => 'nullable|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'location' => 'nullable|string',
            // 'date' => 'nullable|date',
            // 'time' => 'nullable|date_format:H:i',
            // 'duration' => 'nullable|integer',
            'price' => 'nullable|numeric',
            'capacity' => 'nullable|integer',
        ];
    }
}
