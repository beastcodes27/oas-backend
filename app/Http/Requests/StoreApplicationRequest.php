<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entry_level' => ['required', 'string', 'in:Form 1,Form 3,Form 5'],
            'school_id' => ['nullable', 'integer', 'exists:schools,id'],

            'student.first_name' => ['required', 'string', 'max:255'],
            'student.last_name' => ['required', 'string', 'max:255'],
            'student.gender' => ['nullable', 'string', 'in:male,female'],
            'student.birth_date' => ['nullable', 'date', 'before:today'],
            'student.birth_place' => ['nullable', 'string', 'max:120'],
            'student.nationality' => ['nullable', 'string', 'max:60'],
            'student.region' => ['required', 'string', 'max:100'],
            'student.district' => ['required', 'string', 'max:100'],
            'student.ward' => ['required', 'string', 'max:100'],
            'student.phone' => ['nullable', 'string', 'max:30'],
            'student.email' => ['nullable', 'string', 'email', 'max:255'],
            'student.previous_school' => ['nullable', 'string', 'max:255'],
            'student.disability' => ['nullable', 'string', 'max:100'],

            'student.exam_type' => ['required', Rule::in(['psle', 'ftna', 'csee'])],
            'student.exam_reg_number' => ['required', 'string', 'max:40'],
            'student.exam_year' => ['required', 'integer', 'digits:4', 'between:2000,'.date('Y')],
            'student.exam_confirmed' => ['required', 'accepted'],
            'student.exam_result' => ['nullable', 'array'],

            'guardian.name' => ['required', 'string', 'max:255'],
            'guardian.relation' => ['required', 'string', 'max:60'],
            'guardian.phone' => ['nullable', 'string', 'max:30'],
            'guardian.email' => ['nullable', 'string', 'email', 'max:255'],
            'guardian.occupation' => ['nullable', 'string', 'max:100'],
            'guardian.address' => ['nullable', 'string', 'max:255'],
        ];
    }
}
