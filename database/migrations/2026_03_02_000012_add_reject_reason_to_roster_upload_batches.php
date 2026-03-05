<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roster_upload_batches', function (Blueprint $table) {
            $table->text('reject_reason')->nullable()->after('draft_payload_path');
        });
    }

    public function down(): void
    {
        Schema::table('roster_upload_batches', function (Blueprint $table) {
            $table->dropColumn('reject_reason');
        });
    }
};
