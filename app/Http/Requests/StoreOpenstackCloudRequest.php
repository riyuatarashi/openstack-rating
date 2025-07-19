<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOpenstackCloudRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'region_name' => ['required', 'string', 'max:255'],
            'interface' => ['required', 'string', 'in:public,internal,admin'],
            'identity_api_version' => ['required', 'string'],
            'auth_url' => ['required', 'string', 'url'],
            'auth_username' => ['required', 'string', 'max:255'],
            'auth_password' => ['required', 'string', 'max:255'],
            'auth_project_id' => ['required', 'string', 'max:255'],
            'auth_project_name' => ['nullable', 'string', 'max:255'],
            'auth_user_domain_name' => ['required', 'string', 'max:255'],
        ];
    }
}
