<?php

namespace App\Http\Requests\CommercialTool;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommercialToolRequest extends FormRequest
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
            'name_en' => 'required|string',
            // 'name_ar' => 'required|string',
            'name_ar' => 'nullable|string',
            'description_en' => 'required|string',
            // 'description_ar' => 'required|string',
            'description_ar' => 'nullable|string',
            // 'tool_type_id' => 'required|exists:commercial_tool_types,id',
            'tool_attributes' => 'nullable|array',
            'tool_attributes.attribute_name_en' => 'nullable|string',
            'tool_attributes.attribute_name_ar' => 'nullable|string',
            'tool_attributes.values' => 'nullable|array',
            'tool_attributes.values.*.value' => 'nullable|string',
            'tool_attributes.values.*.price' => 'nullable|numeric',
            // 'tool_attributes' => 'required|array',
            // 'tool_attributes.attribute_name_en' => 'required|string',
            // 'tool_attributes.attribute_name_ar' => 'required|string',
            // 'tool_attributes.values' => 'required|array',
            // 'tool_attributes.values.*.value' => 'required|string',
            // 'tool_attributes.values.*.price' => 'required|numeric',
            // 'images' => 'required|array',
            // 'images.*' => 'mimes:jpg,jpeg,png|max:5120',
        ];
    }
}
