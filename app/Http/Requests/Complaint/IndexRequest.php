<?php

namespace App\Http\Requests\Complaint;

use App\Http\Requests\BaseFormRequest;

class IndexRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // pagination
            'page'      => ['sometimes', 'integer', 'min:1'],
            'per_page'  => ['sometimes', 'integer', 'min:1', 'max:200'],

            // filtering fields
            'citizen_id' => ['nullable', 'integer', 'exists:citizens,id'],
            'section'    => ['nullable', 'string', 'in:security,finance,education'],
            'status'     => ['nullable', 'string', 'in:new,pending,done,rejected'],

            // date range
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

}
