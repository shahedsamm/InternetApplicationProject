<?php

namespace App\Http\Controllers;

use App\Services\AuthCitizenService;
use Illuminate\Http\Request;

class CitizenController extends Controller
{
    protected $service;

    public function __construct(AuthCitizenService $service)
    {
        $this->service = $service;
    }

    // تسجيل مواطن جديد + إرسال OTP
    public function register(Request $request)
    {
        $result = $this->service->registerCitizen($request->all());
        return response()->json($result, $result['status'] ? 201 : 422);
    }

    // التحقق من OTP
    public function verifyOtp(Request $request)
    {
        $result = $this->service->verifyOtp($request->all());
        return response()->json($result, $result['status'] ? 200 : 400);
    }

    // إعادة إرسال OTP
    public function resendOtp(Request $request)
    {
        $result = $this->service->resendOtp($request->all());
        return response()->json($result, $result['status'] ? 200 : 400);
    }

    // تسجيل دخول المواطن
    public function login(Request $request)
    {
        $result = $this->service->loginCitizen($request->all());
        return response()->json($result, $result['status'] ? 200 : 422);
    }
}
