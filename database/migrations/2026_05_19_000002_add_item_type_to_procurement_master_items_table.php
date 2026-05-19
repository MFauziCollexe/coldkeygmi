<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_master_items', function (Blueprint $table) {
            if (!Schema::hasColumn('procurement_master_items', 'item_type')) {
                $table->string('item_type', 255)->nullable()->after('description_of_goods');
            }
        });
    }

    public function down(): void
    {
        Schema::table('procurement_master_items', function (Blueprint $table) {
            if (Schema::hasColumn('procurement_master_items', 'item_type')) {
                $table->dropColumn('item_type');
            }
        });
    }
};
