<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublishJoiningInstructionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true || $this->user()?->is_admissions === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required_without:url', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,webp', 'max:10240'],
            'url' => ['required_without:file', 'nullable', 'url', 'max:500'],
            'name' => ['nullable', 'string', 'max:200'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
