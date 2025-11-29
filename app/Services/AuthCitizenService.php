<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AuthCitizenService
{
    const OTP_EXPIRY_MINUTES = 5;
    const OTP_MAX_ATTEMPTS = 3;

    public function registerCitizen($data)
    {
        // التحقق من المدخلات
        $validator = Validator::make($data, [
            'name'     => 'required|string|min:6',
            'phone'    => 'required|string|regex:/^\+963[0-9]{9}$/|unique:users,phone',
            'email'    => 'required|email|unique:users,email|regex:/^[A-Za-z0-9._%+-]+@gmail\.com$/',
            'password' => 'required|min:6'
        ], [
            'phone.regex' => 'رقم الهاتف يجب أن يبدأ بـ +963 ويتكون من 12 خانة.',
            'email.regex' => 'يجب أن يكون الإيميل من نوع Gmail فقط (example@gmail.com).'
        ]);

        if ($validator->fails()) {
            return [
                'status' => false,
                'errors' => $validator->errors()
            ];
        }

        // توليد كود OTP
        $otp = rand(10000, 99999);

        $user = User::create([
            'name'           => $data['name'],
            'phone'          => $data['phone'],
            'email'          => $data['email'],
            'password'       => Hash::make($data['password']),
            'otp_code'       => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(self::OTP_EXPIRY_MINUTES),
            'no_failed_tries'   => 0, // reset
        ]);

        $this->sendOtpToEmail($user->email, $otp);

        return [
            'status'  => true,
            'message' => 'تم إنشاء الحساب وإرسال كود التحقق.',
            'user_id' => $user->id
        ];
    }

public function verifyOtp($data)
{
    $validator = Validator::make($data, [
        'otp_code' => 'required|digits:5',
    ]);

    if ($validator->fails()) {
        return [
            'status' => false,
            'errors' => $validator->errors()
        ];
    }

    // البحث عن المستخدم حسب OTP
    $user = User::where('otp_code', $data['otp_code'])->first();

    // إذا الكود غير موجود
    if (!$user) {

        $lastUser = User::whereNotNull('otp_expires_at')
                        ->where('otp_expires_at', '>', now())
                        ->orderBy('otp_expires_at', 'desc')
                        ->first();

        if ($lastUser) {

            // الحساب مقفل → لا نزيد المحاولات
            if ($lastUser->blocked_until && now()->lessThan($lastUser->blocked_until)) {
                return [
                    'status' => false,
                    'message' => 'تم قفل الحساب لمدة 10 دقائق بسبب كثرة المحاولات. الرجاء الانتظار.'
                ];
            }

            $lastUser->no_failed_tries += 1;

            if ($lastUser->no_failed_tries >= 3) {
                $lastUser->blocked_until = now()->addMinutes(10);
                $lastUser->blocked = true;   // ⬅ تمت إضافة هذا السطر
            }

            $lastUser->last_failed_try_date = now();
            $lastUser->save();
        }

        return [
            'status' => false,
            'message' => 'كود التحقق غير صحيح.'
        ];
    }

    // الحساب مقفول
    if ($user->blocked_until && now()->lessThan($user->blocked_until)) {
        return [
            'status' => false,
            'message' => 'تم قفل الحساب لمدة 10 دقائق بسبب كثرة المحاولات.'
        ];
    }

    // انتهاء الصلاحية
    if (!$user->otp_expires_at || now()->greaterThan($user->otp_expires_at)) {
        return [
            'status' => false,
            'message' => 'انتهت صلاحية كود التحقق.'
        ];
    }

    // إذا كان لديه 3 محاولات بالفعل
    if ($user->no_failed_tries >= 3) {
        $user->blocked_until = now()->addMinutes(10);
        $user->blocked = true; // ⬅ مهم جداً
        $user->save();

        return [
            'status' => false,
            'message' => 'تم قفل الحساب لمدة 10 دقائق بسبب كثرة المحاولات.'
        ];
    }

    // الكود خطأ
    if ($user->otp_code != $data['otp_code']) {

        $user->no_failed_tries += 1;

        if ($user->no_failed_tries >= 3) {
            $user->blocked_until = now()->addMinutes(10);
            $user->blocked = true;   // ⬅ هنا كمان
            $user->save();

            return [
                'status' => false,
                'message' => 'كود التحقق غير صحيح. تم قفل الحساب لمدة 10 دقائق.'
            ];
        }

        $user->last_failed_try_date = now();
        $user->save();

        return [
            'status' => false,
            'message' => 'كود التحقق غير صحيح. المحاولة: ' . $user->no_failed_tries . ' / 3'
        ];
    }

    // الكود صحيح → فك القفل وإعادة تعيين
    $user->email_verified_at = now();
    $user->otp_code = null;
    $user->otp_expires_at = null;
    $user->no_failed_tries = 0;
    $user->blocked_until = null;
    $user->blocked = false;   // ⬅ فك القفل نهائياً
    $user->last_failed_try_date = null;
    $user->save();

    return [
        'status' => true,
        'message' => 'تم التحقق من الحساب بنجاح.'
    ];
}







    private function sendOtpToEmail($email, $otp)
    {
        Mail::raw("كود التحقق الخاص بك هو: $otp (صالح لمدة 5 دقائق)", function ($msg) use ($email) {
            $msg->to($email)->subject('رمز التحقق');
        });
    }



  

public function resendOtp($data)
{
    $validator = Validator::make($data, [
        'email' => 'required|email|exists:users,email',
    ]);

    if ($validator->fails()) {
        return ['status' => false, 'errors' => $validator->errors()];
    }

    $user = User::where('email', $data['email'])->first();

    // إذا الحساب مفعل مسبقاً
    if ($user->email_verified_at) {
        return [
            'status' => false,
            'message' => 'الحساب مفعل بالفعل، لا يمكن إرسال كود جديد.'
        ];
    }

    // إذا كان لديه OTP شغال الآن → يجب الانتظار
    if ($user->otp_expires_at && now()->lessThan($user->otp_expires_at)) {
        $remaining = now()->diffInSeconds($user->otp_expires_at);
        return [
            'status' => false,
            'message' => "لا يمكن إرسال كود جديد قبل انتهاء الصلاحية. الرجاء الانتظار $remaining ثانية.",
        ];
    }

    // توليد كود جديد
    $otp = rand(10000, 99999);

    // تحديث بيانات المستخدم — فتح القفل + إعادة التعيين
    $user->otp_code = $otp;
    $user->otp_expires_at = now()->addMinutes(self::OTP_EXPIRY_MINUTES);

    // فتح القفل كاملاً
    $user->no_failed_tries = 0;
    $user->blocked_until = null;
    $user->last_failed_try_date = null;

    // 👉 إضافة إعادة blocked إلى 0
    if (isset($user->blocked)) {
        $user->blocked = 0;
    }

    $user->save();

    // إرسال البريد
    $this->sendOtpToEmail($user->email, $otp);

    return [
        'status' => true,
        'message' => 'تم إرسال كود تحقق جديد. تم فتح القفل وإعادة ضبط جميع القيود.'
    ];
}

public function loginCitizen($data) //4
{
    $validator = Validator::make($data, [
        'email'    => 'required|email|regex:/^[A-Za-z0-9._%+-]+@gmail\.com$/',
        'password' => 'required|string|min:6',
    ],[
        'email.regex' => 'يجب أن يكون الإيميل من نوع Gmail فقط (example@gmail.com).'
    ]);

    if ($validator->fails()) {
        return [
            'status' => false,
            'errors' => $validator->errors()
        ];
    }

    // البحث عن المستخدم
    $user = User::where('email', $data['email'])->first();

    if (!$user) {
        return [
            'status' => false,
            'message' => 'البريد الإلكتروني غير مسجل.'
        ];
    }

    // -------------------------------------
    // 🔒 التحقق من أن الحساب غير مقفول
    // -------------------------------------
    if ($user->blocked_until && now()->lessThan($user->blocked_until)) {
        $remaining = now()->diffInMinutes($user->blocked_until);

        return [
            'status' => false,
            'message' => "تم قفل الحساب بسبب كثرة المحاولات. الرجاء الانتظار $remaining دقيقة."
        ];
    }

    if (isset($user->blocked) && $user->blocked == 1) {
        return [
            'status' => false,
            'message' => 'تم قفل الحساب. الرجاء طلب كود تحقق جديد لإعادة فتح الحساب.'
        ];
    }

    // -------------------------------------
    // ✔️ التحقق أن الحساب مُفعّل
    // -------------------------------------
    if (!$user->email_verified_at) {
        return [
            'status' => false,
            'message' => 'الحساب غير مفعّل. الرجاء تفعيل بريدك الإلكتروني أولاً.'
        ];
    }

    // -------------------------------------
    // 🔑 التحقق من كلمة المرور
    // -------------------------------------
    if (!Hash::check($data['password'], $user->password)) {
        return [
            'status' => false,
            'message' => 'كلمة المرور غير صحيحة.'
        ];
    }

    // إنشاء توكن (Sanctum)
    $token = $user->createToken('citizen_token')->plainTextToken;

    return [
        'status' => true,
        'message' => 'تم تسجيل الدخول بنجاح.',
        'token' => $token,
        'user'  => [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
        ]
    ];
}




}
 