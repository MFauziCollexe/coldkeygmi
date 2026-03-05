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
            if (!Schema::hasColumn('visitor_forms', 'host_user_id')) {
                $table->foreignId('host_user_id')->nullable()->after('host_name')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('visitor_forms', 'approval_status')) {
                $table->string('approval_status', 30)->default('pending')->after('status');
                $table->index('approval_status');
            }

            if (!Schema::hasColumn('visitor_forms', 'security_approved_by')) {
                $table->foreignId('security_approved_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('visitor_forms', 'security_approved_at')) {
                $table->timestamp('security_approved_at')->nullable()->after('security_approved_by');
            }

            if (!Schema::hasColumn('visitor_forms', 'host_approved_by')) {
                $table->foreignId('host_approved_by')->nullable()->after('security_approved_at')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('visitor_forms', 'host_approved_at')) {
                $table->timestamp('host_approved_at')->nullable()->after('host_approved_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitor_forms', function (Blueprint $table) {
            if (Schema::hasColumn('visitor_forms', 'host_approved_at')) {
                $table->dropColumn('host_approved_at');
            }

            if (Schema::hasColumn('visitor_forms', 'host_approved_by')) {
                $table->dropConstrainedForeignId('host_approved_by');
            }

            if (Schema::hasColumn('visitor_forms', 'security_approved_at')) {
                $table->dropColumn('security_approved_at');
            }

            if (Schema::hasColumn('visitor_forms', 'security_approved_by')) {
                $table->dropConstrainedForeignId('security_approved_by');
            }

            if (Schema::hasColumn('visitor_forms', 'approval_status')) {
                $table->dropIndex(['approval_status']);
                $table->dropColumn('approval_status');
            }

            if (Schema::hasColumn('visitor_forms', 'host_user_id')) {
                $table->dropConstrainedForeignId('host_user_id');
            }
        });
    }
};

