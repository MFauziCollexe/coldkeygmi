<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TTally extends Model
{
    protected $table = 't_tally';

    protected $fillable = [
        't_po_id',
        'item',
        'pallet',
        'kg',
        'is_finish',
        'startdate',
        'enddate',
    ];

    public function po(): BelongsTo
    {
        return $this->belongsTo(TPo::class, 't_po_id');
    }
}
