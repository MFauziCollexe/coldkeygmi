<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersIndexUrl;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\Department;
use App\Models\User;
use App\Models\Position;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    use RemembersIndexUrl;

    /**
     * Get the department manager for a given department
     */
    protected function getDepartmentManager($departmentId)
    {
        $managerPosition = Position::where('department_id', $departmentId)
            ->where('is_manager', true)
            ->first();
        
        if (!$managerPosition) {
            return null;
        }
        
        return User::where('position_id', $managerPosition->id)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Check if user is a manager of a department
     */
    protected function isDepartmentManager($userId, $departmentId = null)
    {
        $user = User::find($userId);
        if (!$user || !$user->position_id) {
            return false;
        }

        $position = Position::find($user->position_id);
        if (!$position || !$position->is_manager) {
            return false;
        }

        if ($departmentId !== null) {
            return $position->department_id == $departmentId;
        }

        return true;
    }

    /**
     * Get user's managed department IDs
     */
    protected function getManagedDepartmentIds($userId)
    {
        $user = User::find($userId);
        if (!$user || !$user->position_id) {
            return [];
        }

        $position = Position::find($user->position_id);
        if (!$position || !$position->is_manager) {
            return [];
        }

        return [$position->department_id];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->rememberIndexUrl($request, 'tickets');

        $query = Ticket::query()->with(['creator', 'assignee', 'department', 'attachments']);

        // Filters
        $search = request('search');
        $status = request('status');
        $deadline = request('deadline');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('ticket_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter status - only apply if status is not empty
        if (!empty($status)) {
            $query->where('status', $status);
        }

        if ($deadline) {
            $query->where('deadline', $deadline);
        }

        if ($assignedTo = request('assigned_to')) {
            $query->where('assigned_to', $assignedTo);
        }

        if ($departmentId = request('department_id')) {
            $query->where('department_id', $departmentId);
        }

        $currentUserId = Auth::id();
        $currentUser = Auth::user();

        // Restrict visibility: creator OR assignee OR department manager (except for admins)
        // Admins can see all tickets
        if ($currentUserId && !($currentUser && $currentUser->is_admin)) {
            $managedDeptIds = $this->getManagedDepartmentIds($currentUserId);
            
            $query->where(function ($q) use ($currentUserId, $managedDeptIds) {
                $q->where('assigned_to', $currentUserId)
                  ->orWhere('created_by', $currentUserId);
                
                // Add department manager visibility
                if (!empty($managedDeptIds)) {
                    $q->orWhereIn('department_id', $managedDeptIds);
                }
            });
        } elseif (!$currentUserId) {
            // no authenticated user -> return none
            $query->whereRaw('0 = 1');
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        // include departments for filter
        $departments = Department::active()->select('id', 'name', 'code')->get();
        
        // include users for assignee filter (only users in departments user can manage)
        $users = User::select('id', 'name', 'email', 'department_id')->get();

        return Inertia::render('GMISL/Utility/Tickets/Index', [
            'tickets' => $tickets,
            'filters' => request()->only(['search', 'status', 'deadline', 'assigned_to', 'department_id', 'page']),
            'departments' => $departments,
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('GMISL/Utility/Tickets/Create', [
            'departments' => Department::active()->select('id', 'name', 'code')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'required|date',
            'department_id' => 'required|exists:departments,id',
            'assigned_to' => 'nullable|exists:users,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|image|max:5120',
        ]);

        $data['ticket_number'] = 'TKT-' . Str::upper(Str::random(8));
        $data['created_by'] = Auth::id();
        $data['deadline_approved'] = true; // Initial deadline is approved
        
        $ticket = Ticket::create($data);

        // Handle attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('tickets/' . $ticket->id, 'public');
                $ticket->attachments()->create([
                    'filename' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        return $this->redirectToRememberedIndex($request, 'tickets', 'tickets.index')
            ->with('success', 'Ticket created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Ticket $ticket)
    {
        $this->authorizeTicketVisibility($ticket);

        // Get users in the ticket's department for distribution
        $departmentUsers = [];
        if ($ticket->department_id) {
            $departmentUsers = User::where('department_id', $ticket->department_id)
                ->select('id', 'name', 'email')
                ->get();
        }

        // Check if current user is a manager of this ticket's department
        $isManager = $ticket->canDistribute(Auth::id());
        
        // Check if current user is the assignee
        $isAssignee = $ticket->isAssignee(Auth::id());
        
        // Check if current user is the creator
        $isCreator = $ticket->isCreator(Auth::id());

        return Inertia::render('GMISL/Utility/Tickets/Show', [
            'ticket' => $ticket->load(['creator', 'assignee', 'department', 'resolvedBy', 'closedBy', 'attachments']),
            'authUser' => [
                'id' => Auth::id(),
                'name' => Auth::user()->name,
                'email' => Auth::user()->email,
                'department_id' => Auth::user()->department_id,
                'position_id' => Auth::user()->position_id,
            ],
            'departmentEmployees' => $departmentUsers,
            'isManager' => $isManager,
            'isAssignee' => $isAssignee,
            'isCreator' => $isCreator,
            'canReopen' => $isCreator && $ticket->status === 'Closed',
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ticket $ticket)
    {
        $this->authorizeTicketVisibility($ticket);

        return Inertia::render('GMISL/Utility/Tickets/Edit', [
            'ticket' => $ticket,
            'departments' => Department::active()->select('id', 'name', 'code')->get(),
            'users' => User::select('id', 'name', 'email', 'department_id')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ticket $ticket)
    {
        $this->authorizeTicketVisibility($ticket);

        // Prevent editing closed tickets
        if ($ticket->status === 'Closed') {
            return redirect()->back()->withErrors(['error' => 'Cannot edit a closed ticket.']);
        }

        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'nullable|date',
            'resolve_deadline' => 'nullable|date',
            'status' => 'nullable|in:Open,In Progress,On Hold,Resolved,Closed',
            'department_id' => 'nullable|exists:departments,id',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        // If changing status from Resolved to In Progress (rejecting resolution), clear resolution notes
        if ($ticket->status === 'Resolved' && $data['status'] === 'In Progress') {
            $data['resolution_notes'] = null;
            $data['resolved_at'] = null;
            $data['resolved_by'] = null;
        }

        $ticket->update($data);

        // Handle additional attachments uploaded during edit
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('tickets/' . $ticket->id, 'public');
                $ticket->attachments()->create([
                    'filename' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        return $this->redirectToRememberedIndex($request, 'tickets', 'tickets.index')
            ->with('success', 'Ticket updated successfully');
    }

    /**
     * Distribute ticket to a specific user (manager only)
     */
    public function distribute(Request $request, Ticket $ticket)
    {
        $this->authorizeTicketVisibility($ticket);

        // Only department manager can distribute
        if (!$ticket->canDistribute(Auth::id())) {
            return redirect()->back()->withErrors(['error' => 'Only the department manager can distribute tickets.']);
        }

        $data = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        // Verify the assigned user is in the same department
        $assignedUser = User::find($data['assigned_to']);
        if ($assignedUser->department_id != $ticket->department_id) {
            return redirect()->back()->withErrors(['error' => 'You can only assign tickets to users in your department.']);
        }

        // Set status to In Progress when distributed
        $ticket->update([
            'assigned_to' => $data['assigned_to'],
            'status' => $ticket->status === 'Open' ? 'In Progress' : $ticket->status,
        ]);

        return redirect()->back()->with('success', 'Ticket distributed successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Ticket $ticket)
    {
        $this->authorizeTicketVisibility($ticket);

        $ticket->delete();
        return $this->redirectToRememberedIndex($request, 'tickets', 'tickets.index')
            ->with('success', 'Ticket deleted');
    }

    /**
     * Delete an attachment from a ticket.
     */
    public function destroyAttachment(Ticket $ticket, TicketAttachment $attachment)
    {
        $this->authorizeTicketVisibility($ticket);

        // Prevent deleting attachments from closed or resolved tickets
        if ($ticket->status === 'Closed' || $ticket->status === 'Resolved') {
            return redirect()->back()->withErrors(['error' => 'Cannot delete attachments from closed or resolved tickets.']);
        }

        if ($attachment->ticket_id != $ticket->id) {
            abort(404);
        }

        // delete file from storage
        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return redirect()->back()->with('success', 'Attachment deleted');
    }

    /**
     * Replace an existing attachment with a new uploaded file.
     */
    public function replaceAttachment(Request $request, Ticket $ticket, TicketAttachment $attachment)
    {
        $this->authorizeTicketVisibility($ticket);

        if ($attachment->ticket_id != $ticket->id) {
            abort(404);
        }

        $data = $request->validate([
            'file' => 'required|image|max:5120',
        ]);

        // remove old file
        Storage::disk('public')->delete($attachment->path);

        $file = $request->file('file');
        $path = $file->store('tickets/' . $ticket->id, 'public');

        $attachment->update([
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return redirect()->back()->with('success', 'Attachment replaced');
    }

    /**
     * Update ticket status.
     * - Assignee can change status from Open, In Progress, On Hold
     * - When status is Resolved, only creator can change to Closed
     */
    public function updateStatus(Request $request, Ticket $ticket)
    {
        $this->authorizeTicketVisibility($ticket);

        $data = $request->validate([
            'status' => 'required|in:Open,In Progress,On Hold,Resolved,Closed',
        ]);

        $newStatus = $data['status'];
        $currentStatus = $ticket->status;
        $userId = Auth::id();

        // Validate transitions
        $validTransitions = [
            'Open' => ['In Progress', 'On Hold'],
            'In Progress' => ['On Hold', 'Open', 'Resolved'],
            'On Hold' => ['In Progress', 'Open'],
            'Resolved' => ['Closed', 'In Progress'],
            'Closed' => [],
        ];

        if (!isset($validTransitions[$currentStatus]) || !in_array($newStatus, $validTransitions[$currentStatus])) {
            return redirect()->back()->withErrors(['status' => "Cannot transition from {$currentStatus} to {$newStatus}."]);
        }

        // Closed tickets cannot be modified
        if ($currentStatus === 'Closed') {
            return redirect()->back()->withErrors(['status' => 'Closed tickets cannot be modified.']);
        }

        // When status is Resolved:
        // - Only creator can change to Closed
        // - Assignee can change to In Progress (reopen)
        if ($currentStatus === 'Resolved') {
            if ($newStatus === 'Closed') {
                // Only creator can close
                if (!$ticket->isCreator($userId)) {
                    return redirect()->back()->withErrors(['status' => 'Only the ticket creator can close the ticket.']);
                }
            } elseif ($newStatus === 'In Progress') {
                // Only assignee can reopen
                if (!$ticket->isAssignee($userId)) {
                    return redirect()->back()->withErrors(['status' => 'Only the assignee can reopen the ticket.']);
                }
            }
        } else {
            // For other statuses, only assignee can change
            if (!$ticket->isAssignee($userId)) {
                return redirect()->back()->withErrors(['status' => 'Only the assignee can change the status.']);
            }

            // Cannot change status if there's pending deadline request
            if ($ticket->hasPendingDeadlineRequest()) {
                return redirect()->back()->withErrors(['status' => 'Cannot change status while there is a pending deadline change request.']);
            }
        }

        $ticket->update(['status' => $newStatus]);

        return redirect()->back()->with('success', 'Status updated to ' . $newStatus);
    }

    /**
     * Resolve a ticket (set status to Resolved, add resolution notes, set resolved_at timestamp).
     * Auto-set resolve_deadline to today when resolved.
     * Optional attachment can be uploaded as proof of completion.
     */
    public function resolve(Request $request, Ticket $ticket)
    {
        $this->authorizeTicketVisibility($ticket);

        // Only assignee can resolve
        if (!$ticket->isAssignee(Auth::id())) {
            return redirect()->back()->withErrors(['status' => 'Only the assignee can resolve the ticket.']);
        }

        // Only allow resolve if status is In Progress
        if ($ticket->status !== 'In Progress') {
            return redirect()->back()->withErrors(['status' => 'Ticket must be In Progress to resolve.']);
        }

        // Cannot resolve if there's pending deadline request
        if ($ticket->hasPendingDeadlineRequest()) {
            return redirect()->back()->withErrors(['status' => 'Cannot resolve while there is a pending deadline change request.']);
        }

        $data = $request->validate([
            'resolution_notes' => 'required|string|min:10',
            'attachment' => 'nullable|file|image|max:5120',
        ]);

        $ticket->update([
            'status' => 'Resolved',
            'resolution_notes' => $data['resolution_notes'],
            'resolved_at' => now(),
            'resolved_by' => Auth::id(),
            'resolve_deadline' => now()->toDateString(), // Auto-set to today when resolved
        ]);

        // Handle optional attachment upload
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('tickets/' . $ticket->id, 'public');
            $ticket->attachments()->create([
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        return redirect()->back()->with('success', 'Ticket resolved successfully.');
    }

    /**
     * Close a ticket (set status to Closed and set closed_at timestamp).
     */
    public function close(Ticket $ticket)
    {
        $this->authorizeTicketVisibility($ticket);

        // Only creator can close the ticket
        if (!$ticket->isCreator(Auth::id())) {
            return redirect()->back()->withErrors(['status' => 'Only the ticket creator can close the ticket.']);
        }

        // Only allow close if status is Resolved
        if ($ticket->status !== 'Resolved') {
            return redirect()->back()->withErrors(['status' => 'Ticket must be Resolved before closing.']);
        }

        $ticket->update([
            'status' => 'Closed',
            'closed_at' => now(),
            'closed_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Ticket closed successfully.');
    }

    /**
     * Reopen a ticket (change from Resolved back to In Progress for re-work).
     * Only creator can reopen.
     */
    public function reopen(Ticket $ticket)
    {
        $this->authorizeTicketVisibility($ticket);

        // Only creator can reopen (not assignee)
        if (!$ticket->isCreator(Auth::id())) {
            return redirect()->back()->withErrors(['status' => 'Only the ticket creator can reopen the ticket.']);
        }

        // Only allow reopen if status is Resolved or Closed
        if ($ticket->status !== 'Resolved' && $ticket->status !== 'Closed') {
            return redirect()->back()->withErrors(['status' => 'Only Resolved or Closed tickets can be reopened.']);
        }

        $ticket->update([
            'status' => 'In Progress',
            'resolution_notes' => null,
            'resolved_at' => null,
            'resolved_by' => null,
            'resolve_deadline' => null, // Clear resolve_deadline when reopening
            'closed_at' => null,
            'closed_by' => null,
        ]);

        return redirect()->back()->with('success', 'Ticket reopened for further work.');
    }

    /**
     * Request deadline change (assignee only).
     */
    public function requestDeadlineChange(Request $request, Ticket $ticket)
    {
        $this->authorizeTicketVisibility($ticket);

        // Only assignee can request deadline change
        if (!$ticket->isAssignee(Auth::id())) {
            return redirect()->back()->withErrors(['error' => 'Only the assignee can request deadline changes.']);
        }

        $data = $request->validate([
            'deadline_request' => 'required|date|after:today',
        ]);

        $ticket->update([
            'deadline_request' => $data['deadline_request'],
            'deadline_approved' => null, // Reset approval status
        ]);

        return redirect()->back()->with('success', 'Deadline change requested. Waiting for approval from ticket creator.');
    }

    /**
     * Approve deadline change (creator only).
     */
    public function approveDeadlineChange(Request $request, Ticket $ticket)
    {
        $this->authorizeTicketVisibility($ticket);

        // Only creator can approve deadline change
        if (!$ticket->isCreator(Auth::id())) {
            return redirect()->back()->withErrors(['error' => 'Only the ticket creator can approve deadline changes.']);
        }

        // Check if there's a pending request
        if (!$ticket->hasPendingDeadlineRequest()) {
            return redirect()->back()->withErrors(['error' => 'No pending deadline change request.']);
        }

        $data = $request->validate([
            'approve' => 'required|boolean',
        ]);

        if ($data['approve']) {
            // Approve: apply the new deadline
            $ticket->update([
                'deadline' => $ticket->deadline_request,
                'deadline_request' => null,
                'deadline_approved' => true,
            ]);
            return redirect()->back()->with('success', 'Deadline change approved.');
        } else {
            // Reject: clear the request
            $ticket->update([
                'deadline_request' => null,
                'deadline_approved' => false,
            ]);
            return redirect()->back()->with('success', 'Deadline change rejected.');
        }
    }

    /**
     * Ensure current user can view/modify the ticket (creator, assignee, or department manager).
     */
    protected function authorizeTicketVisibility(Ticket $ticket)
    {
        $userId = Auth::id();
        if (!$userId) {
            abort(403);
        }

        if (!$ticket->canView($userId)) {
            abort(403);
        }
    }
}
