<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sourceKeys = [
            'gmihr.payroll.roster',
            'gmihr_payroll_roster',
        ];

        $targetKeys = [
            'gmihr.payroll.roster.upload',
            'gmihr_payroll_roster_upload',
            'gmihr.payroll.roster.list',
            'gmihr_payroll_roster_list',
        ];

        $userIds = DB::table('module_permissions')
            ->whereIn('module_key', $sourceKeys)
            ->pluck('user_id')
            ->unique()
            ->values();

        foreach ($userIds as $userId) {
            foreach ($targetKeys as $targetKey) {
                $exists = DB::table('module_permissions')
                    ->where('user_id', $userId)
                    ->where('module_key', $targetKey)
                    ->exists();

                if (!$exists) {
                    DB::table('module_permissions')->insert([
                        'user_id' => $userId,
                        'module_key' => $targetKey,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('module_permissions')
            ->whereIn('module_key', [
                'gmihr.payroll.roster.upload',
                'gmihr_payroll_roster_upload',
                'gmihr.payroll.roster.list',
                'gmihr_payroll_roster_list',
            ])
            ->delete();
    }
};
