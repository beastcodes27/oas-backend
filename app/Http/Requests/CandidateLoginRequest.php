<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CandidateLoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalise the candidate index number into the login username.
     */
    public function prepareForValidation(): void
    {
        $this->merge([
            'username' => strtoupper(trim((string) $this->input('index_number'))),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'index_number' => ['required', 'string', 'max:60'],
            'username' => ['required', 'string', 'max:60'],
            'password' => ['required', 'string'],
        ];
    }
}
