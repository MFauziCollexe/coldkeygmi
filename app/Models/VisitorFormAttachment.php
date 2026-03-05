<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorFormAttachment extends Model
{
    protected $fillable = [
        'visitor_form_id',
        'filename',
        'path',
        'mime_type',
        'size',
    ];

    public function visitorForm(): BelongsTo
    {
        return $this->belongsTo(VisitorForm::class);
    }
}

