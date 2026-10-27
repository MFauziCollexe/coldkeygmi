<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            $table->foreignId('spv_reviewed_by')->nullable()->after('review_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('spv_reviewed_at')->nullable()->after('spv_reviewed_by');
            $table->text('spv_review_notes')->nullable()->after('spv_reviewed_at');
            $table->foreignId('manager_reviewed_by')->nullable()->after('spv_review_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('manager_reviewed_at')->nullable()->after('manager_reviewed_by');
            $table->text('manager_review_notes')->nullable()->after('manager_reviewed_at');
        });

        DB::table('overtimes')
            ->where('status', 'pending')
            ->update(['status' => 'pending_spv']);
    }

    public function down(): void
    {
        DB::table('overtimes')
            ->whereIn('status', ['pending_spv', 'pending_manager'])
            ->update(['status' => 'pending']);

        Schema::table('overtimes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('spv_reviewed_by');
            $table->dropColumn(['spv_reviewed_at', 'spv_review_notes']);
            $table->dropConstrainedForeignId('manager_reviewed_by');
            $table->dropColumn(['manager_reviewed_at', 'manager_review_notes']);
        });
    }
};
