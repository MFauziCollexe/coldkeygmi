<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaterLog extends Model
{
    protected $fillable = [
        'meter_id',
        'tanggal',
        'jam',
        'meter_value',
        'user_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'meter_value' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
