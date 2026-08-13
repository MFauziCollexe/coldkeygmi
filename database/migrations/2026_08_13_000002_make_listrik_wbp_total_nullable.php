<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listriks', function (Blueprint $table) {
            $table->decimal('wbp', 14, 2)->nullable()->change();
            $table->decimal('total', 14, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('listriks', function (Blueprint $table) {
            $table->decimal('wbp', 14, 2)->nullable(false)->change();
            $table->decimal('total', 14, 2)->nullable(false)->change();
        });
    }
};
