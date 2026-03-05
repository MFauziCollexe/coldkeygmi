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
        // Check if module_key column exists and module_keys doesn't
        if (Schema::hasColumn('request_accesses', 'module_key') && !Schema::hasColumn('request_accesses', 'module_keys')) {
            // Rename module_key to module_keys and convert to JSON
            Schema::table('request_accesses', function (Blueprint $table) {
                // First, drop the old column
                $table->dropColumn('module_key');
                
                // Then add the new JSON column
                $table->json('module_keys')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('request_accesses', function (Blueprint $table) {
            $table->dropColumn('module_keys');
            $table->string('module_key')->nullable();
        });
    }
};
