<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class LoginEmployeeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email'    => [
                'required',
                'email',
                'regex:/^[A-Za-z0-9._%+-]+@gmail\.com$/'
            ],
            'password' => 'required|string|min:6',
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'الإيميل مطلوب.',
            'email.email'    => 'صيغة الإيميل غير صحيحة.',
            'email.regex'    => 'يجب أن يكون الإيميل من نوع Gmail فقط.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ];
    }
}
