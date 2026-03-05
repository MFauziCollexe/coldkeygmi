<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\RequestAccess;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $itDepartment = Department::query()
            ->where('code', 'IT')
            ->orWhere('name', 'Information Technology')
            ->first();

        $currentUser = Auth::user();
        $currentUser?->loadMissing('department');
        $dashboardDepartment = $currentUser?->department ?: $itDepartment;
        $dashboardDepartmentId = $dashboardDepartment?->id;

        $ticketQuery = Ticket::query()
            ->when($dashboardDepartmentId, fn($q) => $q->where('department_id', $dashboardDepartmentId), fn($q) => $q->whereRaw('1=0'));
        $totalTickets = (clone $ticketQuery)->count();
        $openTickets = (clone $ticketQuery)->where('status', 'Open')->count();
        $inProgressTickets = (clone $ticketQuery)->where('status', 'In Progress')->count();
        $holdTickets = (clone $ticketQuery)->where('status', 'On Hold')->count();
        $closedTickets = (clone $ticketQuery)->where('status', 'Closed')->count();
        $resolvedTickets = (clone $ticketQuery)->where('status', 'Resolved')->count();

        $requestAccessQuery = RequestAccess::query()
            ->when($dashboardDepartmentId, function ($query) use ($dashboardDepartmentId) {
                $query->where(function ($nested) use ($dashboardDepartmentId) {
                    $nested
                        ->where('target_department_id', $dashboardDepartmentId)
                        ->orWhereHas('creator', function ($creatorQuery) use ($dashboardDepartmentId) {
                            $creatorQuery->where('department_id', $dashboardDepartmentId);
                        });
                });
            }, fn($q) => $q->whereRaw('1=0'));
        $requestAccessTotal = (clone $requestAccessQuery)->count();
        $requestAccessPending = (clone $requestAccessQuery)->where('status', 'pending')->count();
        $requestAccessApproved = (clone $requestAccessQuery)->where('status', 'approved')->count();

        $usersQuery = User::query()
            ->when($dashboardDepartmentId, fn($q) => $q->where('department_id', $dashboardDepartmentId), fn($q) => $q->whereRaw('1=0'));
        $totalUsers = (clone $usersQuery)->count();
        $activeUsers = (clone $usersQuery)->where('status', 'active')->count();
        $deactiveUsers = (clone $usersQuery)->where('status', 'deactivated')->count();

        $allUsersQuery = User::query();
        $totalUsersAllDepartments = (clone $allUsersQuery)->count();
        $activeUsersAllDepartments = (clone $allUsersQuery)->where('status', 'active')->count();
        $deactiveUsersAllDepartments = (clone $allUsersQuery)->where('status', 'deactivated')->count();

        $overdueTickets = Ticket::query()
            ->when($dashboardDepartmentId, fn($q) => $q->where('department_id', $dashboardDepartmentId), fn($q) => $q->whereRaw('1=0'))
            ->whereDate('deadline', '<', now()->toDateString())
            ->whereNotIn('status', ['Closed', 'Resolved'])
            ->count();

        $completionRate = $totalTickets > 0
            ? round((($closedTickets + $resolvedTickets) / $totalTickets) * 100)
            : 0;

        $logs = ActivityLog::query()
            ->orderByDesc('created_date')
            ->orderByDesc('id')
            ->limit(12)
            ->get([
                'id',
                'table_name',
                'action',
                'description',
                'user_email',
                'created_date',
            ]);

        return Inertia::render('Dashboard', [
            'itDashboard' => [
                'department' => [
                    'id' => $dashboardDepartmentId,
                    'name' => $dashboardDepartment?->name ?? 'IT',
                    'code' => $dashboardDepartment?->code ?? 'IT',
                ],
                'tickets' => [
                    'total' => $totalTickets,
                    'open' => $openTickets,
                    'in_progress' => $inProgressTickets,
                    'hold' => $holdTickets,
                    'closed' => $closedTickets,
                    'resolved' => $resolvedTickets,
                    'overdue' => $overdueTickets,
                    'completion_rate' => $completionRate,
                ],
                'request_access' => [
                    'total' => $requestAccessTotal,
                    'pending' => $requestAccessPending,
                    'approved' => $requestAccessApproved,
                ],
                'users' => [
                    'total' => $totalUsers,
                    'active' => $activeUsers,
                    'deactive' => $deactiveUsers,
                ],
                'users_all_departments' => [
                    'total' => $totalUsersAllDepartments,
                    'active' => $activeUsersAllDepartments,
                    'deactive' => $deactiveUsersAllDepartments,
                ],
                'logs' => $logs,
            ],
        ]);
    }
}
