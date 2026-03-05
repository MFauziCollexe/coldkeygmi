<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $operationsDeptId = DB::table('departments')
            ->where('code', 'OPS')
            ->value('id');

        if (!$operationsDeptId) {
            $operationsDeptId = DB::table('departments')
                ->where('name', 'Operations')
                ->value('id');
        }

        if (!$operationsDeptId) {
            return;
        }

        DB::table('positions')->updateOrInsert(
            ['code' => 'OPS-ADL'],
            [
                'name' => 'Admin Loket',
                'department_id' => $operationsDeptId,
                'description' => 'Admin Loket',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('positions')->where('code', 'OPS-ADL')->delete();
    }
};
