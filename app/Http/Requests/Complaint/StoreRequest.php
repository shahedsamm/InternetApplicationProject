<?php

namespace App\Http\Requests\Complaint;

use App\Http\Requests\BaseFormRequest;

class StoreRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'citizen_id' => ['required','exists:users,id'],
            'type' => ['required','in:noise,garbage,infrastructure,other'],
            'section' => ['required','in:security,finance,education'],
            'location' => ['required','string'],
            'description' => ['required','string'],
            'attachments' => ['nullable','array'],
            'attachments.*' => ['file','max:10240'], // 10MB each
            'notes' => ['nullable','string'],
        ];
    }

}
