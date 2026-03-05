<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('departments')
            ->where('code', 'OPS')
            ->update([
                'name' => 'Opperational',
                'description' => 'Opperational Department',
            ]);
    }

    public function down(): void
    {
        DB::table('departments')
            ->where('code', 'OPS')
            ->update([
                'name' => 'Operations',
                'description' => 'Operations Department',
            ]);
    }
};
