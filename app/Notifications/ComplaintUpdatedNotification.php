<?php

namespace App\Notifications;

use App\Models\Complaint;
use App\Models\ComplaintFollowup;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ComplaintUpdatedNotification extends Notification
{
    use Queueable;

    protected $complaint;
    protected $followup;

    public function __construct(Complaint $complaint, ?ComplaintFollowup $followup = null)
    {
        $this->complaint = $complaint;
        $this->followup = $followup;
    }

    public function via($notifiable)
    {
        // you must configure fcm channel or use custom implementation
        return ['mail', 'database', 'broadcast']; // add 'fcm' if you have a channel
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("Update on your complaint {$this->complaint->serial_number}")
            ->line("Status: {$this->complaint->status}")
            ->line("Title: " . ($this->followup ? $this->followup->title : 'Update'))
            ->action('View Complaint', url("/complaints/{$this->complaint->id}"))
            ->line('Thank you for using our service.');
    }

    public function toArray($notifiable)
    {
        return [
            'complaint_id' => $this->complaint->id,
            'serial_number' => $this->complaint->serial_number,
            'status' => $this->complaint->status,
            'followup_id' => $this->followup ? $this->followup->id : null,
        ];
    }

    // if you have an FCM channel, implement toFcm() or toPush() accordingly
}
