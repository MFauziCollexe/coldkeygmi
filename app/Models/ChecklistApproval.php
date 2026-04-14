<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChecklistApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'checklist_header_id',
        'approval_type',
        'scope_key',
        'notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function header()
    {
        return $this->belongsTo(ChecklistHeader::class, 'checklist_header_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
