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
        Schema::table('purchase_requisition_attachments', function (Blueprint $table) {
            $table->string('original_path')->nullable()->after('path');
            $table->string('signed_path')->nullable()->after('original_path');
            $table->string('signature_status')->default('pending')->after('signed_path');
            $table->unsignedBigInteger('signed_by')->nullable()->after('signature_status');
            $table->timestamp('signed_at')->nullable()->after('signed_by');
            $table->json('signature_meta')->nullable()->after('signed_at');

            $table->foreign('signed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requisition_attachments', function (Blueprint $table) {
            $table->dropForeign(['signed_by']);
            $table->dropColumn([
                'original_path',
                'signed_path',
                'signature_status',
                'signed_by',
                'signed_at',
                'signature_meta',
            ]);
        });
    }
};
