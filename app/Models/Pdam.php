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
        'foto_path_1',
        'foto_path_2',
    ];

    protected $appends = ['foto_url_1', 'foto_url_2'];

    protected $casts = [
        'meter_1' => 'decimal:4',
        'meter_2' => 'decimal:4',
    ];

    public function getFotoUrl1Attribute()
    {
        return $this->foto_path_1 ? Storage::disk('public')->url($this->foto_path_1) : null;
    }

    public function getFotoUrl2Attribute()
    {
        return $this->foto_path_2 ? Storage::disk('public')->url($this->foto_path_2) : null;
    }
}
