<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequisitionSupplier extends Model
{
    use HasFactory;

    protected $table = 'pr_supplier';

    protected $fillable = [
        'purchase_requisition_id',
        'supplier_id',
        'lead_time',
        'payment_terms',
        'is_recommended',
    ];

    protected $casts = [
        'is_recommended' => 'boolean',
    ];

    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function itemQuotes(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionSupplierItemQuote::class, 'pr_supplier_id');
    }
}
