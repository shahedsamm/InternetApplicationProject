<?php

namespace App\Services;

use App\Models\User;
use App\Helpers\DateHelper;
use App\Models\Complaint;
use App\Helpers\LogHelper;
use App\Models\ComplaintUpdateHistory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Notifications\ComplaintStatusUpdated;
use App\Services\NotificationService;


class EmployeeAuthService
{

     public function __construct(
        protected NotificationService $notificationService
    ){}

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

    // ✅ تسجيل عملية العرض
    LogHelper::complaint('viewed', $c);

    return [
       'id'            => $c->id,   
        'serial_number' => $c->serial_number,
        'type'          => $finalType,
        'description'   => $finalDescription,
        'location'      => $finalLocation,
        'section'       => $c->section,
        'status'        => $lastEmployeeUpdate?->status ?? $c->status,
        'last_employee_note' => $lastEmployeeUpdate?->notes,
        'created_at'    => DateHelper::arabicDate($c->created_at),
        'updated_at'    => optional($lastEmployeeUpdate ?? $lastCitizenUpdate)->created_at?->format('Y-m-d H:i'),
        'attachments'   => $c->media->map(function ($m) {
            return [
                'file_name' => $m->file_name,
                'url'       => url("storage/{$m->id}/{$m->file_name}"),
                'size'      => $m->size,
                'mime_type' => $m->mime_type,
            ];
        }),
    ];
});



    return [
        'status' => true,
        'data'   => $complaints
    ];
}



 public function update(int $id, array $data, $employee)
    {
        return \DB::transaction(function () use ($id, $data, $employee) {
            $complaint = Complaint::lockForUpdate()->find($id);

            if (!$complaint) {
                abort(404, 'الشكوى غير موجودة.');
            }

            if ($complaint->locked_by !== $employee->id) {
                abort(403, 'لا يمكنك تعديل هذه الشكوى لأنها محجوزة لموظف آخر.');
            }

            // تحديث بيانات الشكوى
            $followups = $data['followups'] ?? [];
            unset($data['followups']);

            $complaint->update($data);

            // سجل التعديل
            $history = ComplaintUpdateHistory::create([
                'complaint_id' => $complaint->id,
                'employee_id'  => $employee->id,
                'status'       => $complaint->status,
                'title'        => $data['title'] ?? 'تم تعديل الشكوى',
                'notes'        => $data['notes'] ?? null,
            ]);

            // followups
            if (!empty($followups)) {
                foreach ($followups as $followupData) {
                    $followup = ComplaintFollowup::create([
                        'complaint_id' => $complaint->id,
                        'title'        => $followupData['title'],
                        'description'  => $followupData['description'] ?? null,
                        'requested_by' => $employee->id,
                    ]);
                    $history->update(['followup_id' => $followup->id]);
                }
            }

            // فك القفل بعد التعديل
            $complaint->update([
                'locked'    => 0,
                'locked_by' => null,
                'locked_at' => null,
            ]);

            // إرسال إشعار للمواطن
            if ($complaint->citizen) {
                $this->notificationService->send(
                    $complaint->citizen,
                    'تحديث حالة الشكوى',
                    'تم تحديث شكواك رقم ' . $complaint->serial_number,
                    'complaint_status'
                );
            }

            return $complaint->fresh(['histories', 'followups']);
        });
    }


    /**
     * حفظ الملفات داخل Media Library
     */
    protected function handleMedia($complaint, $data)
    {
        if (!empty($data['attachments'])) {
            foreach ($data['attachments'] as $file) {
                $complaint->addMedia($file)
                    ->toMediaCollection('attachments');
            }
        }
    }


 public function reserveComplaint(int $complaintId, $employee)
    {
        $complaint = Complaint::find($complaintId);

        if (!$complaint) {
            abort(404, 'الشكوى غير موجودة.');
        }

        if ($complaint->locked) {
            return [
                'status' => false,
                'message' => 'هذه الشكوى مقفولة حالياً من قبل موظف آخر.'
            ];
        }

        $complaint->update([
            'locked'    => 1,
            'locked_by' => $employee->id,
            'locked_at' => now(),
        ]);

        return [
            'status'    => true,
            'message'   => 'تم حجز الشكوى بنجاح.',
            'complaint' => $complaint->fresh()
        ];
    }






 public function cancelReservation(int $complaintId, $employee)
    {
        $complaint = Complaint::find($complaintId);

        if (!$complaint) {
            abort(404, 'الشكوى غير موجودة.');
        }

        if ($complaint->locked_by !== $employee->id) {
            return [
                'status' => false,
                'message' => 'لا يمكنك إلغاء حجز هذه الشكوى، لأنها ليست محجوزة لك.'
            ];
        }

        $complaint->update([
            'locked'    => 0,
            'locked_by' => null,
            'locked_at' => null,
        ]);

        return [
            'status'  => true,
            'message' => 'تم إلغاء الحجز بنجاح.'
        ];
    }


//  public function updateStatus($employee, $data)
// {
//     // ✅ 1️⃣ فك الأقفال المنتهية
//     Complaint::whereNotNull('locked_by')
//         ->where('locked_at', '<=', now()->subMinutes(10))
//         ->update([
//             'locked_by' => null,
//             'locked_at' => null,
//         ]);

//     // ✅ 2️⃣ جلب الشكوى
//     $complaint = Complaint::find($data['complaint_id']);

//     if (!$complaint) {
//         return [
//             'status' => false,
//             'message' => 'الشكوى غير موجودة.'
//         ];
//     }

//     // ✅ 3️⃣ نفس القسم
//     if ($complaint->section !== $employee->section) {
//         return [
//             'status' => false,
//             'message' => 'لا يمكنك التعديل على شكوى من قسم آخر.'
//         ];
//     }

//     // ✅ 4️⃣ التحقق من القفل
//     if (
//         $complaint->locked_by !== null &&
//         $complaint->locked_by !== $employee->id &&
//         $complaint->locked_at > now()->subMinutes(10)
//     ) {
//         return [
//             'status' => false,
//             'message' => 'الشكوى مقفولة حالياً من قبل موظف آخر.'
//         ];
//     }

//     // ✅ 5️⃣ قفل الشكوى
//     $complaint->locked_by = $employee->id;
//     $complaint->locked_at = now();

//     // ✅ 6️⃣ تحديث الحالة
//     $complaint->status = $data['status'];
//     $complaint->save();

//     // ✅ 7️⃣ حفظ سجل التعديل
//     $history = ComplaintUpdateHistory::create([
//         'complaint_id' => $complaint->id,
//         'employee_id'  => $employee->id,
//         'status'       => $data['status'],
//         'notes'        => $data['notes'] ?? null,
//     ]);



//   $citizen = $complaint->citizen;

// if ($citizen) {

//     // 🔔 1️⃣ تخزين الإشعار في DB
//     $citizen->notify(
//         new ComplaintStatusUpdated(
//             $complaint,
//             $employee,
//             $data['status']
//         )
//     );

//     // 📡 2️⃣ إرسال Push Notification
//     $this->notificationService->send(
//         $citizen,
//         'تحديث حالة الشكوى',
//         'تم تغيير حالة شكواك رقم ' . $complaint->serial_number .
//         ' إلى الحالة: ' . $data['status'],
//         'complaint_status'
//     );
// }


// $changes = [
//     'before_status' => $complaint->getOriginal('status'), // الحالة قبل التعديل
//     'after_status'  => $data['status'], // الحالة بعد التعديل
// ];
// LogHelper::complaint('status_changed', $complaint, $changes);

//     return [
//         'status'  => true,
//         'message' => 'تم تحديث حالة الشكوى بنجاح.',
//         'data'    => [
//             'complaint_id'  => $complaint->id,
//             'new_status'   => $complaint->status,
//             'locked_until' => now()->addMinutes(10)->format('Y-m-d '),
//             'history'      => $history
//         ]
//     ];
// }

}