<?php

namespace App\Models;

use App\Support\AccessRuleService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    private const ACCESS_MODULE = 'tickets';

    public $timestamps = true;

    protected $fillable = [
        'ticket_number',
        'title',
        'description',
        'deadline',
        'deadline_request',
        'deadline_approved',
        'resolve_deadline',
        'status',
        'created_by',
        'assigned_to',
        'department_id',
        'resolution_notes',
        'resolved_at',
        'resolved_by',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'deadline' => 'date',
        'deadline_request' => 'date',
        'deadline_approved' => 'boolean',
        'resolve_deadline' => 'date',
    ];

    /**
     * Get the user who created the ticket
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the department assigned to this ticket
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the user assigned to this ticket
     */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who resolved the ticket
     */
    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Get the user who closed the ticket
     */
    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Attachments (images/files) for the ticket
     */
    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class);
    }

    /**
     * Comments posted on the ticket.
     */
    public function comments()
    {
        return $this->hasMany(TicketComment::class)->latest();
    }

    /**
     * Check if deadline change is pending approval
     */
    public function hasPendingDeadlineRequest()
    {
        return !is_null($this->deadline_request) && is_null($this->deadline_approved);
    }

    /**
     * Check if current user is the creator (can approve deadline changes)
     */
    public function isCreator($userId)
    {
        return $this->created_by == $userId;
    }

    /**
     * Check if current user is the assignee (can request deadline changes)
     */
    public function isAssignee($userId)
    {
        return $this->assigned_to == $userId;
    }

    /**
     * Check if current user is the department manager
     */
    public function isDepartmentManager($userId)
    {
        if (!$this->department_id) {
            return false;
        }

        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        return app(AccessRuleService::class)
            ->canAccessDepartment($user, self::ACCESS_MODULE, 'manage_department', (int) $this->department_id);
    }

    /**
     * Check if user can view this ticket (creator, assignee, department manager, or admin)
     */
    public function canView($userId)
    {
        $user = \App\Models\User::find($userId);
        return $this->isCreator($userId) || $this->isAssignee($userId) || $this->isDepartmentManager($userId);
    }

    /**
     * Check if user can distribute this ticket (department manager or admin)
     */
    public function canDistribute($userId)
    {
        return $this->isDepartmentManager($userId);
    }
}
