<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElectricityHvLog extends Model
{
    protected $fillable = [
        'meter_id',
        'tanggal',
        'jam',
        'lbp',
        'wbp',
        'total',
        'kvarh',
        'user_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'lbp' => 'decimal:2',
        'wbp' => 'decimal:2',
        'total' => 'decimal:2',
        'kvarh' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

