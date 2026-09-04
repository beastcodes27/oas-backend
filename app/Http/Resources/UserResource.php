<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'is_admin' => $this->is_admin,
            'is_admissions' => $this->is_admissions,
            'intake' => [
                'entry_level' => $this->entry_level,
                'exam_type' => $this->exam_type,
                'exam_reg_number' => $this->exam_reg_number,
                'exam_year' => $this->exam_year,
                'exam_confirmed' => $this->exam_confirmed,
                'exam_confirmed_at' => $this->exam_confirmed_at?->toISOString(),
                'exam_result' => $this->exam_result,
            ],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
