<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChecklistState extends Model
{
    use HasFactory;

    protected $fillable = [
        'checklist_header_id',
        'version_no',
        'state_json',
        'saved_by',
        'saved_at',
    ];

    protected $casts = [
        'version_no' => 'integer',
        'state_json' => 'array',
        'saved_at' => 'datetime',
    ];

    public function header()
    {
        return $this->belongsTo(ChecklistHeader::class, 'checklist_header_id');
    }

    public function saver()
    {
        return $this->belongsTo(User::class, 'saved_by');
    }
}
