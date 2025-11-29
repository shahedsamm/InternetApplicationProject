<?php

namespace App\Http\Requests\RolePermission;

use App\Http\Requests\BaseFormRequest;

class AssignPermissionsRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

}

