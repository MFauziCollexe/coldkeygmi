<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockCardStockIn extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_card_item_id',
        'transaction_date',
        'quantity',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'quantity' => 'decimal:2',
    ];

    public function item()
    {
        return $this->belongsTo(StockCardItem::class, 'stock_card_item_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
