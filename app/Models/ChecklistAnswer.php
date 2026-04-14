<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChecklistAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'checklist_header_id',
        'template_question_id',
        'section_id',
        'scope_key',
        'answer_value',
        'note',
        'answered_by',
        'answered_at',
    ];

    protected $casts = [
        'answered_at' => 'datetime',
    ];

    public function header()
    {
        return $this->belongsTo(ChecklistHeader::class, 'checklist_header_id');
    }

    public function question()
    {
        return $this->belongsTo(ChecklistTemplateQuestion::class, 'template_question_id');
    }

    public function section()
    {
        return $this->belongsTo(ChecklistTemplateSection::class, 'section_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'answered_by');
    }
}
