<?php

namespace App\Http\Requests\Citizen;

use Illuminate\Foundation\Http\FormRequest;

class VerifyCitizenRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'user_id'  => 'required|exists:users,id',
            'otp_code' => 'required|digits:5',
        ];
    }

    public function messages()
    {
        return [
            'user_id.exists' => 'المستخدم غير موجود.',
            'otp_code.digits' => 'كود التحقق يجب أن يكون 5 أرقام.',
        ];
    }
}
