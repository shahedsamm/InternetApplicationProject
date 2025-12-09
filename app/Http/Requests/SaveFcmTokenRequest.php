<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveFcmTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // أو تحط شرط صلاحية لو بدك
    }

    public function rules(): array
    {
        return [
            'fcm_token' => 'required|string'
        ];
    }

    public function messages(): array
    {
        return [
            'fcm_token.required' => 'FCM Token مطلوب',
            'fcm_token.string'   => 'FCM Token يجب أن يكون نص',
        ];
    }
}
