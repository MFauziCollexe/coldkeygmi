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
        Schema::table('tickets', function (Blueprint $table) {
            $table->date('deadline')->nullable()->after('description');
            $table->date('deadline_request')->nullable()->after('deadline');
            $table->boolean('deadline_approved')->nullable()->after('deadline_request');
            $table->dropColumn('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->enum('priority', ['Low', 'Medium', 'High', 'Urgent'])->default('Medium')->after('description');
            $table->dropColumn(['deadline', 'deadline_request', 'deadline_approved']);
        });
    }
};
