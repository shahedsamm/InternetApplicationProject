<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests\Complaint\IndexRequest;
use App\Http\Requests\Complaint\StoreRequest;
use App\Http\Requests\Complaint\UpdateRequest;
use App\Http\Requests\Complaint\ExportRequest;
use App\Http\Requests\Citizen\CreateComplaintRequest;
use App\Http\Requests\Citizen\UpdateComplaintRequest;
use App\Http\Requests\Citizen\TrackComplaintRequest;
use App\Http\Requests\Complaint\StatsRequest;
use App\Http\Responses\Response;
use App\Helpers\DateHelper;
use App\Models\Complaint;
use Illuminate\Support\Facades\Validator;
use App\Services\ComplaintService;
use Illuminate\Http\JsonResponse;
use Throwable;

class ComplaintController extends Controller
{
    private ComplaintService $service;

    public function __construct(ComplaintService $service)
    {
        $this->service = $service;
    }

    public function index(IndexRequest $request): JsonResponse
    {
        $data = [];
        try {
            $data = $this->service->list($request->validated(), $request->user());
            return Response::Success($data, __('complaint.index'));
        } catch (Throwable $th) {
            activity('Error: Complaint Index')->log($th);
            return Response::Error($data, $th->getMessage());
        }
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $data = [];
        try {
            $data = $this->service->create($request->validated(), $request->user());
            return Response::Success($data, __('complaint.created'));
        } catch (Throwable $th) {
            activity('Error: Complaint Store')->log($th);
            return Response::Error($data, $th->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        $data = [];
        try {
            $data = $this->service->show($id, auth()->user());
            return Response::Success($data, __('complaint.found'));
        } catch (Throwable $th) {
            activity('Error: Complaint Show')->log($th);
            return Response::Error($data, $th->getMessage());
        }
    }

    public function update(UpdateRequest $request, int $id): JsonResponse
    {
        $data = [];
        try {
            $data = $this->service->update($id, $request->validated(), $request->user());
            return Response::Success($data, __('complaint.updated'));
        } catch (Throwable $th) {
            activity('Error: Complaint Update')->log($th);
            return Response::Error($data, $th->getMessage(), $th->getCode() == 401 ? 401 : 500);
        }
    }

    public function stats(StatsRequest $request): JsonResponse
    {
        $data = [];
        try {
            $data = $this->service->statistics($request->validated());
            return Response::Success($data, __('complaint.stats'));
        } catch (Throwable $th) {
            activity('Error: Complaint Stats')->log($th);
            return Response::Error($data, $th->getMessage());
        }
    }

    public function export(ExportRequest $request)
    {
        try {
            return $this->service->export($request->validated());
        } catch (Throwable $th) {
            activity('Error: Complaint Export')->log($th);
            return Response::Error([], $th->getMessage());
        }
    }


     public function storeComplaint(CreateComplaintRequest  $request)
{
    return $this->service->storeComplaint(auth()->user(), $request->validated());
}



public function  updateComplaint(UpdateComplaintRequest $request, $complaintId)
{
    $citizen = auth()->user();

    $result = $this->service->updateComplaint(
        $citizen,
        $complaintId,
        $request->validated()
    );

    return response()->json($result);
}



    public function deleteComplaint($id)
    {
        $citizen = auth()->user();
        $result = $this->service->deleteComplaint($citizen, $id);

        return response()->json($result, $result['status'] ? 200 : 422);
    }

    public function listComplaints()
    {
        $citizen = auth()->user();
        $result = $this->service->listComplaints($citizen);

        return response()->json($result, 200);
    }

public function trackComplaint(TrackComplaintRequest $request, string $serial_number)
{
    $userId = auth()->id();      // المستخدم الحالي
    $serial = $serial_number;   // الرقم المرجعي من الـ URL

    $result = $this->service->trackComplaint($serial, $userId);

    return response()->json($result);
}


  
public function showComplaint($id)
{
    $complaint = Complaint::with([
        'media',
        'updateHistories.employee'
    ])->findOrFail($id);

    return [
        'status' => true,
        'data' => [
            'id'            => $complaint->id,
            'citizen_id'    => $complaint->citizen_id,
            'type'          => $complaint->type,
            'section'       => $complaint->section,
            'location'      => $complaint->location,
            'national_id'   => $complaint->national_id,
            'description'   => $complaint->description,
            'serial_number' => $complaint->serial_number,
            'status'        => $complaint->status,
            'created_at'    => \App\Helpers\DateHelper::arabicDate($complaint->created_at),

            // ⭐ الملفات
            'attachments' => $complaint->getAttachmentsUrls(),

            // ⭐ جميع ملاحظات الموظف المسؤول
            'employee_notes' => $complaint->updateHistories->map(function ($h) {
                return [
                    'status'     => $h->status,
                    'notes'      => $h->notes,
                    'employee'   => $h->employee?->name,
                    'created_at' => $h->created_at->format('Y-m-d '),
                ];
            }),

            // ⭐ آخر ملاحظة فقط
            'last_employee_note' => optional($complaint->updateHistories->first())->notes,
        ]
    ];
}

}
