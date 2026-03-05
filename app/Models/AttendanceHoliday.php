<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceHoliday extends Model
{
    use HasFactory;

    protected $table = 'attendance_holidays';

    protected $fillable = [
        'holiday_date',
        'name',
        'scope_type',
        'notes',
        'is_national',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
            'is_national' => 'boolean',
        ];
    }
}
