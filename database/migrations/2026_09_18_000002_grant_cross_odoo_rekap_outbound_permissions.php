<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sourceKeys = [
            'gmisl.cross_odoo.stock_card',
            'gmisl_cross_odoo_stock_card',
            'gmisl.cross_odoo.test',
            'gmisl_cross_odoo_test',
        ];

        $targetKeys = [
            'gmisl.cross_odoo.rekap_outbound',
            'gmisl_cross_odoo_rekap_outbound',
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

                if (! $exists) {
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
                'gmisl.cross_odoo.rekap_outbound',
                'gmisl_cross_odoo_rekap_outbound',
            ])
            ->delete();
    }
};
