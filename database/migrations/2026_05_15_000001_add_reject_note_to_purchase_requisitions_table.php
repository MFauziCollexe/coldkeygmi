<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_requisitions', 'reject_note')) {
                $table->text('reject_note')->nullable()->after('note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requisitions', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_requisitions', 'reject_note')) {
                $table->dropColumn('reject_note');
            }
        });
    }
};
