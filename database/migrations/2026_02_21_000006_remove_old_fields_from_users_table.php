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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('department');
            $table->dropColumn('job_position');
            $table->dropColumn('work_position');
            $table->dropColumn('remember_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('department')->nullable()->after('status');
            $table->string('job_position')->nullable()->after('department');
            $table->string('work_position')->nullable()->after('job_position');
            $table->string('remember_token', 100)->nullable()->after('password');
        });
    }
};
