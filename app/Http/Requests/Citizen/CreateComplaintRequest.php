<?php

namespace App\Http\Requests\Citizen;

use Illuminate\Foundation\Http\FormRequest;

class CreateComplaintRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'type'        => 'required|string',
            'section'     => 'required|in:وزارةالكهربا,وزارةالمياه ,وزارةالاتصالات,وزارةالصحة,وزارةالتربية',
            'location'    => 'required|string',
            'description' => 'required|string',

            // الرقم الوطني حسب المطلوب
            'national_id' => ['required', 'string', 'regex:/^140100\d{5}$/'],

            // مرفقات متعددة
            'attachments'   => 'nullable|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx,xlsx,txt|max:8192',
        ];
    }

    public function messages()
    {
        return [
            'section.in' => 'القسم يجب أن يكون أحد: وزارة الكهربا، وزارة المياه، وزارة الاتصالات، وزارة الصحة، وزارة التربية.',
            'national_id.regex' => 'الرقم الوطني يجب أن يبدأ بـ 140100 متبوعاً بخمس أرقام.',
            'attachments.*.file' => 'كل مرفق يجب أن يكون ملف صالح.',
            'attachments.*.mimes' => 'نوع الملف يجب أن يكون jpg, jpeg, png, pdf, doc, docx, xlsx, أو txt.',
            'attachments.*.max' => 'حجم كل ملف لا يمكن أن يتجاوز 8 ميغابايت.',
        ];
    }
}
