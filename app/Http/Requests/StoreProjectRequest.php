<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'health_check_url' => ['required', 'url', 'max:255', 'unique:projects,health_check_url'],
            'is_active' => ['boolean'],
            'notification_emails' => ['nullable', 'array'],
            'notification_emails.*' => ['required', 'email', 'distinct', 'max:255'],
        ];
    }
}
