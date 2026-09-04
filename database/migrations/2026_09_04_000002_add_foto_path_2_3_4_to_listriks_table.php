<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listriks', function (Blueprint $table) {
            $table->string('foto_path_2')->nullable()->after('foto_path');
            $table->string('foto_path_3')->nullable()->after('foto_path_2');
            $table->string('foto_path_4')->nullable()->after('foto_path_3');
        });
    }

    public function down(): void
    {
        Schema::table('listriks', function (Blueprint $table) {
            $table->dropColumn(['foto_path_4', 'foto_path_3', 'foto_path_2']);
        });
    }
};
