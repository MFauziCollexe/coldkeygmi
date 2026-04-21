<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpAssistantMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'role',
        'message',
        'module',
        'page_component',
        'page_url',
        'provider',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
