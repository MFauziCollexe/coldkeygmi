<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_permissions', 'attachment_images')) {
                $table->longText('attachment_images')->nullable()->after('attachment_image');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_permissions', function (Blueprint $table) {
            if (Schema::hasColumn('leave_permissions', 'attachment_images')) {
                $table->dropColumn('attachment_images');
            }
        });
    }
};
