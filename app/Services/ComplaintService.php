<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\ComplaintUpdateHistory;
use App\Models\ComplaintFollowup;
use Illuminate\Support\Facades\DB;
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
}
