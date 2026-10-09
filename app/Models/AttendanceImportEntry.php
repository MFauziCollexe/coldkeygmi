<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceImportEntry extends Model
{
    protected $fillable = [
        'batch_id',
        'attendance_date',
        'pin',
        'name',
        'check_in',
        'check_out',
        'status',
        'is_off',
        'leave_type',
        'shift_code',
        'schedule_start',
        'schedule_end',
        'overtime_label',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'is_off' => 'boolean',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(AttendanceImportBatch::class, 'batch_id');
    }

    public function scopeForPeriod(Builder $query, ?string $month, ?string $year): Builder
    {
        if ($month !== null && $year !== null) {
            $first = sprintf('%04d-%02d-01', (int) $year, (int) $month);
            $last = \Illuminate\Support\Carbon::createFromDate((int) $year, (int) $month, 1)
                ->endOfMonth()
                ->format('Y-m-d');

            return $query->whereBetween('attendance_date', [$first, $last]);
        }

        return $query;
    }

    protected function attendanceDateLabel(): Attribute
    {
        return Attribute::make(
            get: fn ($value, array $attributes) => isset($attributes['attendance_date'])
                ? \Illuminate\Support\Carbon::parse($attributes['attendance_date'])->format('d M Y')
                : ($value ?? ''),
        );
    }
}