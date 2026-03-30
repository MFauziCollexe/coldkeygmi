<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of the activity logs.
     */
    public function index()
    {
        $query = ActivityLog::query();

        // Filters
        $search = request('search');
        $tableName = request('table_name');
        $action = request('action');
        $userId = request('user_id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('table_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('user_email', 'like', "%{$search}%")
                  ->orWhere('record_id', 'like', "%{$search}%");
            });
        }

        if ($tableName) {
            $query->where('table_name', $tableName);
        }

        if ($action) {
            $query->where('action', $action);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $logs = $query->orderByDesc('id')->orderByDesc('created_date')->paginate(15)->withQueryString();

        // Get unique table names for filter
        $tableNames = ActivityLog::distinct()->pluck('table_name')->filter()->values();

        // Get unique actions for filter
        $actions = ActivityLog::distinct()->pluck('action')->filter()->values();

        // Get unique users for filter
        $users = ActivityLog::distinct()
            ->whereNotNull('user_id')
            ->select('user_id', 'user_email')
            ->get()
            ->unique('user_id')
            ->values();

        return Inertia::render('ControlPanel/Logs', [
            'logs' => $logs,
            'filters' => request()->only(['search', 'table_name', 'action', 'user_id', 'page']),
            'tableNames' => $tableNames,
            'actions' => $actions,
            'users' => $users,
        ]);
    }

    /**
     * Display the specified activity log.
     */
    public function show(ActivityLog $log)
    {
        return Inertia::render('ControlPanel/LogShow', [
            'log' => $log,
        ]);
    }

    /**
     * Remove all activity logs.
     */
    public function clear()
    {
        ActivityLog::query()->delete();

        return redirect()
            ->route('control.logs')
            ->with('success', 'Semua activity log berhasil dihapus.');
    }
}
