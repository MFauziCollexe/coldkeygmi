<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChecklistScan extends Model
{
    use HasFactory;

    protected $fillable = [
        'checklist_header_id',
        'scan_scope',
        'scope_key',
        'barcode_value',
        'scan_date',
        'scanned_by',
    ];

    protected $casts = [
        'scan_date' => 'datetime',
    ];

    public function header()
    {
        return $this->belongsTo(ChecklistHeader::class, 'checklist_header_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }
}
