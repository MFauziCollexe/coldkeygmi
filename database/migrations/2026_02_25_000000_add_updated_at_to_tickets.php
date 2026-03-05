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
            // Add updated_at if it doesn't exist
            if (!Schema::hasColumn('tickets', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
            
            // Add other missing fields
            if (!Schema::hasColumn('tickets', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('assigned_to');
                $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('tickets', 'deadline')) {
                $table->date('deadline')->nullable()->after('description');
            }
            
            if (!Schema::hasColumn('tickets', 'deadline_request')) {
                $table->date('deadline_request')->nullable()->after('deadline');
            }
            
            if (!Schema::hasColumn('tickets', 'deadline_approved')) {
                $table->boolean('deadline_approved')->nullable()->after('deadline_request');
            }
            
            if (!Schema::hasColumn('tickets', 'resolve_deadline')) {
                $table->date('resolve_deadline')->nullable()->after('deadline_approved');
            }
            
            if (!Schema::hasColumn('tickets', 'resolved_by')) {
                $table->unsignedBigInteger('resolved_by')->nullable()->after('resolved_at');
                $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('tickets', 'closed_by')) {
                $table->unsignedBigInteger('closed_by')->nullable()->after('closed_at');
                $table->foreign('closed_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Drop columns if needed
            if (Schema::hasColumn('tickets', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};
