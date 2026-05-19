<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementMasterItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_code',
        'item_name',
        'description_of_goods',
        'item_type',
        'unit',
        'default_price',
        'is_active',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function purchaseRequisitionItems(): HasMany
    {
        return $this->hasMany(PurchaseRequisitionItem::class);
    }
}
