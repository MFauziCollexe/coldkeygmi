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
        Schema::table('visitor_forms', function (Blueprint $table) {
            if (!Schema::hasColumn('visitor_forms', 'visit_time')) {
                $table->time('visit_time')->nullable()->after('visit_date');
            }
            if (!Schema::hasColumn('visitor_forms', 'appointment_time')) {
                $table->time('appointment_time')->nullable()->after('purpose');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitor_forms', function (Blueprint $table) {
            if (Schema::hasColumn('visitor_forms', 'visit_time')) {
                $table->dropColumn('visit_time');
            }
            if (Schema::hasColumn('visitor_forms', 'appointment_time')) {
                $table->dropColumn('appointment_time');
            }
        });
    }
};

