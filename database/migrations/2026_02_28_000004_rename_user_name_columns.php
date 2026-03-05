<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new 'name' column
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
        });

        // Copy data from first_name and last_name to name
        DB::statement('UPDATE users SET name = CONCAT(first_name, " ", last_name) WHERE first_name IS NOT NULL AND last_name IS NOT NULL');

        // Handle cases where only one name exists
        DB::statement('UPDATE users SET name = first_name WHERE name IS NULL AND first_name IS NOT NULL');
        DB::statement('UPDATE users SET name = last_name WHERE name IS NULL AND last_name IS NOT NULL');

        // Drop old columns
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('first_name');
            $table->dropColumn('last_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back old columns
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
        });

        // This is a best-effort restore - won't perfectly restore original data
        // Split name by space (assuming first name is first word)
        DB::statement('UPDATE users SET first_name = SUBSTRING_INDEX(name, " ", 1), last_name = TRIM(SUBSTRING(name, LENGTH(SUBSTRING_INDEX(name, " ", 1)) + 1)) WHERE name IS NOT NULL');

        // Drop the new column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
