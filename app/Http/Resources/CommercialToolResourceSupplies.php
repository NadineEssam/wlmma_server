<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

class CommercialToolResourceSupplies extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // These actually belong to the tool, not supplies_rents
            'name_en' => $this->tool->name_en ?? null,
            'name_ar' => $this->tool->name_ar ?? null,
            'description_en' => $this->tool->description_en ?? null,
            'description_ar' => $this->tool->description_ar ?? null,
            'price' => $this->price,
            'sameUsersameProvider' => $this->sameUsersameProvider ?? null,
            'user_id' => $this->tool->user_id ?? null,
            'type_id' => $this->tool->type_id ?? null,
            'is_favourite' => (bool) $this->is_favourite,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
            'commercial_attribute' => $this->tool->commercialAttribute,
            'supplier' => $this->supplier,
            'renter' => $this->renter,
            'type' => $this->tool->type,
            // Fix tool images
            'tool_images' => $this->tool->toolImages->map(function ($image) {
                return [
                    'id' => $image->id,
                    'image_path' => env('APP_URL') . 'storage/app/public/' . $image->image_path,
                ];
            }),
        ];
    }
}
