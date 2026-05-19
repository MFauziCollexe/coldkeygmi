<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('procurement_master_items', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->after('item_type');
            $table->foreign('category_id')->references('id')->on('procurement_categories')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('procurement_master_items', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};