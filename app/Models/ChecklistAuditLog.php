<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChecklistAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'checklist_header_id',
        'action',
        'old_value_json',
        'new_value_json',
        'actor_id',
        'logged_at',
    ];

    protected $casts = [
        'old_value_json' => 'array',
        'new_value_json' => 'array',
        'logged_at' => 'datetime',
    ];

    public function header()
    {
        return $this->belongsTo(ChecklistHeader::class, 'checklist_header_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
