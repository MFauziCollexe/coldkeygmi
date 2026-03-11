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
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'department_id')) {
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete()->after('user_id');
            }
            if (!Schema::hasColumn('employees', 'position_id')) {
                $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete()->after('department_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'position_id')) {
                $table->dropConstrainedForeignId('position_id');
            }
            if (Schema::hasColumn('employees', 'department_id')) {
                $table->dropConstrainedForeignId('department_id');
            }
        });
    }
};

