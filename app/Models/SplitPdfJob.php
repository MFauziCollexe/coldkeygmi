<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SplitPdfJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_filename',
        'original_path',
        'page_ranges',
        'split_mode',
        'output_filename',
        'output_path',
        'output_type',
        'output_count',
        'output_size',
        'status',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'page_ranges' => 'array',
            'output_count' => 'integer',
            'output_size' => 'integer',
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
}
