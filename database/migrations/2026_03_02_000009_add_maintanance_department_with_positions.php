<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('departments')->updateOrInsert(
            ['code' => 'MNT'],
            [
                'name' => 'Maintanance',
                'description' => 'Maintanance Department',
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        $departmentId = DB::table('departments')->where('code', 'MNT')->value('id');
        if (!$departmentId) {
            return;
        }

        $positions = [
            ['code' => 'MNT-MGR', 'name' => 'Manager'],
            ['code' => 'MNT-SPV', 'name' => 'Supervisor'],
            ['code' => 'MNT-LDR', 'name' => 'Leader'],
            ['code' => 'MNT-STF', 'name' => 'Staff'],
        ];

        foreach ($positions as $position) {
            DB::table('positions')->updateOrInsert(
                ['code' => $position['code']],
                [
                    'name' => $position['name'],
                    'department_id' => $departmentId,
                    'description' => $position['name'],
                    'is_manager' => $position['code'] === 'MNT-MGR',
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        DB::table('positions')
            ->where('department_id', $departmentId)
            ->whereNotIn('code', ['MNT-MGR', 'MNT-SPV', 'MNT-LDR', 'MNT-STF'])
            ->update([
                'is_active' => false,
                'is_manager' => false,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        DB::table('positions')->whereIn('code', ['MNT-MGR', 'MNT-SPV', 'MNT-LDR', 'MNT-STF'])->delete();
        DB::table('departments')->where('code', 'MNT')->delete();
    }
};
