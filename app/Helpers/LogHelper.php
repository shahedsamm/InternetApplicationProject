<?php

namespace App\Helpers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class LogHelper
{
    /**
     * سجّل عملية على الشكوى
     *
     * @param string $action نوع العملية: created, updated, deleted, status_changed
     * @param \App\Models\Complaint $complaint الشكوى المعنية
     * @param array|null $changes التغييرات (اختياري)
     */
    public static function complaint(string $action, $complaint, ?array $changes = null)
    {
        $user = Auth::user(); // المستخدم الحالي
        if (!$user) return;

        // تحديد نوع المستخدم بدقة
        $userType = 'unknown';
        if ($user->hasRole('admin')) {
            $userType = 'admin';
        } elseif ($user->hasRole('employee')) {
            $userType = 'employee';
        } elseif ($user->hasRole('citizen')) {
            $userType = 'citizen';
        }

         AuditLog::create([
            'complaint_id'  => $complaint->id, // ⬅️ العمود الصحيح
            'user_id'       => $user->id,
            'user_type'     => $userType,
            'action'        => $action,
            'changes'       => $changes ? json_encode($changes, JSON_UNESCAPED_UNICODE) : null,
            'ip_address'    => request()->ip(),
            'user_agent'    => request()->userAgent(),
            'created_at'    => now(),
        ]);
    }
}
