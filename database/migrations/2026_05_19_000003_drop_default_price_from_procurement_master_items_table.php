<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_master_items', function (Blueprint $table) {
            if (Schema::hasColumn('procurement_master_items', 'default_price')) {
                $table->dropColumn('default_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('procurement_master_items', function (Blueprint $table) {
            if (!Schema::hasColumn('procurement_master_items', 'default_price')) {
                $table->decimal('default_price', 15, 2)->nullable()->after('unit');
            }
        });
    }
};