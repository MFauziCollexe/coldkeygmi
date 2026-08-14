<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Listrik extends Model
{
    use HasFactory;

    protected $table = 'listriks';

    protected $fillable = [
        'lokasi',
        'tanggal',
        'jam',
        'lbp',
        'wbp',
        'total',
        'kvarh',
        'foto_path',
    ];

    protected $appends = ['foto_url'];

    protected $casts = [
        'lbp' => 'decimal:4',
        'wbp' => 'decimal:4',
        'total' => 'decimal:4',
        'kvarh' => 'decimal:4',
    ];

    public function getFotoUrlAttribute()
    {
        return $this->foto_path ? Storage::disk('public')->url($this->foto_path) : null;
    }
}
