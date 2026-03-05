<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attendance_holidays', function (Blueprint $table) {
            $table->string('scope_type', 20)->default('all')->after('name');
            $table->dropUnique('attendance_holidays_holiday_date_unique');
            $table->unique(['holiday_date', 'scope_type'], 'attendance_holidays_date_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_holidays', function (Blueprint $table) {
            $table->dropUnique('attendance_holidays_date_scope_unique');
            $table->dropColumn('scope_type');
            $table->unique('holiday_date');
        });
    }
};

