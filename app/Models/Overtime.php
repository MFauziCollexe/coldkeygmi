<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Overtime extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_id',
        'overtime_date',
        'start_time',
        'end_time',
        'hours',
        'reason',
        'attachment_path',
        'attachment_original_name',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'spv_reviewed_by',
        'spv_reviewed_at',
        'spv_review_notes',
        'manager_reviewed_by',
        'manager_reviewed_at',
        'manager_review_notes',
    ];

    protected $appends = [
        'attachment_url',
    ];

    protected $casts = [
        'overtime_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'reviewed_at' => 'datetime',
        'spv_reviewed_at' => 'datetime',
        'manager_reviewed_at' => 'datetime',
    ];

    /**
     * Get the employee associated with the overtime request.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who created this overtime request
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who reviewed this request
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function spvApprover()
    {
        return $this->belongsTo(User::class, 'spv_reviewed_by');
    }

    public function managerApprover()
    {
        return $this->belongsTo(User::class, 'manager_reviewed_by');
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        $path = trim((string) ($this->attachment_path ?? ''));
        if ($path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * Calculate hours between start and end time
     */
    public static function calculateHours($startTime, $endTime)
    {
        $start = strtotime($startTime);
        $end = strtotime($endTime);
        
        if ($end < $start) {
            // Overnight overtime
            $end += 86400; // Add 24 hours
        }
        
        $diff = $end - $start;
        return round($diff / 3600, 2); // Convert to hours
    }
}
