<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $departmentId = DB::table('departments')->where('code', 'OPS')->value('id');
        if (!$departmentId) {
            return;
        }

        $now = now();

        DB::table('positions')->updateOrInsert(
            ['code' => 'OPS-MGR'],
            [
                'name' => 'Manager',
                'department_id' => $departmentId,
                'description' => 'Manager',
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        DB::table('positions')->updateOrInsert(
            ['code' => 'OPS-SPV'],
            [
                'name' => 'Supervisor',
                'department_id' => $departmentId,
                'description' => 'Supervisor',
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        DB::table('positions')->updateOrInsert(
            ['code' => 'OPS-LDR'],
            [
                'name' => 'Leader',
                'department_id' => $departmentId,
                'description' => 'Leader',
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        DB::table('positions')->updateOrInsert(
            ['code' => 'OPS-STF'],
            [
                'name' => 'Staff',
                'department_id' => $departmentId,
                'description' => 'Staff',
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        DB::table('positions')
            ->where('department_id', $departmentId)
            ->whereNotIn('code', ['OPS-MGR', 'OPS-SPV', 'OPS-LDR', 'OPS-STF'])
            ->update([
                'is_active' => false,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        $departmentId = DB::table('departments')->where('code', 'OPS')->value('id');
        if (!$departmentId) {
            return;
        }

        $now = now();

        DB::table('positions')->updateOrInsert(
            ['code' => 'OPS-MGR'],
            [
                'name' => 'Operations Manager',
                'department_id' => $departmentId,
                'description' => 'Operations Manager',
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        DB::table('positions')->updateOrInsert(
            ['code' => 'OPS-STF'],
            [
                'name' => 'Operations Staff',
                'department_id' => $departmentId,
                'description' => 'Operations Staff',
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }
};
