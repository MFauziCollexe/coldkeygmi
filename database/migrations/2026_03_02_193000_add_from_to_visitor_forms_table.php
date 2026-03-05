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
            if (!Schema::hasColumn('visitor_forms', 'from')) {
                $table->string('from', 150)->nullable()->after('visitor_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitor_forms', function (Blueprint $table) {
            if (Schema::hasColumn('visitor_forms', 'from')) {
                $table->dropColumn('from');
            }
        });
    }
};

