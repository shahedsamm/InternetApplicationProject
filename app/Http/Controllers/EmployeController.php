<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\AuthController;
use App\Http\Requests\Employee\LoginEmployeeRequest;
use App\Http\Requests\Employee\UpdateComplaintStatusRequest;
use App\Services\EmployeeAuthService;
use Illuminate\Support\Facades\Auth;

use App\Services\EmployeeComplaintService;


class EmployeController extends Controller
{
    private $service;

    public function __construct(EmployeeAuthService $service)
    {
        $this->service = $service;
    }

    public function login(LoginEmployeeRequest $request)
    {
        $result = $this->service->login($request->validated());

        if (!$result['status']) {
            return response()->json([
                'status'  => false,
                'message' => $result['message']
            ], 401);
        }

        return response()->json([
            'status'  => true,
            'token'   => $result['token'],
            'user'    => $result['user']
        ], 200);
    }



     public function departmentComplaints(Request $request)
    {
        $employee = auth()->user();

        $result = $this->service->getComplaintsForEmployeeDepartment($employee);

        return response()->json($result, 200);
    }


     public function updateStatus(UpdateComplaintStatusRequest $request)
    {
        $employee = Auth::user();

        $result = $this->service->updateStatus($employee, $request->validated());

        return response()->json($result, $result['status'] ? 200 : 400);
    }
    
}
