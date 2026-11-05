<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLogCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_group_id',
        'log_date',
        'pin',
        'corrected_first_scan',
        'corrected_last_scan',
        'note',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejected_at',
        'rejection_reason',
        'approval_stage',
        'spv_reviewed_by',
        'spv_reviewed_at',
        'spv_review_notes',
        'manager_reviewed_by',
        'manager_reviewed_at',
        'manager_review_notes',
    ];

    protected $casts = [
        'log_date' => 'date:Y-m-d',
        'corrected_first_scan' => 'datetime',
        'corrected_last_scan' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'spv_reviewed_at' => 'datetime',
        'manager_reviewed_at' => 'datetime',
    ];
}
