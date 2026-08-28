<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'gender' => $this->gender,
            'birth_date' => $this->birth_date?->toDateString(),
            'birth_place' => $this->birth_place,
            'nationality' => $this->nationality,
            'region' => $this->region,
            'district' => $this->district,
            'ward' => $this->ward,
            'phone' => $this->phone,
            'email' => $this->email,
            'previous_school' => $this->previous_school,
            'previous_class' => $this->previous_class,
            'previous_marks' => $this->previous_marks,
            'disability' => $this->disability,
            'exam_type' => $this->exam_type,
            'exam_reg_number' => $this->exam_reg_number,
            'exam_year' => $this->exam_year,
            'exam_confirmed' => $this->exam_confirmed,
            'exam_result' => $this->exam_result,
        ];
    }
}
