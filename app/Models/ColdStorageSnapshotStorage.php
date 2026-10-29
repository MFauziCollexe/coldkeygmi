<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ColdStorageSnapshotStorage extends Model
{
    protected $fillable = [
        'snapshot_batch_id',
        'odoo_location_id',
        'name',
        'complete_name',
        'capacity',
        'used',
        'available',
        'occupancy',
        'status',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ColdStorageSnapshotBatch::class, 'snapshot_batch_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(ColdStorageSnapshotCustomer::class, 'snapshot_storage_id');
    }
}
