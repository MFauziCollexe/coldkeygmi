<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserImpersonationToken extends Model
{
    protected $fillable = [
        'token_hash',
        'target_user_id',
        'issued_by_user_id',
        'expires_at',
        'consumed_at',
        'redeemed_ip',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }
}