<?php

namespace App\Http\Requests\Citizen;

use Illuminate\Foundation\Http\FormRequest;

class UpdateComplaintRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'type'        => 'sometimes|in:noise,garbage,infrastructure,other',
              'section'     => 'required|in:كهربا,مياه,اتصالات,وزارة الصحة,وزارة التربية',
            'location'    => 'sometimes|string',
            'description' => 'sometimes|string',

            // ✅ الرقم الوطني قابل للتعديل
            'national_id' => ['sometimes', 'string', 'regex:/^140100\d{5}$/'],

            // ✅ دعم تعديل / إضافة ملفات جديدة
            'attachments'   => 'sometimes|array',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx,xlsx,txt|max:8192',

            // ✅ منع العبث
            'status'      => 'prohibited',
            'citizen_id'  => 'prohibited',
        ];
    }

    public function messages()
    {
        return [
            'type.in'        => 'نوع الشكوى غير صالح.',
            'section.in'     => 'القسم غير صالح.',
            'national_id.regex' => 'الرقم الوطني يجب أن يبدأ بـ 140100 ويتبعه 5 أرقام.',

            'attachments.array'   => 'المرفقات يجب أن تكون على شكل مصفوفة.',
            'attachments.*.file'  => 'كل مرفق يجب أن يكون ملفاً صالحاً.',
            'attachments.*.mimes' => 'نوع الملف غير مدعوم.',
            'attachments.*.max'   => 'حجم الملف يجب ألا يتجاوز 8 ميغابايت.',

            'status.prohibited'      => 'لا يمكن تعديل حالة الشكوى.',
            'citizen_id.prohibited' => 'لا يمكن تعديل صاحب الشكوى.',
        ];
    }
}
