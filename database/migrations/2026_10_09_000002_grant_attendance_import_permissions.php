<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $userIds = DB::table('module_permissions as mp')
            ->select('mp.user_id')
            ->where('mp.module_key', 'gmihr_attendance_log')
            ->distinct()
            ->pluck('mp.user_id');

        $now = Carbon::now();
        foreach ($userIds as $userId) {
            $exists = DB::table('module_permissions')
                ->where('user_id', $userId)
                ->whereIn('module_key', ['gmihr.attendance.import', 'gmihr_attendance_import'])
                ->exists();
            if ($exists) {
                continue;
            }
            DB::table('module_permissions')->insert([
                'user_id' => $userId,
                'module_key' => 'gmihr_attendance_import',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('module_permissions')
            ->where('module_key', 'gmihr_attendance_import')
            ->delete();
    }
};