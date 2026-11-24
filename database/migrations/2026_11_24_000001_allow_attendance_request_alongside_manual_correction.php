<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_log_corrections', function (Blueprint $table) {
            $table->string('correction_type', 20)->default('manual')->after('pin');
        });

        DB::table('attendance_log_corrections')
            ->whereNotNull('request_group_id')
            ->update(['correction_type' => 'request']);

        Schema::table('attendance_log_corrections', function (Blueprint $table) {
            $table->dropUnique('attendance_log_corrections_log_date_pin_unique');
            $table->unique(
                ['log_date', 'pin', 'correction_type'],
                'attendance_log_corrections_date_pin_type_unique'
            );
        });
    }

    public function down(): void
    {
        $hasOverlappingCorrections = DB::table('attendance_log_corrections')
            ->select('log_date', 'pin')
            ->groupBy('log_date', 'pin')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasOverlappingCorrections) {
            throw new RuntimeException(
                'Cannot roll back attendance correction types while manual corrections and requests coexist for the same date and PIN.'
            );
        }

        Schema::table('attendance_log_corrections', function (Blueprint $table) {
            $table->dropUnique('attendance_log_corrections_date_pin_type_unique');
            $table->unique(['log_date', 'pin']);
            $table->dropColumn('correction_type');
        });
    }
};
