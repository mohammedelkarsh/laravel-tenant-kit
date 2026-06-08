<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'subdomain' => [
                'required',
                'string',
                'alpha_dash',
                'min:3',
                'max:63',
                Rule::notIn(['www', 'app', 'api', 'admin', 'mail', 'ftp']),
                Rule::unique('tenants', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'subdomain.unique' => 'This workspace URL is already taken.',
            'subdomain.not_in' => 'This subdomain is reserved.',
        ];
    }
}
