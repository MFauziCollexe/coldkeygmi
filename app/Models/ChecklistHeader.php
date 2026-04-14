<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChecklistHeader extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'entry_code',
        'title',
        'period_type',
        'period_value',
        'area_code',
        'location_code',
        'status',
        'current_step',
        'created_by',
        'approved_by',
        'approved_at',
        'payload_summary_json',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'payload_summary_json' => 'array',
    ];

    public function template()
    {
        return $this->belongsTo(ChecklistTemplate::class, 'template_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function states()
    {
        return $this->hasMany(ChecklistState::class, 'checklist_header_id')->orderBy('version_no');
    }

    public function answers()
    {
        return $this->hasMany(ChecklistAnswer::class, 'checklist_header_id');
    }

    public function scans()
    {
        return $this->hasMany(ChecklistScan::class, 'checklist_header_id');
    }

    public function approvals()
    {
        return $this->hasMany(ChecklistApproval::class, 'checklist_header_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(ChecklistAuditLog::class, 'checklist_header_id');
    }
}
