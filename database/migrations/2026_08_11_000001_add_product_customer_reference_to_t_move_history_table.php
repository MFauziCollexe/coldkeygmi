<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_move_history', function (Blueprint $table) {
            $table->string('product_customer_reference')->nullable()->after('created_on');
        });
    }

    public function down(): void
    {
        Schema::table('t_move_history', function (Blueprint $table) {
            $table->dropColumn('product_customer_reference');
        });
    }
};
