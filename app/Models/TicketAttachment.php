<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TicketAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'filename',
        'path',
        'mime_type',
        'size',
    ];

    protected $appends = ['url'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    // Return a full URL for the stored file
    public function getUrlAttribute()
    {
        return Storage::url($this->path);
    }
}
