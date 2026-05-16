<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequisitionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_requisition_id',
        'procurement_master_item_id',
        'line_no',
        'item_code',
        'item_name',
        'description_of_goods',
        'specification',
        'unit',
        'quantity',
        'required_date',
        'price',
        'product_name',
        'uom',
        'qty',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'quantity' => 'decimal:2',
        'price' => 'decimal:2',
        'required_date' => 'date',
    ];

    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class);
    }

    public function procurementMasterItem(): BelongsTo
    {
        return $this->belongsTo(ProcurementMasterItem::class);
    }
}
