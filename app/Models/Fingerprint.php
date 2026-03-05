<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fingerprint extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'fingerprints';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'scan_date',
        'scan_date_only',
        'scan_time',
        'pin',
        'nip',
        'name',
        'position',
        'department',
        'office',
        'verify',
        'io',
        'workcode',
        'sn',
        'machine',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'scan_date' => 'datetime',
        'scan_date_only' => 'date',
        'verify' => 'integer',
        'io' => 'integer',
        'workcode' => 'integer',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Check if fingerprint data already exists.
     */
    public static function exists(string $scanDate, string $pin): bool
    {
        return self::where('scan_date', $scanDate)
            ->where('pin', $pin)
            ->exists();
    }
}
