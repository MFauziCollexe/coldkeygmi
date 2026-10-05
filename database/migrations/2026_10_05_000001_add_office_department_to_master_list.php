<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('departments')->updateOrInsert(
            ['code' => 'OFF'],
            [
                'name' => 'Office',
                'description' => 'Office (gabungan HSE, HRD, OPS, FAT)',
                'is_active' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        DB::table('departments')
            ->where('code', 'OFF')
            ->delete();
    }
};
