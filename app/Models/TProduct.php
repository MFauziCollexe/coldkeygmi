<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TProduct extends Model
{
    protected $table = 't_products';

    protected $fillable = [
        'customer',
        'barcode',
        'internal_reference',
        'name',
        'product_type',
        'product_category',
        'unit_of_measure',
        'standard_qty_pallet',
        'pack_size_cf',
        'length',
        'width',
        'height',
        'weight',
        'layer_stack',
        'volume',
        'track_inventory',
        'valuation_by_lot_serial_number',
        'use_expiration_date',
        'tags_name',
        'routes',
        'customer_id',
        'optional_products_external_id',
        'optional_products_id',
        'products_external_id',
        'products_id',
    ];

    protected $casts = [
        'standard_qty_pallet' => 'float',
        'length' => 'float',
        'width' => 'float',
        'height' => 'float',
        'weight' => 'float',
        'volume' => 'float',
    ];
}
