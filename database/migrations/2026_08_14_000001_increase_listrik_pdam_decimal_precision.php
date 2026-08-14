<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listriks', function (Blueprint $table) {
            $table->decimal('lbp', 14, 4)->change();
            $table->decimal('wbp', 14, 4)->nullable()->change();
            $table->decimal('total', 14, 4)->nullable()->change();
            $table->decimal('kvarh', 14, 4)->nullable()->change();
        });

        Schema::table('pdams', function (Blueprint $table) {
            $table->decimal('meter_1', 14, 4)->nullable()->change();
            $table->decimal('meter_2', 14, 4)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('listriks', function (Blueprint $table) {
            $table->decimal('lbp', 14, 2)->change();
            $table->decimal('wbp', 14, 2)->nullable(false)->change();
            $table->decimal('total', 14, 2)->nullable(false)->change();
            $table->decimal('kvarh', 14, 4)->nullable()->change();
        });

        Schema::table('pdams', function (Blueprint $table) {
            $table->decimal('meter_1', 14, 2)->nullable()->change();
            $table->decimal('meter_2', 14, 2)->nullable()->change();
        });
    }
};
