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
        Schema::table('overtimes', function (Blueprint $table) {
            if (!Schema::hasColumn('overtimes', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('reason');
            }
            if (!Schema::hasColumn('overtimes', 'attachment_original_name')) {
                $table->string('attachment_original_name')->nullable()->after('attachment_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            if (Schema::hasColumn('overtimes', 'attachment_original_name')) {
                $table->dropColumn('attachment_original_name');
            }
            if (Schema::hasColumn('overtimes', 'attachment_path')) {
                $table->dropColumn('attachment_path');
            }
        });
    }
};

