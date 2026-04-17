<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'department_id',
        'position_id',
        'nik',
        'name',
        'alias_name',
        'employment_status',
        'resigned_at',
        'work_group',
        'join_date',
        'phone',
        'address',
        'birth_date',
        'birth_place',
        'gender',
        'religion',
        'marital_status',
        'education',
        'face_reference_photo_path',
        'face_reference_descriptor',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'join_date' => 'date',
        'birth_date' => 'date',
        'resigned_at' => 'date',
    ];

    /**
     * Get the user associated with the employee.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }
}
