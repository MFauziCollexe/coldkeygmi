<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_po', function (Blueprint $table) {
            $table->dropForeign('tallies_customer_id_foreign');
        });

        DB::statement("UPDATE t_po SET customer_id = (SELECT c.customers_id_odoo FROM customers c WHERE c.id = CAST(t_po.customer_id AS UNSIGNED)) WHERE t_po.customer_id IS NOT NULL AND t_po.customer_id != ''");

        Schema::table('t_po', function (Blueprint $table) {
            $table->string('customer_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('t_po', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->change();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
        });
    }
};
