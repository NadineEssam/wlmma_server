<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "title_en" => $this->title_en,
            "title_ar" => $this->title_ar,
            "description_en" => $this->description_en,
            "description_ar" => $this->description_ar,
            "city_name_en" => $this->city_name_en,
            "city_name_ar" => $this->city_name_ar,
            "duration" => $this->duration,
            "price" => $this->price,
            "type_decducted_amount" => $this->type_decducted_amount,
            "decducted_amount" => $this->decducted_amount,
            "capacity" => $this->capacity,
            "id" => $this->id,
            "status" => $this->status,
            "activity_plans" => $this->activity_plans?: $this->activityPlans,
            "tools" => $this->tools,
            "images" => $this->images,
            'is_tourguideable' => $this->is_tourguideable,
            'is_photographer_available' => $this->is_photographer_available,
            'activity_days' => explode(',', $this->activity_days),
            'activity_times' => explode(',', $this->activity_times),
        ];
    }
}
