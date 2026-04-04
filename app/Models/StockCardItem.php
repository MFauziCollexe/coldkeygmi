<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockCardItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_code',
        'name',
        'item_type',
        'unit',
        'current_stock',
        'minimum_stock',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'current_stock' => 'decimal:2',
        'minimum_stock' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function stockIns()
    {
        return $this->hasMany(StockCardStockIn::class);
    }

    public function stockRequests()
    {
        return $this->hasMany(StockCardRequest::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
