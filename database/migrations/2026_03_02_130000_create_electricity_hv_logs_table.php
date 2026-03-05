<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('electricity_hv_logs', function (Blueprint $table) {
            $table->id();
            $table->string('meter_id', 100);
            $table->date('tanggal');
            $table->time('jam');
            $table->decimal('lbp', 14, 2);
            $table->decimal('wbp', 14, 2);
            $table->decimal('total', 14, 2);
            $table->decimal('kvarh', 14, 2)->default(0);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['meter_id', 'tanggal']);
            $table->index('tanggal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('electricity_hv_logs');
    }
};

