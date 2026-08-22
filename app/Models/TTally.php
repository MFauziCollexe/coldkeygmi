<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class TTally extends Model
{
    protected $table = 't_tally';

    protected $fillable = [
        'no_tally',
        't_po_id',
        'item',
        'pallet',
        'exp_date',
        'kg',
        'is_finish',
        'startdate',
        'enddate',
        'user_tally',
    ];

    protected static function booted(): void
    {
        static::creating(function (TTally $tally) {
            if (is_null($tally->startdate)) {
                $tally->startdate = now();
            }
        });
    }

    public function po(): BelongsTo
    {
        return $this->belongsTo(TPo::class, 't_po_id');
    }

    public function tallyUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_tally');
    }
}
