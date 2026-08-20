<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_tally', function (Blueprint $table) {
            $table->dateTime('startdate')->nullable()->after('is_finish');
            $table->dateTime('enddate')->nullable()->after('startdate');
        });
    }

    public function down(): void
    {
        Schema::table('t_tally', function (Blueprint $table) {
            $table->dropColumn(['startdate', 'enddate']);
        });
    }
};
