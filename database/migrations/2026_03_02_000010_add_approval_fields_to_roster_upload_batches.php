<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roster_upload_batches', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('uploaded_by')->constrained('departments')->nullOnDelete();
            $table->string('status', 20)->default('pending')->after('saved_rows');
            $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->string('draft_payload_path')->nullable()->after('approved_at');

            $table->index(['department_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('roster_upload_batches', function (Blueprint $table) {
            $table->dropIndex(['department_id', 'status']);
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn(['status', 'approved_at', 'draft_payload_path']);
        });
    }
};
