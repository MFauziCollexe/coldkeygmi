<?php

namespace App\Models;

use App\Support\AccessRuleService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestAccess extends Model
{
    use HasFactory;

    private const ACCESS_MODULE = 'request_access';

    protected $fillable = [
        'request_number',
        'type',
        'user_id',
        'target_user_name',
        'target_user_email',
        'target_department_id',
        'module_keys',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'processed_by',
        'processed_at',
        'processing_notes',
        'created_by',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'module_keys' => 'array',
    ];

    /**
     * Get module keys as array (for backward compatibility)
     */
    public function getModuleKeysAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }
        return json_decode($value, true) ?? [];
    }

    /**
     * Set module keys from array
     */
    public function setModuleKeysAttribute($value)
    {
        $this->attributes['module_keys'] = json_encode($value);
    }

    /**
     * Get the user who made the request (for existing_user type)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the target department (for new_user type)
     */
    public function targetDepartment()
    {
        return $this->belongsTo(Department::class, 'target_department_id');
    }

    /**
     * Get the manager who reviewed this request
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the IT staff who processed this request
     */
    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Get the creator of this request
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if current user is the creator of this request
     */
    public function isCreator($userId)
    {
        return $this->created_by == $userId;
    }

    /**
     * Check if current user is the manager who should review this request
     */
    public function canReview($userId)
    {
        $targetDepartmentId = (int) optional($this->creator)->department_id;
        if ($targetDepartmentId <= 0 && $this->created_by) {
            $targetDepartmentId = (int) User::where('id', $this->created_by)->value('department_id');
        }

        return app(AccessRuleService::class)->canAccessDepartment($userId, self::ACCESS_MODULE, 'review', $targetDepartmentId);
    }

    /**
     * Check if current user can process this request (IT staff only)
     */
    public function canProcess($userId)
    {
        if ($this->status !== 'approved') {
            return false;
        }

        return app(AccessRuleService::class)->allows($userId, self::ACCESS_MODULE, 'process');
    }

    /**
     * Generate unique request number
     */
    public static function generateRequestNumber()
    {
        $prefix = 'RAC-';
        $random = strtoupper(substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8));
        $requestNumber = $prefix . $random;

        // Ensure uniqueness
        while (self::where('request_number', $requestNumber)->exists()) {
            $random = strtoupper(substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 8));
            $requestNumber = $prefix . $random;
        }

        return $requestNumber;
    }
}
