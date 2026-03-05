<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_type',
        'name',
        'code',
        'logo_image',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'country',
        'zip',
        'phone',
        'mobile',
        'email',
        'website',
        'npwp',
        'is_pkp',
        'invoice_transaction_code',
        'tags',
        'is_active',
    ];

    protected $casts = [
        'customer_type' => 'string',
        'is_pkp' => 'boolean',
        'is_active' => 'boolean',
    ];
}
