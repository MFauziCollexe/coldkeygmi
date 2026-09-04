<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pdams', function (Blueprint $table) {
            $table->renameColumn('foto_path', 'foto_path_1');
        });

        Schema::table('pdams', function (Blueprint $table) {
            $table->string('foto_path_2')->nullable()->after('foto_path_1');
        });
    }

    public function down(): void
    {
        Schema::table('pdams', function (Blueprint $table) {
            $table->dropColumn('foto_path_2');
        });

        Schema::table('pdams', function (Blueprint $table) {
            $table->renameColumn('foto_path_1', 'foto_path');
        });
    }
};
