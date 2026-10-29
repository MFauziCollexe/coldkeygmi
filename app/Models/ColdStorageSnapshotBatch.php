<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ColdStorageSnapshotBatch extends Model
{
    protected $fillable = [
        'snapshot_date',
        'captured_at',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'captured_at' => 'datetime',
    ];

    public function storages(): HasMany
    {
        return $this->hasMany(ColdStorageSnapshotStorage::class, 'snapshot_batch_id');
    }
}
