<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requisition_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_requisition_items', 'price')) {
                $table->decimal('price', 15, 2)->nullable()->after('required_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requisition_items', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_requisition_items', 'price')) {
                $table->dropColumn('price');
            }
        });
    }
};
