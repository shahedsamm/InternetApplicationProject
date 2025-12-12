<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'complaint_id',
        'user_id',
        'user_type',
        'action',
        'changes',
        'ip',
        'user_agent'
    ];

    protected $casts = [
        'changes' => 'array'
    ];

     public function complaint()
    {
        return $this->belongsTo(Complaint::class, 'complaint_id');
    }
}

