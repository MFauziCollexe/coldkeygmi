<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeavePermission extends Model
{
    use HasFactory;

    public const CUTI_HAMIL_MIN_DAYS = 80;

    public const CUTI_HAMIL_KEYWORDS = ['hamil', 'maternity', 'melahirkan', 'bersalin', 'pregnancy'];

    protected $fillable = [
        'user_id',
        'employee_id',
        'type',
        'start_date',
        'end_date',
        'days',
        'reason',
        'attachment_image',
        'attachment_images',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'attachment_images' => 'array',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the user who created this request
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who reviewed this request
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Calculate number of days between start and end date
     */
    public static function calculateDays($startDate, $endDate)
    {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $interval = $start->diff($end);
        return $interval->days + 1; // Inclusive
    }

    /**
     * Determine whether a leave request should be treated as maternity leave.
     */
    public static function isCutiHamil(?int $days, ?string $reason): bool
    {
        $normalizedReason = mb_strtolower(trim((string) ($reason ?? '')));

        if ($normalizedReason !== '') {
            foreach (self::CUTI_HAMIL_KEYWORDS as $keyword) {
                if (str_contains($normalizedReason, $keyword)) {
                    return true;
                }
            }
        }

        return (int) ($days ?? 0) >= self::CUTI_HAMIL_MIN_DAYS;
    }

    /**
     * Number of days deducted from the annual leave quota.
     * Maternity leave counts as a single day.
     */
    public static function quotaDaysFor(?int $days, ?string $reason): int
    {
        $days = max(0, (int) ($days ?? 0));

        return self::isCutiHamil($days, $reason) ? 1 : $days;
    }
}
