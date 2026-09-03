<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CandidateRegisterRequest extends FormRequest
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
            'index_number' => ['required', 'string', 'max:60', 'regex:/^[0-9A-Za-z\/\-\. ]+$/'],
            'username' => ['required', 'string', 'max:60', 'unique:users,username'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
        ];
    }

    public function messages(): array
    {
        return [
            'index_number.regex' => 'The index number contains invalid characters.',
            'username.unique' => 'An account for this index number already exists. Please sign in instead.',
            'password.letters' => 'The password must contain at least one letter.',
            'password.mixed_case' => 'The password must contain both upper and lower case letters.',
            'password.numbers' => 'The password must contain at least one number.',
        ];
    }
}
