<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Pdam extends Model
{
    use HasFactory;

    protected $table = 'pdams';

    protected $fillable = [
        'tanggal',
        'jam_1',
        'meter_1',
        'jam_2',
        'meter_2',
        'foto_path',
    ];

    protected $appends = ['foto_url'];

    protected $casts = [
        'meter_1' => 'decimal:2',
        'meter_2' => 'decimal:2',
    ];

    public function getFotoUrlAttribute()
    {
        return $this->foto_path ? Storage::disk('public')->url($this->foto_path) : null;
    }
}
