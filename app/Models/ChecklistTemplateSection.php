<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChecklistTemplateSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'code',
        'title',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function template()
    {
        return $this->belongsTo(ChecklistTemplate::class, 'template_id');
    }

    public function questions()
    {
        return $this->hasMany(ChecklistTemplateQuestion::class, 'section_id')->orderBy('sort_order');
    }

    public function answers()
    {
        return $this->hasMany(ChecklistAnswer::class, 'section_id');
    }
}
