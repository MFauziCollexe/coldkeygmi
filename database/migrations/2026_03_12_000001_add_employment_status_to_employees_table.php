<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'employment_status')) {
                $table->string('employment_status')->default('active')->after('name');
                $table->index('employment_status');
            }
            if (!Schema::hasColumn('employees', 'resigned_at')) {
                $table->date('resigned_at')->nullable()->after('employment_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'employment_status')) {
                $table->dropIndex(['employment_status']);
                $table->dropColumn('employment_status');
            }
            if (Schema::hasColumn('employees', 'resigned_at')) {
                $table->dropColumn('resigned_at');
            }
        });
    }
};

