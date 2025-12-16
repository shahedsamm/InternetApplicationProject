<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ComplaintStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(
        public $complaint,
        public $employee,
        public $status
    ) {}

    // التخزين فقط في DB
    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'complaint_id'   => $this->complaint->id,
            'serial_number'  => $this->complaint->serial_number,
            'status'         => $this->status,
            'employee_name'  => $this->employee->name,
            'message'        => 'تم تحديث حالة شكوى',
        ];
    }
}
