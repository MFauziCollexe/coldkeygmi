<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('t_tally', function (Blueprint $table) {
            $table->unsignedBigInteger('user_tally')->nullable()->after('enddate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_tally', function (Blueprint $table) {
            $table->dropColumn('user_tally');
        });
    }
};
