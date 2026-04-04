<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockCardRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_card_item_id',
        'request_date',
        'quantity',
        'requested_by_name',
        'notes',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'request_date' => 'date',
        'quantity' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(StockCardItem::class, 'stock_card_item_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
