<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_import_entries', function (Blueprint $table) {
            $table->string('shift_code', 16)->nullable()->after('name');
            $table->string('schedule_start', 5)->nullable()->after('shift_code');
            $table->string('schedule_end', 5)->nullable()->after('schedule_start');
            $table->string('overtime_label', 8)->nullable()->after('schedule_end');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_import_entries', function (Blueprint $table) {
            $table->dropColumn(['shift_code', 'schedule_start', 'schedule_end', 'overtime_label']);
        });
    }
};