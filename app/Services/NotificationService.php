<?php

namespace App\Services;
use App\Models\User;
use App\Models\Notification as NotificationModel;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class NotificationService
{

    public function index()
    {
        return auth()->user()->notifications;
    }


    public function send($user, $title, $message, $type = 'basic')
    {
       

        $serviceAccountPath = storage_path('app/firebase.json');

        // Initialize the Firebase Factory with the service account
        $factory = (new Factory)->withServiceAccount($serviceAccountPath);

        // Create the Messaging instance
        $messaging = $factory->createMessaging();

        // Prepare the notification array
        $notification = [
            'title' => $title,
            'body' => $message,
            'sound' => 'default',
        ];

        // Additional data payload
      $data = [
    'type'          => $type,
    'complaint_id'  => $complaint->id ?? null,
    'status'        => $data['status'] ?? null,
    'message'       => $message,
];


        // Create the CloudMessage instance
        $cloudMessage = CloudMessage::withTarget('token', $user['fcm_token'])
            ->withNotification($notification)
            ->withData($data);

        try {
            // Send the notification
            $messaging->send($cloudMessage);

            // Save the notification to the database
            NotificationModel::query()->create([
                'type' => 'App\Notifications\UserFollow',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id' => $user['id'],
                'data' => json_encode([
                    'user' => $user['first_name'] . ' ' . $user['last_name'],
                    'message' => $message,
                    'title' => $title,
                ]), // The data of the notification
            ]);
            return 1;
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            Log::error($e->getMessage());
            return 0;
        } catch (\Kreait\Firebase\Exception\FirebaseException $e) {
            Log::error($e->getMessage());
            return 0;
        }
    }

   public function getCitizenNotifications(User $citizen)
{
    return $citizen->notifications()
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($notification) {
            return [
                // 'id' => $notification->id,
                'data' => $notification->data, // جاهز array
                'created_at' => $notification->created_at->format('Y-m-d '),
            ];
        })
        ->values();
}


}