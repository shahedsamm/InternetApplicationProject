<?php

namespace App\Services;

use App\Models\User;
use App\Models\Complaint;
use App\Models\ComplaintUpdateHistory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class EmployeeAuthService
{
    public function login(array $data)
    {
        // البحث عن المستخدم بالإيميل
        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            return [
                'status' => false,
                'message' => 'المستخدم غير موجود.'
            ];
        }

        // التحقق من كلمة المرور
        if (!Hash::check($data['password'], $user->password)) {
            return [
                'status' => false,
                'message' => 'كلمة المرور غير صحيحة.'
            ];
        }

        // ✅ التحقق أنه موظف
        if (!$user->hasRole('employee')) {
            return [
                'status' => false,
                'message' => 'أنت لا تملك صلاحية الدخول كموظف.'
            ];
        }

        // ✅ إنشاء توكن
        $token = $user->createToken('employee-token')->plainTextToken;

        return [
            'status' => true,
            'token'  => $token,
            'user'   => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ]
        ];
    }

 public function getComplaintsForEmployeeDepartment($employee)
{
    $departmentId = $employee->section;
    $employeeId   = $employee->id;

    $complaints = Complaint::where('section', $departmentId)

        // ✅ آخر تعديل من المواطن
        ->with(['followups' => function ($q) {
            $q->latest()->limit(1);
        }])

        // ✅ آخر إجراء من نفس الموظف
        ->with(['updateHistories' => function ($q) use ($employeeId) {
            $q->where('employee_id', $employeeId)
              ->latest()
              ->limit(1);
        }])

        ->latest()
        ->get()

        ->map(function ($c) {

    $lastCitizenUpdate  = $c->followups->first();        // آخر تعديل مواطن
    $lastEmployeeUpdate = $c->updateHistories->first(); // آخر إجراء موظف

    // ✅ معالجة الوصف إذا كان JSON
    $finalType = $c->type;
    $finalDescription = $c->description;
    $finalLocation = $c->location;

    if ($lastCitizenUpdate) {
        $changes = json_decode($lastCitizenUpdate->description, true);

        $finalType        = $changes['type']['new'] ?? $c->type;
        $finalDescription = $changes['description']['new'] ?? $c->description;
        $finalLocation    = $changes['location']['new'] ?? $c->location;
    }

    return [
        'serial_number' => $c->serial_number,
        'type'          => $finalType,
        'description'   => $finalDescription,
        'location'      => $finalLocation,
        'section'       => $c->section,
        'status'        => $lastEmployeeUpdate?->status ?? $c->status,
        'last_employee_note' => $lastEmployeeUpdate?->notes,
        'updated_at'    => optional($lastEmployeeUpdate ?? $lastCitizenUpdate)->created_at?->format('Y-m-d H:i'),
    ];
});


    return [
        'status' => true,
        'data'   => $complaints
    ];
}

 public function updateStatus($employee, $data)
{
    DB::beginTransaction();

    try {
        // ✅ جلب الشكوى مع قفل على مستوى قاعدة البيانات
        $complaint = Complaint::lockForUpdate()->find($data['complaint_id']);

        if (!$complaint) {
            return [
                'status' => false,
                'message' => 'الشكوى غير موجودة.'
            ];
        }

        // ✅ التحقق أن الموظف من نفس القسم
        if ($complaint->section !== $employee->section) {
            return [
                'status' => false,
                'message' => 'لا يسمح لك بتعديل شكوى من قسم آخر.'
            ];
        }

        // ✅ التحقق من وجود قفل من موظف آخر
        if ($complaint->locked_by && $complaint->locked_by !== $employee->id) {
            return [
                'status' => false,
                'message' => 'الشكوى قيد المعالجة من موظف آخر حالياً.'
            ];
        }

        // ✅ وضع القفل لهذا الموظف
        $complaint->locked_by = $employee->id;
        $complaint->locked_at = now();
        $complaint->save();

        // ✅ تحديث الحالة
        $complaint->status = $data['status'];
        $complaint->save();

        // ✅ حفظ السجل في history
        $history = ComplaintUpdateHistory::create([
            'complaint_id' => $complaint->id,
            'employee_id'  => $employee->id,
            'status'       => $data['status'],
            'notes'        => $data['notes'] ?? null,
        ]);

        // ✅ فك القفل بعد الانتهاء
        $complaint->locked_by = null;
        $complaint->locked_at = null;
        $complaint->save();

        DB::commit();

        return [
            'status'  => true,
            'message' => 'تم تحديث حالة الشكوى بنجاح.',
            'data'    => [
                'complaint_id' => $complaint->id,
                'new_status'  => $complaint->status,
                'history'     => $history
            ]
        ];

    } catch (\Exception $e) {
        DB::rollBack();

        return [
            'status' => false,
            'message' => 'حدث خطأ أثناء تحديث الشكوى',
            'error' => $e->getMessage()
        ];
    }

}
}