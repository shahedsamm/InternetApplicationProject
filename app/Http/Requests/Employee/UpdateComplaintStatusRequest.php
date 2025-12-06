<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class UpdateComplaintStatusRequest extends FormRequest
{
    public function authorize()
    {
        return true; // الصلاحيات نتأكد منها عبر middleware
    }

    public function rules()
    {
        return [
            'complaint_id' => 'required|exists:complaints,id',
            'status'       => 'required|in:new,pending,done,rejected',
            'title'        => 'required|string|max:255',
            'notes'        => 'nullable|string',
            'followup_id'  => 'nullable|exists:complaint_followups,id',
        ];
    }
}
