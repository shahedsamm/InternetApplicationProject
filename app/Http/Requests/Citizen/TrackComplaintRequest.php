<?php

namespace App\Http\Requests\Citizen;

use Illuminate\Foundation\Http\FormRequest;

class TrackComplaintRequest extends FormRequest
{
    public function authorize()
    {
        return true; // السماح لأي مستخدم مصادق عليه
    }

    public function rules()
    {
        return [
            'serial_number' => 'required|string',
        ];
    }

    public function messages()
    {
       return [
            'serial_number.required' => 'الرقم المرجعي للشكوى مطلوب.',
            'serial_number.string'   => 'الرقم المرجعي يجب أن يكون نص.',
        ];
    }
}
