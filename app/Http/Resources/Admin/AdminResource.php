<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminResource extends JsonResource
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
            'phone_number' => $this->phone_number,
            'name' => $this->first_name . ' ' . $this->sur_name,
            'email' => $this->email,
            'fcm_token' => $this->fcm_token,
            'address' => $this->address,
            'is_authorized_by_manager' => $this->is_authorized_by_manager,
            'national_id' => $this->national_id,
        ];
    }
}
