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


  public function update(UpdateComplaintStatusRequest $request)
{
    $employee = Auth::user();
    $data = $request->validated(); // تحتوي على complaint_id + status + notes

    $complaintId = $data['complaint_id']; // رقم الشكوى
    unset($data['complaint_id']);         // ❌ مهم جداً، نزيله قبل التحديث

    $result = $this->service->update($complaintId, $data, $employee);

    return response()->json($result, $result['status'] ? 200 : 400);
}

    
 public function reserveComplaint($id)
    {
        $employee = Auth::user(); // الموظف المسجل الدخول

        $result = $this->service->reserveComplaint($id, $employee);

        return response()->json($result, $result['status'] ? 200 : 400);
    }

     public function cancelReservation(Request $request)
    {
        $employee = Auth::user();
        return response()->json(
            $this->service->cancelReservation($request->complaint_id, $employee)
        );
    }
    

}
