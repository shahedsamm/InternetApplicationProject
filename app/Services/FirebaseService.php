<?php

namespace App\Services;

class FirebaseNotificationService
{
    public function send($token, $title, $body)
    {
        $SERVER_API_KEY = env('FIREBASE_SERVER_KEY');

        $data = [
            "to" => $token,
            "notification" => [
                "title" => $title,
                "body"  => $body,
                "sound" => "default"
            ]
        ];

        $headers = [
            'Authorization: key=' . $SERVER_API_KEY,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}
