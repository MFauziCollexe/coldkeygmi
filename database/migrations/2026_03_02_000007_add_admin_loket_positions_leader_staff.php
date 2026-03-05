<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $departmentId = DB::table('departments')->where('code', 'ADL')->value('id');
        if (!$departmentId) {
            return;
        }

        $now = now();

        DB::table('positions')->updateOrInsert(
            ['code' => 'ADL-LDR'],
            [
                'name' => 'Leader',
                'department_id' => $departmentId,
                'description' => 'Leader',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('positions')->updateOrInsert(
            ['code' => 'ADL-STF'],
            [
                'name' => 'Staff',
                'department_id' => $departmentId,
                'description' => 'Staff',
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('positions')
            ->where('department_id', $departmentId)
            ->whereNotIn('code', ['ADL-LDR', 'ADL-STF'])
            ->update([
                'is_active' => false,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        DB::table('positions')->whereIn('code', ['ADL-LDR', 'ADL-STF'])->delete();
    }
};
