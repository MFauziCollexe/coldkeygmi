<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $departments = [
            ['code' => 'IT', 'name' => 'IT', 'description' => 'IT Department'],
            ['code' => 'OPS', 'name' => 'Opperational', 'description' => 'Opperational Department'],
            ['code' => 'INV', 'name' => 'Inventory', 'description' => 'Inventory Department'],
            ['code' => 'RSC', 'name' => 'Risk Controll', 'description' => 'Risk Controll Department'],
            ['code' => 'ADL', 'name' => 'Admin Loket', 'description' => 'Admin Loket Department'],
            ['code' => 'HRD', 'name' => 'HRD', 'description' => 'HRD Department'],
            ['code' => 'FAT', 'name' => 'FAT', 'description' => 'FAT Department'],
            ['code' => 'SEC', 'name' => 'Security', 'description' => 'Security Department'],
        ];

        foreach ($departments as $department) {
            DB::table('departments')->updateOrInsert(
                ['code' => $department['code']],
                [
                    'name' => $department['name'],
                    'description' => $department['description'],
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        DB::table('departments')
            ->whereNotIn('code', array_column($departments, 'code'))
            ->update([
                'is_active' => false,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        // no-op rollback for data sync migration
    }
};
