<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckInline extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_name',
        'customer',
        'po',
        'qty',
        'exp',
        'batch',
        'date',
        'image',
        'created_by',
        'updated_by',
        'created_date',
        'updated_date',
    ];

    protected $casts = [
        'date' => 'date',
        'created_date' => 'datetime',
        'updated_date' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function images()
    {
        return $this->hasMany(CheckInlineImage::class);
    }
}
