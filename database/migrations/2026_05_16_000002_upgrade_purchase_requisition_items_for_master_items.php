<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requisition_items', function (Blueprint $table) {
            $table->foreignId('procurement_master_item_id')
                ->nullable()
                ->after('purchase_requisition_id')
                ->constrained('procurement_master_items')
                ->nullOnDelete();
            $table->unsignedInteger('line_no')->default(1)->after('procurement_master_item_id');
            $table->string('item_code', 100)->nullable()->after('line_no');
            $table->string('item_name', 255)->nullable()->after('item_code');
            $table->text('description_of_goods')->nullable()->after('item_name');
            $table->text('specification')->nullable()->after('description_of_goods');
            $table->string('unit', 100)->nullable()->after('specification');
            $table->decimal('quantity', 15, 2)->nullable()->after('unit');
            $table->date('required_date')->nullable()->after('quantity');
            $table->decimal('price', 15, 2)->nullable()->after('required_date');
        });

        DB::table('purchase_requisition_items')
            ->orderBy('id')
            ->get()
            ->groupBy('purchase_requisition_id')
            ->each(function ($items) {
                foreach ($items->values() as $index => $item) {
                    DB::table('purchase_requisition_items')
                        ->where('id', $item->id)
                        ->update([
                            'line_no' => $index + 1,
                            'item_name' => $item->product_name,
                            'description_of_goods' => $item->product_name,
                            'unit' => $item->uom,
                            'quantity' => $item->qty,
                            'price' => 0,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('purchase_requisition_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('procurement_master_item_id');
            $table->dropColumn([
                'line_no',
                'item_code',
                'item_name',
                'description_of_goods',
                'specification',
                'unit',
                'quantity',
                'required_date',
                'price',
            ]);
        });
    }
};
