<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_presences', function (Blueprint $table) {
            $table->text('check_out_reason')->nullable()->after('check_out_area_name');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_presences', function (Blueprint $table) {
            $table->dropColumn('check_out_reason');
        });
    }
};
