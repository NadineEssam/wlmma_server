<?php

namespace App\Http\Requests\CommercialTool;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommercialToolRequest extends FormRequest
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
            'name_en' => 'nullable|string',
            'name_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'tool_type_id' => 'nullable|exists:commercial_tool_types,id',
            'tool_attributes' => 'nullable|array',
            'tool_attributes.attribute_name_en' => 'nullable|string',
            'tool_attributes.attribute_name_ar' => 'nullable|string',
            'tool_attributes.values' => 'nullable|array',
            'tool_attributes.values.*.value' => 'nullable|string',
            'tool_attributes.values.*.price' => 'nullable|numeric',
            'images' => 'nullable|array',
            'images.*' => 'mimes:jpg,jpeg,png|max:5120',
        ];
    }
}
