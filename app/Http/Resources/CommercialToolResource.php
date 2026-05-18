<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

class CommercialToolResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'description_en' => $this->description_en,
            'description_ar' => $this->description_ar,
            'price' => $this->price,
            'terms_conditions' => $this->terms_conditions,
            'sameUsersameProvider' => $this->sameUsersameProvider ?? null,  // ✅ Add this line
            'user_id' => $this->user_id,
            'type_id' => $this->type_id,
            'is_favourite' => $this->is_favourite,  // Include the favourite flag
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
            'id' => $this->id,
            'commercial_attribute' => $this->commercialAttribute,
            'user' => $this->user,
            'type' => $this->type,
            'tool_image' => $this->images,
        ];
    }
}
