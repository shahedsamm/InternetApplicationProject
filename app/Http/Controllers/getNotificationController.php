<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Citizen\GetNotificationsRequest;
use App\Services\NotificationService;

class getNotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

   public function index(GetNotificationsRequest $request)
{
    $user = auth()->user();

    $notifications = $this->notificationService->getCitizenNotifications($user);

    return response()->json([
        'status' => true,
        'data' => $notifications
    ]);
}

}
