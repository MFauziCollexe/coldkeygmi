<?php

/**
 * ACTIVITY LOGGING USAGE EXAMPLES
 * 
 * The ColdKey GMI application automatically logs all database activity
 * related to users through the Observer pattern.
 */

namespace Examples;

use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

class ActivityLoggingExamples
{
    /**
     * Example 1: Getting all activity logs
     */
    public function getAllLogs()
    {
        // Get all logs, sorted by newest first
        $logs = ActivityLog::allLogs();

        foreach ($logs as $log) {
            echo json_encode([
                'id' => $log->id,
                'table_name' => $log->table_name,
                'record_id' => $log->record_id,
                'action' => $log->action,
                'user_email' => $log->user_email,
                'ip_address' => $log->ip_address,
                'created_date' => $log->created_date->format('Y-m-d H:i:s'),
                'description' => $log->description,
            ], JSON_PRETTY_PRINT);
        }
    }

    /**
     * Example 2: Getting logs for specific user record
     */
    public function getUserActivityHistory($userId)
    {
        // Get all changes made to a specific user
        $history = ActivityLog::getRecordHistory('users', $userId);

        echo "Activity history for User ID {$userId}:\n";
        foreach ($history as $log) {
            echo "Action: {$log->action} at {$log->created_date}\n";
            echo "By: {$log->user_email} ({$log->ip_address})\n";
            echo "Changes: {$log->description}\n";
            if ($log->old_values) {
                echo "Before: " . json_encode($log->old_values) . "\n";
            }
            if ($log->new_values) {
                echo "After: " . json_encode($log->new_values) . "\n";
            }
            echo "---\n";
        }
    }

    /**
     * Example 3: Getting all INSERT operations
     */
    public function getNewUserSignups()
    {
        // Get all user creation logs
        $signups = ActivityLog::where('table_name', 'users')
            ->where('action', 'insert')
            ->orderBy('created_date', 'desc')
            ->get();

        echo "New user signups:\n";
        foreach ($signups as $signup) {
            $data = json_decode($signup->new_values, true);
            echo "User: {$data['first_name']} {$data['last_name']}\n";
            echo "Email: {$data['email']}\n";
            echo "Signed up at: {$signup->created_date}\n";
            echo "From IP: {$signup->ip_address}\n";
            echo "Status: {$data['status']}\n";
            echo "---\n";
        }
    }

    /**
     * Example 4: Getting all UPDATE operations for a specific date
     */
    public function getUpdatesforDate($date)
    {
        // Get all updates on a specific date
        $updates = ActivityLog::where('table_name', 'users')
            ->where('action', 'update')
            ->whereDate('created_date', $date)
            ->orderBy('created_date', 'desc')
            ->get();

        echo "User updates on {$date}:\n";
        foreach ($updates as $update) {
            echo "Record ID: {$update->record_id}\n";
            echo "Updated by: {$update->user_email}\n";
            echo "Description: {$update->description}\n";
            echo "Changed fields:\n";
            foreach ($update->old_values as $field => $oldValue) {
                $newValue = $update->new_values[$field] ?? 'N/A';
                echo "  - {$field}: {$oldValue} → {$newValue}\n";
            }
            echo "---\n";
        }
    }

    /**
     * Example 5: Getting all DELETE operations
     */
    public function getDeletedUsers()
    {
        // Get all deleted user records
        $deletions = ActivityLog::getByAction('delete');

        echo "Deleted users:\n";
        foreach ($deletions as $deletion) {
            $data = json_decode($deletion->old_values, true);
            echo "User: {$data['first_name']} {$data['last_name']}\n";
            echo "Email: {$data['email']}\n";
            echo "Deleted at: {$deletion->created_date}\n";
            echo "Deleted by: {$deletion->user_email}\n";
            echo "---\n";
        }
    }

    /**
     * Example 6: Getting logs by IP address (security audit)
     */
    public function getActivityByIP($ipAddress)
    {
        // Get all activities from a specific IP
        $activities = ActivityLog::where('ip_address', $ipAddress)
            ->orderBy('created_date', 'desc')
            ->get();

        echo "All activities from IP {$ipAddress}:\n";
        foreach ($activities as $activity) {
            echo "{$activity->action} on {$activity->table_name} at {$activity->created_date}\n";
            echo "Description: {$activity->description}\n";
            echo "---\n";
        }
    }

    /**
     * Example 7: Getting logs by user email (who made changes)
     */
    public function getActivitiesByUser($userEmail)
    {
        // Get all changes made by a specific user
        $activities = ActivityLog::where('user_email', $userEmail)
            ->orderBy('created_date', 'desc')
            ->get();

        echo "All activities by {$userEmail}:\n";
        foreach ($activities as $activity) {
            echo "{$activity->action} on {$activity->table_name} (ID: {$activity->record_id})\n";
            echo "At: {$activity->created_date}\n";
            echo "From IP: {$activity->ip_address}\n";
            echo "---\n";
        }
    }

    /**
     * Example 8: Working with user model changes (auto-logged)
     */
    public function userStatusChange()
    {
        // When a user's status is changed, activity log is automatically created
        $user = User::find(2);
        
        // This creates an activity log entry automatically
        $user->activate('admin@example.com');
        
        // This also creates an activity log entry
        $user->deactivate('admin@example.com');
        
        // Or directly update (also logged):
        $user->update(['status' => 'active']);
    }

    /**
     * Example 9: Create user manually (also logged)
     */
    public function createNewUser()
    {
        // This automatically creates an activity log entry
        $user = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
            'status' => 'deactivated',
            'department' => 'Engineering',
            'job_position' => 'Developer',
            'work_position' => 'Senior',
            'user_created' => 'admin@example.com',
            'user_updated' => 'admin@example.com',
        ]);

        // Check activity log was created
        $log = ActivityLog::where('record_id', $user->id)
            ->where('action', 'insert')
            ->first();

        echo "User created with ID: {$user->id}\n";
        echo "Activity logged: {$log->description}\n";
    }

    /**
     * Example 10: Generate audit report for compliance
     */
    public function generateAuditReport($startDate, $endDate)
    {
        $logs = ActivityLog::where('table_name', 'users')
            ->whereBetween('created_date', [$startDate, $endDate])
            ->orderBy('created_date')
            ->get();

        $report = [
            'period' => "{$startDate} to {$endDate}",
            'total_actions' => count($logs),
            'by_action' => [],
            'by_user' => [],
            'logs' => [],
        ];

        foreach ($logs as $log) {
            // Count by action type
            if (!isset($report['by_action'][$log->action])) {
                $report['by_action'][$log->action] = 0;
            }
            $report['by_action'][$log->action]++;

            // Count by user
            $user = $log->user_email ?: 'system';
            if (!isset($report['by_user'][$user])) {
                $report['by_user'][$user] = 0;
            }
            $report['by_user'][$user]++;

            // Store log details
            $report['logs'][] = [
                'timestamp' => $log->created_date->format('Y-m-d H:i:s'),
                'action' => $log->action,
                'record_id' => $log->record_id,
                'user' => $log->user_email,
                'ip' => $log->ip_address,
                'description' => $log->description,
            ];
        }

        return $report;
    }
}

/**
 * DATABASE QUERIES FOR ANALYSIS
 * 
 * These SQL queries can be used to analyze activity logs
 */

class ActivityLogQueries
{
    /**
     * Get summary of activities by type
     */
    public static function summaryQuery()
    {
        return "
            SELECT 
                action,
                COUNT(*) as count,
                MIN(created_date) as first_action,
                MAX(created_date) as last_action
            FROM activity_logs
            WHERE table_name = 'users'
            GROUP BY action;
        ";
    }

    /**
     * Get most active users (making changes)
     */
    public static function mostActiveUsersQuery()
    {
        return "
            SELECT 
                user_email,
                COUNT(*) as total_actions,
                COUNT(CASE WHEN action = 'insert' THEN 1 END) as inserts,
                COUNT(CASE WHEN action = 'update' THEN 1 END) as updates,
                COUNT(CASE WHEN action = 'delete' THEN 1 END) as deletes
            FROM activity_logs
            WHERE table_name = 'users'
            GROUP BY user_email
            ORDER BY total_actions DESC;
        ";
    }

    /**
     * Get frequently modified users (most changed records)
     */
    public static function mostModifiedUsersQuery()
    {
        return "
            SELECT 
                record_id,
                COUNT(*) as modifications,
                MAX(created_date) as last_modified
            FROM activity_logs
            WHERE table_name = 'users'
            GROUP BY record_id
            ORDER BY modifications DESC
            LIMIT 10;
        ";
    }

    /**
     * Get activities by date range
     */
    public static function activitiesByDateRangeQuery($startDate, $endDate)
    {
        return "
            SELECT 
                DATE(created_date) as date,
                COUNT(*) as total_actions,
                COUNT(DISTINCT record_id) as affected_records
            FROM activity_logs
            WHERE table_name = 'users'
            AND created_date BETWEEN '{$startDate}' AND '{$endDate}'
            GROUP BY DATE(created_date)
            ORDER BY date;
        ";
    }
}
