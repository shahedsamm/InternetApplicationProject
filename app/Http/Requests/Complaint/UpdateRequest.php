<?php

namespace App\Http\Requests\Complaint;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class UpdateRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        return[
            'type' => ['sometimes','in:noise,garbage,infrastructure,other'],
            'section' => ['sometimes','in:security,finance,education'],
            'location' => ['sometimes','string'],
            'description' => ['sometimes','string'],
            'attachments' => ['nullable','array'],
            'attachments.*' => ['file','max:10240'],
            'status' => ['sometimes','in:new,pending,done,rejected'],
            'notes' => ['nullable','string'],
            'followups' => ['nullable','array'],
            'followups.*.title' => ['required_with:followup','string'],
            'followups.*.description' => ['nullable','string'],
        ];
    }
}
