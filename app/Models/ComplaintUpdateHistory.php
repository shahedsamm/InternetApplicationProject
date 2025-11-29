<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintUpdateHistory extends Model
{
    protected $fillable = [
        'complaint_id','employee_id','followup_id','status','notes','title'
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function followup(): BelongsTo
    {
        return $this->belongsTo(ComplaintFollowup::class);
    }
}
