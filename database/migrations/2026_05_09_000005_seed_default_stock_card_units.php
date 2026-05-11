<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = Carbon::now();

        foreach ([
            'Box',
            'Gram',
            'Kg',
            'Liter',
            'Meter',
            'Pack',
            'Pcs',
            'Roll',
            'Set',
            'Unit',
        ] as $name) {
            DB::table('stock_card_units')->updateOrInsert(
                ['name' => $name],
                [
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('stock_card_units')
            ->whereIn('name', ['Box', 'Gram', 'Kg', 'Liter', 'Meter', 'Pack', 'Pcs', 'Roll', 'Set', 'Unit'])
            ->delete();
    }
};
