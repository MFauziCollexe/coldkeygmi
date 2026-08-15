<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TPo extends Model
{
    protected $table = 't_po';

    protected $fillable = [
        'po',
        'nopol',
        'driver',
        'customer_id',
        'transaksi',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customers_id_odoo');
    }
}
