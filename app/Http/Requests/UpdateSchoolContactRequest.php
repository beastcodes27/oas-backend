<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contact.phone' => ['required', 'string', 'max:30'],
            'contact.email' => ['required', 'string', 'email', 'max:255'],
            'contact.address' => ['required', 'string', 'max:255'],
        ];
    }
}
