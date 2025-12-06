<?php 
namespace App\Http\Requests;
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
            'type'        => 'required|in:noise,garbage,infrastructure,other',
            'section'     => 'required|in:security,finance,education',
            'location'    => 'required|string',
            'description' => 'required|string',
             'national_id' => ['required', 'string', 'regex:/^140100\d{5}$/'],
            
            // تعديل هنا للسماح بأكثر من ملف
            'attachments'          => 'nullable|array',        // نسمح بوجود array من الملفات
            'attachments.*'        => 'file|mimes:jpg,jpeg,png,pdf,doc,docx,xlsx,txt|max:8192', // قواعد لكل ملف في القائمة
        ];
    }

    public function messages()
    {
        return [
            'national_id.regex' => 'الرقم الوطني يجب أن يبدأ بـ وبعدها خمس ارقام140100 .',
            'attachments.*.file' => 'كل مرفق يجب أن يكون ملف صالح.',
            'attachments.*.mimes' => 'نوع الملف يجب أن يكون jpg, jpeg, png, pdf, doc, docx, xlsx, أو txt.',
            'attachments.*.max' => 'حجم كل ملف لا يمكن أن يتجاوز 8 ميغابايت.',
        ];
    }
}
