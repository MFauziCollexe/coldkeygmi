<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_requisitions', 'po_comment')) {
                $table->text('po_comment')->nullable()->after('note');
            }

            if (!Schema::hasColumn('purchase_requisitions', 'po_photo_path')) {
                $table->string('po_photo_path')->nullable()->after('po_comment');
            }

            if (!Schema::hasColumn('purchase_requisitions', 'po_photo_filename')) {
                $table->string('po_photo_filename')->nullable()->after('po_photo_path');
            }

            if (!Schema::hasColumn('purchase_requisitions', 'po_processed_by')) {
                $table->foreignId('po_processed_by')
                    ->nullable()
                    ->after('po_photo_filename')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('purchase_requisitions', 'po_processed_at')) {
                $table->timestamp('po_processed_at')->nullable()->after('po_processed_by');
            }

            if (!Schema::hasColumn('purchase_requisitions', 'po_done_by')) {
                $table->foreignId('po_done_by')
                    ->nullable()
                    ->after('po_processed_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('purchase_requisitions', 'po_done_at')) {
                $table->timestamp('po_done_at')->nullable()->after('po_done_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_requisitions', 'po_done_by')) {
                $table->dropConstrainedForeignId('po_done_by');
            }

            if (Schema::hasColumn('purchase_requisitions', 'po_done_at')) {
                $table->dropColumn('po_done_at');
            }

            if (Schema::hasColumn('purchase_requisitions', 'po_processed_by')) {
                $table->dropConstrainedForeignId('po_processed_by');
            }

            if (Schema::hasColumn('purchase_requisitions', 'po_processed_at')) {
                $table->dropColumn('po_processed_at');
            }

            if (Schema::hasColumn('purchase_requisitions', 'po_photo_filename')) {
                $table->dropColumn('po_photo_filename');
            }

            if (Schema::hasColumn('purchase_requisitions', 'po_photo_path')) {
                $table->dropColumn('po_photo_path');
            }

            if (Schema::hasColumn('purchase_requisitions', 'po_comment')) {
                $table->dropColumn('po_comment');
            }
        });
    }
};
