<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChecklistMedia extends Model
{
    protected $fillable = [
        'checklist_header_id',
        'media_type',
        'scope_key',
        'path',
        'url',
        'original_name',
        'sort_order',
    ];

    public function checklistHeader()
    {
        return $this->belongsTo(ChecklistHeader::class, 'checklist_header_id');
    }
}
