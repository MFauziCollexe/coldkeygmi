<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExitPermit extends Model
{
    protected $fillable = [
        'permit_number',
        'request_date',
        'employee_name',
        'department_name',
        'purpose',
        'time_out',
        'time_back',
        'status',
        'security_status',
        'security_approved_by',
        'security_approved_at',
        'hrd_status',
        'hrd_approved_by',
        'hrd_approved_at',
        'manager_status',
        'manager_approved_by',
        'manager_approved_at',
        'user_id',
        'department_id',
    ];

    protected $casts = [
        'request_date' => 'date',
        'security_approved_at' => 'datetime',
        'hrd_approved_at' => 'datetime',
        'manager_approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function securityApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'security_approved_by');
    }

    public function hrdApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hrd_approved_by');
    }

    public function managerApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_approved_by');
    }
}

