<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveIntakeRequest extends FormRequest
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
            'exam_type' => ['required', Rule::in(['psle', 'ftna', 'csee'])],
            'exam_reg_number' => ['required', 'string', 'max:40'],
            'exam_year' => ['required', 'integer', 'digits:4', 'between:2000,'.date('Y')],
            'exam_confirmed' => ['required', 'accepted'],
            'exam_result' => ['nullable', 'array'],
        ];
    }
}
