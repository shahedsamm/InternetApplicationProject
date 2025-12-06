<?php

namespace App\Services;
use Spatie\Permission\Models\Role;
use App\Jobs\SendOtpEmailJob;
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
    
    $otp = rand(10000, 99999);

    $user = User::create([
        'name'  => $data['name'],
        'phone' => $data['phone'],
        'email' => $data['email'],
        'password' => Hash::make($data['password']),
        'otp_code' => $otp,
        'otp_expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES),
        'no_failed_tries' => 0,
    ]);

    SendOtpEmailJob::dispatch($user->email, $otp);


    return [
        'status' => true,
        'message' => 'تم إنشاء الحساب وإرسال كود التحقق.',
        'user_id' => $user->id
    ];
}

public function verifyOtp($userId, $otpCode)
{
    // الحصول على المستخدم
    $user = User::where('id', $userId)->first();

    if (!$user) {
        return ['status' => false, 'message' => 'المستخدم غير موجود.'];
    }

    // حساب مقفول
    if ($user->blocked_until && now()->lessThan($user->blocked_until)) {
        return ['status' => false, 'message' => 'تم قفل الحساب لمدة 10 دقائق بسبب كثرة المحاولات.'];
    }

    // انتهاء الصلاحية
    if (!$user->otp_expires_at || now()->greaterThan($user->otp_expires_at)) {
        return ['status' => false, 'message' => 'انتهت صلاحية كود التحقق.'];
    }

    // كود خطأ
    if ($user->otp_code != $otpCode) {

        $user->no_failed_tries++;

        if ($user->no_failed_tries >= 3) {
            $user->blocked_until = now()->addMinutes(10);
            $user->blocked = true;
        }

        $user->last_failed_try_date = now();
        $user->save();

        return ['status' => false, 'message' => 'كود التحقق غير صحيح.'];
    }

    // نجاح التحقق
    $user->email_verified_at = now();
    $user->otp_code = null;
    $user->otp_expires_at = null;
    $user->no_failed_tries = 0;
    $user->blocked_until = null;
    $user->blocked = false;
    $user->last_failed_try_date = null;
    $user->save();

    // 🔥 توليد التوكن
    $token = $user->createToken('CitizenToken')->plainTextToken;

    return [
        'status'  => true,
        'message' => 'تم التحقق من الحساب بنجاح.',
        'token'   => $token,
        'user'    => [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ]
    ];
}


   



  

 public function resendOtp($email)
{
    $user = User::where('email', $email)->first();

    if ($user->email_verified_at) {
        return [
            'status' => false,
            'message' => 'الحساب مفعل مسبقاً.',
            'user_id' => $user->id
        ];
    }

    if ($user->otp_expires_at && now()->lessThan($user->otp_expires_at)) {
        $remaining = now()->diffInSeconds($user->otp_expires_at);

        return [
            'status' => false,
            'message' => "الرجاء الانتظار $remaining ثانية.",
            'user_id' => $user->id
        ];
    }

    $otp = rand(10000, 99999);

    $user->otp_code = $otp;
    $user->otp_expires_at = now()->addMinutes(self::OTP_EXPIRY_MINUTES);

    // إعادة فتح القفل
    $user->no_failed_tries = 0;
    $user->blocked_until = null;
    $user->blocked = 0;
    $user->last_failed_try_date = null;

    $user->save();

    SendOtpEmailJob::dispatch($user->email, $otp);

    return [
        'status' => true,
        'message' => 'تم إرسال كود جديد.',
        'user_id' => $user->id
    ];
}


 public function loginCitizen($email, $password)
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return ['status' => false, 'message' => 'البريد الإلكتروني غير مسجل.'];
        }

        if ($user->blocked_until && now()->lessThan($user->blocked_until)) {
            return ['status' => false, 'message' => 'الحساب مقفول حالياً.'];
        }

        if (!$user->email_verified_at) {
            return ['status' => false, 'message' => 'الحساب غير مفعّل.'];
        }

        if (!Hash::check($password, $user->password)) {
            return ['status' => false, 'message' => 'كلمة المرور غير صحيحة.'];
        }

        $token = $user->createToken('citizen_token')->plainTextToken;

        return [
            'status' => true,
            'message' => 'تم تسجيل الدخول بنجاح.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ];
    }



public function logout()
{
    $user = auth()->user();

    if ($user) {
        $user->currentAccessToken()->delete();
    }

    return ['status' => true, 'message' => 'تم تسجيل الخروج بنجاح'];
}


}
 