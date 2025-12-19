<?php

namespace App\Http\Requests\Citizen;

use Illuminate\Foundation\Http\FormRequest;

class GetNotificationsRequest extends FormRequest
{
    public function authorize()
    {
        // نفترض أنه يجب تسجيل الدخول
        return auth()->check();
    }

    public function rules()
    {
        return [
            'page' => 'nullable|integer|min:1', // للـ pagination
            'per_page' => 'nullable|integer|min:1|max:50',
        ];
    }
}
