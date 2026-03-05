<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckInlineImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'check_inline_id',
        'image',
    ];

    public function checkInline()
    {
        return $this->belongsTo(CheckInline::class);
    }
}

