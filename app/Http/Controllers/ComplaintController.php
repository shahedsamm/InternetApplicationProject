<?php

namespace App\Http\Controllers;

use App\Http\Requests\Complaint\IndexRequest;
use App\Http\Requests\Complaint\StoreRequest;
use App\Http\Requests\Complaint\UpdateRequest;
use App\Http\Requests\Complaint\ExportRequest;
use App\Http\Requests\Complaint\StatsRequest;
use App\Http\Responses\Response;
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
}
