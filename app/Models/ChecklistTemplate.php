<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChecklistTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'module',
        'version_no',
        'is_active',
    ];

    protected $casts = [
        'version_no' => 'integer',
        'is_active' => 'boolean',
    ];

    public function sections()
    {
        return $this->hasMany(ChecklistTemplateSection::class, 'template_id')->orderBy('sort_order');
    }

    public function questions()
    {
        return $this->hasMany(ChecklistTemplateQuestion::class, 'template_id')->orderBy('sort_order');
    }

    public function headers()
    {
        return $this->hasMany(ChecklistHeader::class, 'template_id');
    }
}
