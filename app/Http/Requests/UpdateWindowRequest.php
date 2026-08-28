<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWindowRequest extends FormRequest
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
            'applications_open' => ['required', 'boolean'],
            'window_opens_at' => ['nullable', 'date'],
            'window_closes_at' => ['nullable', 'date', 'after_or_equal:window_opens_at'],
        ];
    }
}
