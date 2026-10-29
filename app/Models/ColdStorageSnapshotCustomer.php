<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ColdStorageSnapshotCustomer extends Model
{
    protected $fillable = [
        'snapshot_storage_id',
        'odoo_owner_id',
        'name',
        'occupied_slots',
        'percentage',
    ];

    public function storage(): BelongsTo
    {
        return $this->belongsTo(ColdStorageSnapshotStorage::class, 'snapshot_storage_id');
    }
}
