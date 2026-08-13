<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdams', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->time('jam_1')->nullable();
            $table->decimal('meter_1', 14, 2)->nullable();
            $table->time('jam_2')->nullable();
            $table->decimal('meter_2', 14, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pdams');
    }
};
