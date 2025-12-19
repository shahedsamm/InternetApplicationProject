<?php

namespace App\Services;
use Spatie\Permission\Models\Role;
use App\Jobs\SendOtpEmailJob;
use App\Jobs\SendAccountBlockedEmailJob;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AuthCitizenService
{
    const OTP_EXPIRY_MINUTES = 5;
    const OTP_MAX_ATTEMPTS = 3;
    private const BLOCK_MINUTES = 15;
    // الحد الأقصى لمحاولات تسجيل الدخول
    private const MAX_FAILED_ATTEMPTS = 5;

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
    // 1️⃣ جلب المستخدم
    $user = User::find($userId);

    if (!$user) {
        return [
            'status' => false,
            'message' => 'المستخدم غير موجود.'
        ];
    }

    // 2️⃣ الحساب مقفول حالياً
    if ($user->blocked_until && now()->lessThan($user->blocked_until)) {
        return [
            'status' => false,
            'message' => 'تم قفل الحساب لمدة 10 دقائق بسبب كثرة المحاولات.'
        ];
    }

    // 3️⃣ انتهاء صلاحية الكود
    if (!$user->otp_expires_at || now()->greaterThan($user->otp_expires_at)) {
        return [
            'status' => false,
            'message' => 'انتهت صلاحية كود التحقق.'
        ];
    }

    // 4️⃣ كود التحقق خاطئ
    if ($user->otp_code != $otpCode) {

        $user->no_failed_tries++;

        // 🔒 قفل الحساب بعد 3 محاولات + إرسال إيميل مرة واحدة
        if ($user->no_failed_tries >= 3 && !$user->blocked) {

            $user->blocked = true;
            $user->blocked_until = now()->addMinutes(10);

            // 📧 إرسال الإيميل
            SendAccountBlockedEmailJob::dispatch($user->email);
        }

        $user->last_failed_try_date = now();
        $user->save();

        return [
            'status' => false,
            'message' => 'كود التحقق غير صحيح.'
        ];
    }

    // 5️⃣ نجاح التحقق
    $user->email_verified_at = now();
    $user->otp_code = null;
    $user->otp_expires_at = null;
    $user->no_failed_tries = 0;
    $user->blocked = false;
    $user->blocked_until = null;
    $user->last_failed_try_date = null;
    $user->save();

    // 🔑 توليد التوكن
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

    // ❌ بيانات خاطئة (لا نكشف وجود الإيميل)
    if (!$user || !Hash::check($password, $user->password)) {
        return [
            'status' => false,
            'message' => 'بيانات تسجيل الدخول غير صحيحة.'
        ];
    }

    // 📧 الحساب غير مفعل
    if (!$user->email_verified_at) {
        return [
            'status' => false,
            'message' => 'يرجى تفعيل الحساب عبر البريد الإلكتروني قبل تسجيل الدخول.'
        ];
    }

    // 👇 استخدام Cache لحساب عدد المحاولات الناجحة
    $cacheKey = 'successful_login_'.$user->id;
    $successAttempts = Cache::get($cacheKey, 0);

    // 🚫 إذا تجاوزت 5 محاولات ناجحة في ربع ساعة → حظر 15 دقيقة
    if ($successAttempts >= 5) {
        return [
            'status' => false,
            'message' => 'تم حظر تسجيل الدخول مؤقتاً بسبب تجاوز عدد المحاولات الناجحة. يرجى المحاولة بعد 15 دقيقة.'
        ];
    }

    // ✅ تسجيل محاولة ناجحة
    $successAttempts++;
    Cache::put($cacheKey, $successAttempts, now()->addMinutes(15));

    // إنشاء توكن
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
 