<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Listrik extends Model
{
    use HasFactory;

    protected $fillable = [
        'lokasi',
        'tanggal',
        'jam',
        'lbp',
        'wbp',
        'total',
        'kvarh',
    ];

    protected $casts = [
        'lbp' => 'decimal:2',
        'wbp' => 'decimal:2',
        'total' => 'decimal:2',
        'kvarh' => 'decimal:4',
    ];
}
