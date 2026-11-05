<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_log_corrections', function (Blueprint $table) {
            $table->uuid('request_group_id')->nullable()->after('id')->index();
            $table->string('approval_stage')->nullable()->after('status');
            $table->foreignId('spv_reviewed_by')->nullable()->after('approval_stage')->constrained('users')->nullOnDelete();
            $table->timestamp('spv_reviewed_at')->nullable()->after('spv_reviewed_by');
            $table->text('spv_review_notes')->nullable()->after('spv_reviewed_at');
            $table->foreignId('manager_reviewed_by')->nullable()->after('spv_review_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('manager_reviewed_at')->nullable()->after('manager_reviewed_by');
            $table->text('manager_review_notes')->nullable()->after('manager_reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_log_corrections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('spv_reviewed_by');
            $table->dropColumn(['spv_reviewed_at', 'spv_review_notes']);
            $table->dropConstrainedForeignId('manager_reviewed_by');
            $table->dropColumn([
                'request_group_id',
                'approval_stage',
                'manager_reviewed_at',
                'manager_review_notes',
            ]);
        });
    }
};
