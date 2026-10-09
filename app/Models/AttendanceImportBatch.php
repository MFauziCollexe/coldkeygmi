<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceImportBatch extends Model
{
    protected $fillable = [
        'filename',
        'month',
        'year',
        'uploaded_by',
        'total_rows',
        'valid_rows',
        'saved_rows',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'total_rows' => 'integer',
        'valid_rows' => 'integer',
        'saved_rows' => 'integer',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(AttendanceImportEntry::class, 'batch_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}