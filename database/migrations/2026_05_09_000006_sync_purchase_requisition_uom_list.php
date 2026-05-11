<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = Carbon::now();
        $units = [
            'Carton',
            'Bag',
            'Ball',
            'Basket',
            'Batang',
            'Bin',
            'Blok',
            'Bungkus',
            'Box',
            'Dus',
            'Ea',
            'Fishbox',
            'Jrigen',
            'Keranjang',
            'Lembar',
            'Pack',
            'Pcs',
            'Pouch',
            'Sachet',
            'Sack',
            'Set',
            'Styrofoam',
            'Outer',
            'Karung',
        ];

        foreach ($units as $name) {
            DB::table('stock_card_units')->updateOrInsert(
                ['name' => $name],
                [
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        DB::table('stock_card_units')
            ->whereNotIn('name', $units)
            ->update([
                'is_active' => false,
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        DB::table('stock_card_units')
            ->whereIn('name', [
                'Carton',
                'Bag',
                'Ball',
                'Basket',
                'Batang',
                'Bin',
                'Blok',
                'Bungkus',
                'Box',
                'Dus',
                'Ea',
                'Fishbox',
                'Jrigen',
                'Keranjang',
                'Lembar',
                'Pack',
                'Pcs',
                'Pouch',
                'Sachet',
                'Sack',
                'Set',
                'Styrofoam',
                'Outer',
                'Karung',
            ])
            ->update([
                'is_active' => true,
                'updated_at' => Carbon::now(),
            ]);
    }
};
