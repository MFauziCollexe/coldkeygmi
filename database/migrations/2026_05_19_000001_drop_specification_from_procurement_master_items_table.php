<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_master_items', function (Blueprint $table) {
            if (Schema::hasColumn('procurement_master_items', 'specification')) {
                $table->dropColumn('specification');
            }
        });
    }

    public function down(): void
    {
        Schema::table('procurement_master_items', function (Blueprint $table) {
            if (!Schema::hasColumn('procurement_master_items', 'specification')) {
                $table->text('specification')->nullable()->after('description_of_goods');
            }
        });
    }
};
