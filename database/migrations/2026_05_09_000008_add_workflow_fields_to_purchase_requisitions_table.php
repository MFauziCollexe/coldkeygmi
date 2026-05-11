<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_requisitions', 'approved_by')) {
                $table->foreignId('approved_by')
                    ->nullable()
                    ->after('status')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('purchase_requisitions', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }

            $table->index(['department_id', 'status'], 'purchase_requisitions_department_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            $table->dropIndex('purchase_requisitions_department_status_index');

            if (Schema::hasColumn('purchase_requisitions', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }

            if (Schema::hasColumn('purchase_requisitions', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
        });
    }
};
