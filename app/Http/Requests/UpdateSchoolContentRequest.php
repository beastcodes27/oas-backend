<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolContentRequest extends FormRequest
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
            'combinations' => ['nullable', 'array'],
            'combinations.*' => ['string', 'max:120'],

            'result_links' => ['nullable', 'array'],
            'result_links.*.name' => ['required', 'string', 'max:200'],
            'result_links.*.url' => ['required', 'string', 'url', 'max:500'],
        ];
    }
}
