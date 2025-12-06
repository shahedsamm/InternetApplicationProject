<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\ComplaintUpdateHistory;
use App\Http\Requests\Employee\UpdateComplaintStatusRequest;

class EmployeeComplaintService
{
     public function getComplaintsForEmployeeDepartment($employee)
{
    $departmentId = $employee->section; // ✅ القسم الصحيح

    $complaints = Complaint::whereHas('updateHistories', function ($q) use ($departmentId) {
        $q->whereHas('employee', function ($qq) use ($departmentId) {
            $qq->where('section', $departmentId);
        });
    })
    ->with(['updateHistories' => function ($q) {
        $q->latest()->limit(1);
    }])
    ->latest()
    ->get()
    ->map(function ($c) {
        $last = $c->updateHistories->first();

        return [
            'serial_number' => $c->serial_number,
            'type'          => $c->type,
            'section'       => $c->section,
            'status'        => $last?->status,
            'last_note'     => $last?->notes,
            'updated_at'    => optional($last)->created_at?->format('Y-m-d H:i'),
        ];
    });

    return [
        'status' => true,
        'data'   => $complaints
    ];
}
     public function updateStatus($employee, $data)
    {
        $complaint = Complaint::find($data['complaint_id']);

        if (!$complaint) {
            return [
                'status' => false,
                'message' => 'الشكوى غير موجودة.'
            ];
        }

        // ✅ تحديث حالة الشكوى الأساسية فقط
        $complaint->status = $data['status'];
        $complaint->save();

        // ✅ حفظ السجل في history
        $history = ComplaintUpdateHistory::create([
            'complaint_id' => $complaint->id,
            'employee_id'  => $employee->id,
            'followup_id'  => $data['followup_id'] ?? null,
            'status'       => $data['status'],
            'title'        => $data['title'],
            'notes'        => $data['notes'] ?? null,
        ]);

        return [
            'status'  => true,
            'message' => 'تم تحديث حالة الشكوى بنجاح.',
            'data'    => [
                'complaint_id' => $complaint->id,
                'new_status'  => $complaint->status,
                'history'     => $history
            ]
        ];
    }
}
