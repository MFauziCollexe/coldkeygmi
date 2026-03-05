<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $templates = [
            'FAT' => ['MGR' => 'Manager', 'SPV' => 'Supervisor', 'STF' => 'Staff'],
            'HRD' => ['MGR' => 'Manager', 'SPV' => 'Supervisor', 'STF' => 'Staff'],
            'IT'  => ['MGR' => 'Manager', 'SPV' => 'Supervisor', 'STF' => 'Staff'],
            'RSC' => ['MGR' => 'Manager', 'SPV' => 'Supervisor', 'STF' => 'Staff'],
            'SEC' => ['MGR' => 'Manager', 'SPV' => 'Supervisor', 'STF' => 'Staff'],
            'INV' => ['MGR' => 'Manager', 'SPV' => 'Supervisor', 'LDR' => 'Leader', 'STF' => 'Staff'],
        ];

        foreach ($templates as $deptCode => $positions) {
            $departmentId = DB::table('departments')->where('code', $deptCode)->value('id');
            if (!$departmentId) {
                continue;
            }

            $allowedCodes = [];
            foreach ($positions as $suffix => $name) {
                $code = "{$deptCode}-{$suffix}";
                $allowedCodes[] = $code;

                DB::table('positions')->updateOrInsert(
                    ['code' => $code],
                    [
                        'name' => $name,
                        'department_id' => $departmentId,
                        'description' => $name,
                        'is_manager' => $suffix === 'MGR',
                        'is_active' => true,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }

            DB::table('positions')
                ->where('department_id', $departmentId)
                ->whereNotIn('code', $allowedCodes)
                ->update([
                    'is_active' => false,
                    'is_manager' => false,
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        // no-op rollback for data standardization
    }
};
