<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Setiap kelompok inventaris punya batch sendiri sehingga tidak lagi saling
     * merebut "effective batch" pada periode yang sama. Batch lama dipindah
     * berdasarkan uploader supaya historis tetap dapat diakses.
     */
    private const SAID_UPLOADER_ID = 16;
    private const IMANDA_UPLOADER_ID = 22;
    private const INVENTORY_DEPARTMENT_ID = 13;

    public function up(): void
    {
        $now = now();

        DB::table('departments')->updateOrInsert(
            ['code' => 'INV_SAID'],
            [
                'name' => 'Inventory SAID',
                'description' => 'Roster kelompok SAID',
                'is_active' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        DB::table('departments')->updateOrInsert(
            ['code' => 'INV_IMANDA'],
            [
                'name' => 'Inventory Imanda',
                'description' => 'Roster kelompok IMANDA',
                'is_active' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $saidDepartmentId = (int) DB::table('departments')->where('code', 'INV_SAID')->value('id');
        $imandaDepartmentId = (int) DB::table('departments')->where('code', 'INV_IMANDA')->value('id');

        DB::table('roster_upload_batches')
            ->where('department_id', self::INVENTORY_DEPARTMENT_ID)
            ->where('uploaded_by', self::IMANDA_UPLOADER_ID)
            ->update(['department_id' => $imandaDepartmentId]);

        DB::table('roster_upload_batches')
            ->where('department_id', self::INVENTORY_DEPARTMENT_ID)
            ->where('uploaded_by', '!=', self::IMANDA_UPLOADER_ID)
            ->update(['department_id' => $saidDepartmentId]);
    }

    public function down(): void
    {
        DB::table('roster_upload_batches')
            ->whereIn('department_id', function ($query) {
                $query->select('id')
                    ->from('departments')
                    ->whereIn('code', ['INV_SAID', 'INV_IMANDA']);
            })
            ->update(['department_id' => self::INVENTORY_DEPARTMENT_ID]);

        DB::table('departments')
            ->whereIn('code', ['INV_SAID', 'INV_IMANDA'])
            ->delete();
    }
};
