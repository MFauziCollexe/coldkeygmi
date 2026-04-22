<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompressPdfJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_filename',
        'compressed_filename',
        'original_path',
        'compressed_path',
        'original_size',
        'compressed_size',
        'compression_ratio',
        'compression_level',
        'status',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'original_size' => 'integer',
            'compressed_size' => 'integer',
            'compression_ratio' => 'float',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function getCompressionPercentage(): float
    {
        if ($this->original_size == 0) {
            return 0;
        }
        return round((1 - ($this->compressed_size ?? 0) / $this->original_size) * 100, 2);
    }
}
