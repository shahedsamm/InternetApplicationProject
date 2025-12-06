<?php

namespace App\Http\Requests;
namespace App\Http\Requests\Citizen;
use Illuminate\Foundation\Http\FormRequest;

class LoginCitizenRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email'    => 'required|email|regex:/^[A-Za-z0-9._%+-]+@gmail\.com$/',
            'password' => 'required|string|min:6',
        ];
    }

    public function messages()
    {
        return [
            'email.regex' => 'يجب أن يكون الإيميل من نوع Gmail فقط (example@gmail.com).'
        ];
    }
}
