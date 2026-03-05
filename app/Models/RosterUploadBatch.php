<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Department;
use App\Models\User;

class RosterUploadBatch extends Model
{
    protected $fillable = [
        'month',
        'year',
        'revision_of_batch_id',
        'version',
        'filename',
        'source_file_path',
        'delimiter',
        'uploaded_by',
        'department_id',
        'total_rows',
        'saved_rows',
        'status',
        'is_current',
        'approved_by',
        'approved_at',
        'draft_payload_path',
        'reject_reason',
        'change_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'is_current' => 'boolean',
    ];

    public function entries()
    {
        return $this->hasMany(RosterEntry::class, 'batch_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function revisionOf()
    {
        return $this->belongsTo(self::class, 'revision_of_batch_id');
    }

    public function revisions()
    {
        return $this->hasMany(self::class, 'revision_of_batch_id');
    }
}
