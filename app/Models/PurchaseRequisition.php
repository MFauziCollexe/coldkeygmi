<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class PurchaseRequisition extends Model
{
    use HasFactory;

    protected $fillable = [
        'pr_number',
        'pr_date',
        'request_date',
        'priority',
        'requested_by',
        'department_id',
        'supplier_id',
        'status',
        'approved_by',
        'approved_at',
        'note',
        'po_comment',
        'po_photo_path',
        'po_photo_filename',
        'po_processed_by',
        'po_processed_at',
        'po_done_by',
        'po_done_at',
    ];

    protected $casts = [
        'pr_date' => 'date',
        'request_date' => 'date',
        'approved_at' => 'datetime',
        'po_processed_at' => 'datetime',
        'po_done_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionItem::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionAttachment::class);
    }

    public static function generateNumber(?Carbon $date = null): string
    {
        $baseDate = $date ?: now();
        $prefix = 'PR' . $baseDate->format('y-m') . '-';

        $last = static::query()
            ->where('pr_number', 'like', $prefix . '%')
            ->orderByDesc('pr_number')
            ->value('pr_number');

        $sequence = 1;
        if (is_string($last) && preg_match('/^' . preg_quote($prefix, '/') . '(\d{4})$/', $last, $matches)) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
