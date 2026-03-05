<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitorForm extends Model
{
    protected $fillable = [
        'visitor_name',
        'from',
        'identity_no',
        'purpose',
        'appointment_time',
        'host_name',
        'host_user_id',
        'visit_date',
        'visit_time',
        'check_out',
        'status',
        'approval_status',
        'security_approved_by',
        'security_approved_at',
        'host_approved_by',
        'host_approved_at',
        'user_id',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'security_approved_at' => 'datetime',
        'host_approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hostUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    public function securityApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'security_approved_by');
    }

    public function hostApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_approved_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(VisitorFormAttachment::class);
    }
}
