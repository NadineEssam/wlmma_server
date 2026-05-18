<?php

namespace App\Http\Resources\LandingPage;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LandingPageContentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'page_id' => $this->page_id,
            'content' => $this->content,
        ];
    }
}
