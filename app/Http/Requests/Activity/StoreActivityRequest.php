<?php

namespace App\Http\Requests\Activity;

use App\Enums\AWeekDaysEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
{
    public function authorize()
    {
        return true;  // Adjust based on your authorization logic
    }

    public function rules()
    {
        return [
            // 'start_date' => 'date_format:Y-m-d',
            'spoken_lang' => 'nullable|string',
            'title_en' => 'required|string|max:255',
            // 'title_ar' => 'required|string|max:255',
            'title_ar' => 'nullable|string|max:255',
            'description_en' => 'required|string',
            // 'description_ar' => 'required|string',
            'description_ar' => 'nullable|string',
            'privacy_policy_en' => 'required|string',
            // 'privacy_policy_ar' => 'required|string',
            'privacy_policy_ar' => 'nullable|string',
            'cancel_policy_en' => 'required|string',
            // 'cancel_policy_ar' => 'required|string',
            'cancel_policy_ar' => 'nullable|string',
            'tool_id' => 'nullable|array',
            'tool_id.*' => 'required|integer|exists:commercial_tools,id',
            'city_name_en' => 'required|string',
            // 'city_name_ar' => 'required|string',
            'city_name_ar' => 'nullable|string',
            'duration' => 'required|integer',
            'price' => 'required|numeric',
            // 'capacity' => 'required|integer',
            'lat' => 'nullable|numeric|between:-90,90',
            'long' => 'nullable|numeric|between:-180,180',
            'is_tourguideable' => 'required|boolean',
            'tourguide_price' => 'required_if:is_tourguideable,true|numeric',
            'is_photographer_available' => 'required|boolean',
            'photographer_price' => 'required_if:is_photographer_available,true|numeric',
            'activity_days' => 'array|min:1|required_without:activity_plans',
            'activity_days.*' => ['required_with:activity_days', 'string'],  // Rule::in(AWeekDaysEnum::all())],
            'activity_days' => 'distinct',
            'activity_times' => 'array|min:1|required_with:activity_days',
            'activity_times.*' => 'required_with:activity_times|date_format:h:i:s A',
            'activity_times' => 'distinct',
            // Images validation
            'images' => 'required|array',  // Ensure images is an array
            'images.*' => 'mimes:jpg,jpeg,png|max:5120',
            'activity_type_id' => 'required|numeric|exists:activity_types,id',
            'activity_plans' => 'array|min:1|required_without:activity_days',
            'activity_plans.*.location_en' => 'required_with:activity_plans|string',
            // 'activity_plans.*.location_ar' => 'required_with:activity_plans|string',
            'activity_plans.*.location_ar' => 'nullable|string',
            'activity_plans.*.starts_at' => 'required_with:activity_plans|date_format:h:i:s A',
            'activity_plans.*.ends_at' => 'required_with:activity_plans|date_format:h:i:s A',
            // 'activity_plans.*.dates' => 'required_with:activity_plans|date_format:Y-m-d',
            'tools' => 'nullable|array',
            'tools.*.name_en' => 'required_with:tools|string',
            // 'tools.*.name_ar' => 'required_with:tools|string',
            'tools.*.name_ar' => 'nullable|string',
            'tools.*.description_en' => 'required_with:tools|string',
            // 'tools.*.description_ar' => 'required_with:tools|string',
            'tools.*.description_ar' => 'nullable|string',
            'tools.*.tool_attributes' => 'nullable|array',
            'tools.*.tool_attributes.attribute_name_en' => 'nullable|string',
            'tools.*.tool_attributes.attribute_name_ar' => 'nullable|string',
            'tools.*.tool_attributes.values' => 'nullable|array',
            'tools.*.tool_attributes.values.*.value' => 'nullable|string',
            'tools.*.tool_attributes.values.*.price' => 'nullable|numeric',
            // 'tools.*.tool_attributes' => 'required_with:tools|array',
            // 'tools.*.tool_attributes.attribute_name_en' => 'required_with:tools|string',
            // 'tools.*.tool_attributes.attribute_name_ar' => 'required_with:tools|string',
            // 'tools.*.tool_attributes.values' => 'required_with:tools|array',
            // 'tools.*.tool_attributes.values.*.value' => 'required_with:tools|string',
            // 'tools.*.tool_attributes.values.*.price' => 'required_with:tools|numeric',
            'tools.*.image' => 'required_with:tools|array',  // Ensure 'image' is an array for each tool
            'tools.*.image.*' => 'mimes:jpg,jpeg,png|max:5120',  // Ensure each image is of type jpg, jpeg, or png and not larger than 5MB
        ];
    }
}
