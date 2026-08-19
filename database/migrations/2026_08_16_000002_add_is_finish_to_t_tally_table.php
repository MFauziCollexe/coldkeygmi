<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('t_tally', function (Blueprint $table) {
            $table->tinyInteger('is_finish')->default(0)->after('kg');
        });
    }

    public function down(): void
    {
        Schema::table('t_tally', function (Blueprint $table) {
            $table->dropColumn('is_finish');
        });
    }
};
