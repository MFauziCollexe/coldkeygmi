<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChecklistTemplateQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'section_id',
        'question_code',
        'question_text',
        'answer_type',
        'sort_order',
        'is_required',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    public function template()
    {
        return $this->belongsTo(ChecklistTemplate::class, 'template_id');
    }

    public function section()
    {
        return $this->belongsTo(ChecklistTemplateSection::class, 'section_id');
    }

    public function answers()
    {
        return $this->hasMany(ChecklistAnswer::class, 'template_question_id');
    }
}
