<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElectricityStandardLog extends Model
{
    protected $fillable = [
        'meter_id',
        'tanggal',
        'jam',
        'kwh',
        'user_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'kwh' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
