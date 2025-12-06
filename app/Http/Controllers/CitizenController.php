<?php

namespace App\Http\Controllers;
use App\Http\Requests\Citizen\RegisterRequest;
use App\Http\Requests\Citizen\VerifyCitizenRequest;
use App\Http\Requests\Citizen\ResendOtpRequest;
use App\Http\Requests\Citizen\LoginCitizenRequest;
use App\Http\Requests\Citizen\LogoutRequest;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\AuthCitizenService;
use Illuminate\Http\Request;

class CitizenController extends Controller
{
    protected $service;

    public function __construct(AuthCitizenService $service)
    {
        $this->service = $service;
    }

  public function register(RegisterRequest $request)
    {
        // ⭐ استخدم الـ Service فقط
        $response = $this->service->registerCitizen($request->validated());

        return response()->json($response, $response['status'] ? 201 : 400);
    }
   public function verifyOtp(VerifyCitizenRequest $request)
{
    $data = $request->validated();

    return response()->json(
        $this->service->verifyOtp($data['user_id'], $data['otp_code'])
    );
}


public function resendOtp(ResendOtpRequest $request)
{
    return $this->service->resendOtp($request->email);
}

public function login(LoginCitizenRequest $request)
{
    return $this->service->loginCitizen(
        $request->email,
        $request->password
    );
}

public function logout(LogoutRequest $request)
{
    $response = $this->service->logout();

    return response()->json($response);
}

   
}
