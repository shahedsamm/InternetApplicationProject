<?php

namespace App\Http\Requests\Auth;
namespace App\Http\Requests\Citizen;

use Illuminate\Foundation\Http\FormRequest;

class LogoutRequest extends FormRequest
{
    public function authorize()
    {
        return true; // المستخدم لازم يكون authenticated عن طريق middleware
    }

    public function rules()
    {
        return [];
    }
}
