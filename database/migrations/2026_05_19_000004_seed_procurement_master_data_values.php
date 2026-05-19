<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_card_item_types')) {
            $now = now();
            $itemTypes = [
                'Raw Material',
                'Finished Goods',
                'Trading Goods',
                'Consumable',
                'Asset',
                'Spare Part',
                'Packaging',
                'Service',
            ];

            foreach ($itemTypes as $name) {
                DB::table('stock_card_item_types')->updateOrInsert(
                    ['name' => $name],
                    ['is_active' => true, 'updated_at' => $now, 'created_at' => $now]
                );
            }
        }

        if (Schema::hasTable('procurement_categories')) {
            $now = now();
            $categories = [
                'ATK',
                'Office Equipment',
                'Electrical',
                'Tools',
                'Spare Part',
                'Packaging',
                'Cleaning Supplies',
                'Pantry',
                'Safety Equipment',
                'Raw Material',
                'Finished Goods',
            ];

            foreach ($categories as $name) {
                DB::table('procurement_categories')->updateOrInsert(
                    ['name' => $name],
                    ['is_active' => true, 'updated_at' => $now, 'created_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stock_card_item_types')) {
            DB::table('stock_card_item_types')
                ->whereIn('name', [
                    'Raw Material',
                    'Finished Goods',
                    'Trading Goods',
                    'Consumable',
                    'Asset',
                    'Spare Part',
                    'Packaging',
                    'Service',
                ])
                ->delete();
        }

        if (Schema::hasTable('procurement_categories')) {
            DB::table('procurement_categories')
                ->whereIn('name', [
                    'ATK',
                    'Office Equipment',
                    'Electrical',
                    'Tools',
                    'Spare Part',
                    'Packaging',
                    'Cleaning Supplies',
                    'Pantry',
                    'Safety Equipment',
                    'Raw Material',
                    'Finished Goods',
                ])
                ->delete();
        }
    }
};
