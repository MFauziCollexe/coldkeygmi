<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequisitionSupplierItemQuote extends Model
{
    use HasFactory;

    protected $table = 'pr_supplier_item_quotes';

    protected $fillable = [
        'pr_supplier_id',
        'purchase_requisition_item_id',
        'quoted_price',
        'is_selected',
    ];

    protected $casts = [
        'quoted_price' => 'decimal:2',
        'is_selected' => 'boolean',
    ];

    public function prSupplier(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisitionSupplier::class, 'pr_supplier_id');
    }

    public function purchaseRequisitionItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisitionItem::class);
    }
}
