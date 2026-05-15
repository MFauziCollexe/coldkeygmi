<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PurchaseRequisitionAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_requisition_id',
        'filename',
        'path',
        'mime_type',
        'size',
        'original_path',
        'signed_path',
        'signature_status',
        'signed_by',
        'signed_at',
        'signature_meta',
    ];

    protected $casts = [
        'size' => 'integer',
        'signed_at' => 'datetime',
        'signature_meta' => 'array',
    ];

    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function isSigned(): bool
    {
        return $this->signature_status === 'signed';
    }

    public function getSignedUrlAttribute(): ?string
    {
        return $this->signed_path
            ? Storage::disk('public')->url($this->signed_path)
            : null;
    }

    public function getOriginalUrlAttribute(): ?string
    {
        $path = $this->original_path ?: $this->path;

        return $path
            ? Storage::disk('public')->url($path)
            : null;
    }

    public function isImage(): bool
    {
        $extension = strtolower(pathinfo((string) $this->filename, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
    }
}
