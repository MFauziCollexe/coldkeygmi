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
            $columnsToDrop = [];

            if (Schema::hasColumn('visitor_forms', 'company')) {
                $columnsToDrop[] = 'company';
            }

            if (Schema::hasColumn('visitor_forms', 'phone')) {
                $columnsToDrop[] = 'phone';
            }

            if (Schema::hasColumn('visitor_forms', 'notes')) {
                $columnsToDrop[] = 'notes';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitor_forms', function (Blueprint $table) {
            if (!Schema::hasColumn('visitor_forms', 'company')) {
                $table->string('company', 150)->nullable()->after('visitor_name');
            }

            if (!Schema::hasColumn('visitor_forms', 'phone')) {
                $table->string('phone', 50)->nullable()->after('company');
            }

            if (!Schema::hasColumn('visitor_forms', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
        });
    }
};

