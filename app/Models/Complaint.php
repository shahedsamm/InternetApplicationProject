<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Complaint extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = [];
    


    protected $casts = [
        'attachments' => 'array',
        'locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();
        // when creating a record, generate the serial number
        static::creating(function ($model) {
            if (empty($model->serial_number)) {
                $model->serial_number = self::generateSerialNumber();
            }
        });
    }

    public static function generateSerialNumber(): string
    {
        // Example: CMP-20251115-000123
        $date = now()->format('Ymd');
        $next = str_pad((string) (self::max('id') + 1 ?: 1), 6, '0', STR_PAD_LEFT);
        return 'CMP-' . $date . '-' . $next;
    }

    // relations
    public function citizen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'citizen_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ComplaintUpdateHistory::class);
    }

    public function followups(): HasMany
    {
        return $this->hasMany(ComplaintFollowup::class);
    }
     public function updateHistories()
    {
        return $this->hasMany(ComplaintUpdateHistory::class); // بدل الاسم حسب جدولك
    }

}
