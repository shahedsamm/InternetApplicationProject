<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
  'section' => 'required|in:وزارةالكهربا,وزارةالمياه ,وزارةالاتصالات,وزارةالصحة,وزارةالتربية',
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],

          

        ];
    }
}
