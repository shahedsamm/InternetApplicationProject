<?php

namespace App\Http\Requests;
namespace App\Http\Requests\Citizen;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'     => 'required|string|min:6',
            'phone'    => 'required|string|regex:/^\+963[0-9]{9}$/|unique:users,phone',
            'email'    => 'required|email|unique:users,email|regex:/^[A-Za-z0-9._%+-]+@gmail\.com$/',
            'password' => 'required|min:6'
        ];
    }

    public function messages()
    {
        return [
            'phone.regex' => 'رقم الهاتف يجب أن يبدأ بـ +963 ويتكون من 12 خانة.',
            'email.regex' => 'يجب أن يكون الإيميل من نوع Gmail فقط (example@gmail.com).'
        ];
    }
}
