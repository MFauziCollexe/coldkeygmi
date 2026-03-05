<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'activity_logs';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'table_name',
        'record_id',
        'action',
        'old_values',
        'new_values',
        'user_id',
        'user_email',
        'ip_address',
        'created_date',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_date' => 'datetime',
        ];
    }

    /**
     * Get all activity logs sorted by newest first.
     */
    public static function allLogs($tableName = null)
    {
        $query = self::query();

        if ($tableName) {
            $query->where('table_name', $tableName);
        }

        return $query->orderBy('created_date', 'desc')->get();
    }

    /**
     * Get activity logs for a specific record.
     */
    public static function getRecordHistory($tableName, $recordId)
    {
        return self::where('table_name', $tableName)
            ->where('record_id', $recordId)
            ->orderBy('created_date', 'desc')
            ->get();
    }

    /**
     * Get activity logs by action type.
     */
    public static function getByAction($action)
    {
        return self::where('action', $action)
            ->orderBy('created_date', 'desc')
            ->get();
    }
}
