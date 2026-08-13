<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listriks', function (Blueprint $table) {
            $table->string('foto_path')->nullable()->after('kvarh');
        });

        Schema::table('pdams', function (Blueprint $table) {
            $table->string('foto_path')->nullable()->after('meter_2');
        });
    }

    public function down(): void
    {
        Schema::table('listriks', function (Blueprint $table) {
            $table->dropColumn('foto_path');
        });

        Schema::table('pdams', function (Blueprint $table) {
            $table->dropColumn('foto_path');
        });
    }
};
