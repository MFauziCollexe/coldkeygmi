<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Indicates if the model should be timestamped.
     * We're using custom created_date and updated_date columns.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'account',
        'password',
        'status',
        'department_id',
        'position_id',
        'is_admin',
        'user_created',
        'user_updated',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'created_date' => 'datetime',
            'updated_date' => 'datetime',
        ];
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute()
    {
        return $this->name;
    }

    /**
     * Module permissions relation
     */
    public function modulePermissions()
    {
        return $this->hasMany(\App\Models\ModulePermission::class);
    }

    /**
     * Check if the user has access to a module key.
     * Strict check - no admin bypass, must have explicit permission in DB
     */
    public function hasModulePermission(string $key): bool
    {
        // check related permissions - allow either dot or underscore format
        $alt = str_replace('.', '_', $key);
        return $this->modulePermissions()->where(function ($q) use ($key, $alt) {
            $q->where('module_key', $key)->orWhere('module_key', $alt);
        })->exists();
    }

    /**
     * Simple admin detector. Check is_admin column.
     */
    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * Check if user is active.
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Activate the user.
     */
    public function activate($activatedBy = 'system')
    {
        $this->update([
            'status' => 'active',
            'user_updated' => $activatedBy,
        ]);
    }

    /**
     * Deactivate the user.
     */
    public function deactivate($deactivatedBy = 'system')
    {
        $this->update([
            'status' => 'deactivated',
            'user_updated' => $deactivatedBy,
        ]);
    }

    /**
     * Get the department associated with the user.
     */
    public function department()
    {
        return $this->belongsTo(\App\Models\Department::class);
    }

    /**
     * Get the position associated with the user.
     */
    public function position()
    {
        return $this->belongsTo(\App\Models\Position::class);
    }

    /**
     * Get the employee profile associated with the user.
     */
    public function employee()
    {
        return $this->hasOne(\App\Models\Employee::class);
    }
}
