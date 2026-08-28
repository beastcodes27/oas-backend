<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'entry_level' => ['required', 'string', 'in:Form 1,Form 5'],
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
            'student.phone' => ['required', 'string', 'max:30'],
            'student.email' => ['nullable', 'string', 'email', 'max:255'],
            'student.previous_school' => ['nullable', 'string', 'max:255'],
            'student.previous_class' => ['nullable', 'string', 'max:60'],
            'student.previous_marks' => ['nullable', 'string', 'max:60'],
            'student.disability' => ['nullable', 'string', 'max:100'],

            'guardian.name' => ['required', 'string', 'max:255'],
            'guardian.relation' => ['required', 'string', 'max:60'],
            'guardian.phone' => ['nullable', 'string', 'max:30'],
            'guardian.email' => ['nullable', 'string', 'email', 'max:255'],
            'guardian.occupation' => ['nullable', 'string', 'max:100'],
            'guardian.address' => ['nullable', 'string', 'max:255'],
        ];
    }
}
