<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendancePresence extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_id',
        'attendance_lock_area_id',
        'roster_entry_id',
        'attendance_date',
        'shift_source',
        'shift_name',
        'shift_start_time',
        'shift_end_time',
        'is_off',
        'holiday_name',
        'check_in_at',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_accuracy',
        'check_in_area_name',
        'check_in_photo_path',
        'check_out_at',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_accuracy',
        'check_out_area_name',
        'check_out_reason',
        'check_out_photo_path',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'is_off' => 'boolean',
        'check_in_latitude' => 'float',
        'check_in_longitude' => 'float',
        'check_in_accuracy' => 'float',
        'check_out_latitude' => 'float',
        'check_out_longitude' => 'float',
        'check_out_accuracy' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function lockArea()
    {
        return $this->belongsTo(AttendanceLockArea::class, 'attendance_lock_area_id');
    }

    public function rosterEntry()
    {
        return $this->belongsTo(RosterEntry::class);
    }
}
