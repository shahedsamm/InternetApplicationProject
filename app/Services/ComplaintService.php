<?php

namespace App\Services;
use App\Models\Complaint;
use App\Models\ComplaintUpdateHistory;
use App\Models\ComplaintFollowup;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ComplaintUpdatedNotification;



class ComplaintService
{
    public function list(array $filters, $user)
    {
        $query = Complaint::with(['histories', 'followups']);

        // Citizen sees only his complaints
        if ($user->role == 'citizen') {
            $query->where('citizen_id', $user->id);
        }

        // Employee sees only his section
        if ($user->role == 'employee') {
            $query->where('section', $user->section);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data, $user)
    {
        $data['citizen_id'] = $user->id;

        $complaint =  Complaint::create($data);

        // Store attachments
        $this->handleMedia($complaint, $data);

        return $complaint;
    }

    public function show(int $id, $user)
    {
        $query = Complaint::with(['histories', 'followups']);

        // Restrict access
        if ($user->role == 'citizen') {
            $query->where('citizen_id', $user->id);
        }

        if ($user->role == 'employee') {
            $query->where('section', $user->section);
        }

        return $query->findOrFail($id);
    }

    public function update(int $id, array $data, $user)
    {
        return DB::transaction(function () use ($id, $data, $user) {

            $complaint = Complaint::lockForUpdate()->find($id);

            if (!$complaint) {
                abort(404, 'Complaint not found.');
            }

            // LOCK CHECK: If status is pending or done → cannot update
            if ($complaint->locked == 1) {
                abort(401, 'This record is currently locked by another employee.');
            }

            // Lock the record
            $complaint->update(['locked' => 1]);

            $followups = $data['followups'] ?? [];
            unset($data['followups']);
            // Update complaint
            $complaint->update($data);

            // Create complaint history
            $history = ComplaintUpdateHistory::create([
                'complaint_id' => $complaint->id,
                'employee_id'  => $user->id,
                'followup_id'  => null,
                'status'       => $complaint->status,
                'title'        => $data['title'] ?? 'Updated Complaint',
                'notes'        => $data['notes'] ?? null,
            ]);

            // Create followup
            if (!empty($followups)) {
                foreach ($followups as $followup) {
                    $followup = ComplaintFollowup::create([
                        'complaint_id' => $complaint->id,
                        'title' => $followup['title'],
                        'description' => $followup['description'],
                        'requested_by' => auth()->id(),
                    ]);

                    $history->update(['followup_id' => $followup->id]);
                }
            }

            // Store attachments
            $this->handleMedia($complaint, $data);

            // Unlock record
            $complaint->update(['locked' => 0]);

            // Send user notification
            Notification::send(
                $complaint->citizen,
                new ComplaintUpdatedNotification($complaint)
            );

            return $complaint->fresh(['histories', 'followups']);
        });
    }

    public function statistics(array $filters)
    {
        $start = $filters['start_date'] ?? now()->subWeek();
        $end   = $filters['end_date'] ?? now();

        return Complaint::selectRaw('status, COUNT(*) as total')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('status')
            ->pluck('total', 'status');
    }

    public function export(array $filters)
    {
        $start = $filters['start_date'] ?? now()->subWeek();
        $end   = $filters['end_date'] ?? now();

        $complaints = Complaint::whereBetween('created_at', [$start, $end])->get();

        $filename = 'complaints_export_' . now()->timestamp . '.csv';
        $path = storage_path("app/$filename");

        $file = fopen($path, 'w');

        fputcsv($file, ['Serial', 'Citizen', 'Type', 'Section', 'Status', 'Created']);

        foreach ($complaints as $c) {
            fputcsv($file, [
                $c->serial_number,
                $c->citizen_id,
                $c->type,
                $c->section,
                $c->status,
                $c->created_at,
            ]);
        }

        fclose($file);

        return response()->download($path, $filename)->deleteFileAfterSend();
    }

    private function handleMedia($complaint, $data)
    {
        $complaint->clearMediaCollection('attachments');
        // Handle images
        $mediaJsonArray = [];
        if (isset($data['attachments'])) {
            foreach ($data['attachments'] as $image) {
                $media = $complaint->addMedia($image)
                    ->toMediaCollection('attachments');

                $mediaJsonArray[] = $media->getUrl();
            }
            // Save media JSON to complaint
            $complaint->attachments = $mediaJsonArray;
            $complaint->save();
        }
    }


 public function storeComplaint($citizen, $data)
{
    // التأكد من أن المستخدم لديه دور citizen
    if (!$citizen->hasRole('citizen')) {
        $citizen->assignRole('citizen');
    }

    // إنشاء الشكوى
    $complaint = Complaint::create([
        'citizen_id'  => $citizen->id,
        'type'        => $data['type'],
        'section'     => $data['section'],
        'location'    => $data['location'],
        'description' => $data['description'],
        'status'      => 'new',
        'national_id' => $data['national_id'],
    ]);

    $uploadedFileNames = [];

    // رفع الملفات إذا موجودة
    if (!empty($data['attachments'])) {
        foreach ($data['attachments'] as $file) {
            $media = $complaint->addMedia($file)
                ->toMediaCollection('attachments');

            // حفظ اسم الملف فقط
            $uploadedFileNames[] = $media->file_name;
        }
    }

    return [
        'status'    => true,
        'complaint' => $complaint,
        // 'files'     => $uploadedFileNames, // ⬅ هنا اسماء الملفات
    ];
}


public function updateComplaint($citizen, $complaintId, $data)
{
    $complaint = Complaint::where('id', $complaintId)
        ->where('citizen_id', $citizen->id)
        ->first();

    if (!$complaint) {
        return [
            'status' => false,
            'message' => 'الشكوى غير موجودة أو لا تخصك'
        ];
    }

    // ✅ الحقول المسموح تعديلها
    $fields = ['type', 'section', 'location', 'description', 'national_id','attachment'];

    $originalData = $complaint->only($fields);
    $newData = array_intersect_key($data, array_flip($fields));

    // ✅ استخراج التغييرات فقط
    $changes = [];

    foreach ($newData as $key => $value) {
        if ((string) ($originalData[$key] ?? '') !== (string) $value) {
            $changes[$key] = [
                'old' => $originalData[$key] ?? null,
                'new' => $value
            ];
        }
    }

    if (empty($changes)) {
        return [
            'status' => false,
            'message' => 'لم يتم إجراء أي تعديل فعلي.'
        ];
    }

    // ✅ حفظ التعديل في complaint_followups
    $followup = ComplaintFollowup::create([
        'complaint_id' => $complaint->id,
        'title'        => 'طلب تعديل من المواطن',
        'description'  => json_encode($changes, JSON_UNESCAPED_UNICODE),
        'requested_by' => $citizen->id,
    ]);
    // ✅ إضافة ملفات جديدة في حال تم إرسالها
if (!empty($data['attachments'])) {
    foreach ($data['attachments'] as $file) {
        $complaint->addMedia($file)
            ->toMediaCollection('attachments');
    }
}


    // ✅ نسخة "بعد التعديل" للعرض فقط
    $after = $originalData;
    foreach ($changes as $key => $val) {
        $after[$key] = $val['new'];
    }

    $complaintFull = $complaint->toArray();
    foreach ($after as $key => $val) {
        $complaintFull[$key] = $val;
    }

    return [
        'status'  => true,
        'message' => 'تم حفظ طلب التعديل بنجاح.',
        'complaint_after' => $complaintFull,
    ];
}


    
     public function listComplaints($citizen)
{
    $complaints = Complaint::with(['followups' => function ($q) {
        $q->latest();
    }])
    ->where('citizen_id', $citizen->id)
    ->orderBy('created_at', 'desc')
    ->get();

    $result = $complaints->map(function ($complaint) {

        $original = [
            'type'        => $complaint->type,
            'section'     => $complaint->section,
            'location'    => $complaint->location,
            'description' => $complaint->description,
            'status'      => $complaint->status,
        ];

        // ✅ نسخة افتراضية = الأصل
        $after = $original;

        $updatedBy = null;
        $updatedAt = null;

        // ✅ إذا وجد تعديل
        if ($complaint->followups->count()) {

            $latestFollowup = $complaint->followups->first();
            $changes = json_decode($latestFollowup->description, true);

            // ✅ نطبق التعديلات على نسخة "after"
            foreach ($changes as $field => $change) {
                $after[$field] = $change['new'];
            }

            $updatedBy = $latestFollowup->requested_by;
            $updatedAt = $latestFollowup->created_at;
        }

        return [
            'id'            => $complaint->id,
            // 'before_update'=> $original,
            'after_update' => $after,
            // 'updated_by'   => $updatedBy,
            'updated_at'   => $updatedAt,
        ];
    });

    return [
        'status'     => true,
        'complaints' => $result
    ];
}




public function trackComplaint($serial, $userId)
{
    $complaint = Complaint::with(['updateHistories' => fn($q) => $q->latest()])
        ->where('serial_number', $serial)
        ->first();

    if (!$complaint) {
        return [
            'status' => false,
            'code' => 404,
            'message' => 'الشكوى غير موجودة.',
            'data' => null
        ];
    }

    if ($complaint->citizen_id !== $userId) {
        return [
            'status' => false,
            'code' => 403,
            'message' => 'لا تملك صلاحية لعرض هذه الشكوى.',
            'data' => null
        ];
    }

    $lastHistory = $complaint->updateHistories->first();

    return [
        'status' => true,
        'code' => 200,
        'message' => 'تم جلب حالة الشكوى بنجاح',
        'data' => [
            'complaint' => [
                'type'    => $complaint->type,
                'section' => $complaint->section,
                'status'  => $complaint->status,
            ],
            'last_admin_note' => $lastHistory ? $lastHistory->notes : null,
        ]
    ];
}

 


}
