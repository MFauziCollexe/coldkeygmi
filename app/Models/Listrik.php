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
        'foto_path_2',
        'foto_path_3',
        'foto_path_4',
    ];

    protected $appends = ['foto_url', 'foto_url_2', 'foto_url_3', 'foto_url_4'];

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

    public function getFotoUrl2Attribute()
    {
        return $this->foto_path_2 ? Storage::disk('public')->url($this->foto_path_2) : null;
    }

    public function getFotoUrl3Attribute()
    {
        return $this->foto_path_3 ? Storage::disk('public')->url($this->foto_path_3) : null;
    }

    public function getFotoUrl4Attribute()
    {
        return $this->foto_path_4 ? Storage::disk('public')->url($this->foto_path_4) : null;
    }
}
