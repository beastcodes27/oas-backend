<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolResource extends JsonResource
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
            'name' => $this->name,
            'short_name' => $this->short_name,
            'monogram' => $this->monogram,
            'motto' => $this->motto,
            'type' => $this->type,
            'region' => $this->region,
            'district' => $this->district,
            'rating' => $this->rating,
            'capacity' => $this->capacity,
            'forms' => $this->forms,
            'streams' => $this->streams,
            'combinations' => $this->combinations ?? [],
            'result_links' => $this->result_links ?? [],
            'programs' => $this->programs,
            'contact' => $this->contact,
            'window' => $this->window,
            'applications_open' => $this->applications_open,
            'window_opens_at' => $this->window_opens_at?->toISOString(),
            'window_closes_at' => $this->window_closes_at?->toISOString(),
            'selections_published_at' => $this->selections_published_at?->toISOString(),
        ];
    }
}
