<?php

namespace App\Http\Requests\RolePermission;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'guard_name' => ['nullable', 'string', 'max:255', 'in:web,api'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

}

