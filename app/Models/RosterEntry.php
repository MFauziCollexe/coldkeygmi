<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RosterEntry extends Model
{
    protected $fillable = [
        'batch_id',
        'month',
        'year',
        'roster_date',
        'day_name',
        'employee_key',
        'employee_nrp',
        'employee_name',
        'shift_code',
        'is_off',
        'start_time',
        'end_time',
        'work_hours',
        'created_by',
    ];

    protected $casts = [
        'roster_date' => 'date',
        'is_off' => 'boolean',
        'work_hours' => 'decimal:2',
    ];

    public function batch()
    {
        return $this->belongsTo(RosterUploadBatch::class, 'batch_id');
    }
}
