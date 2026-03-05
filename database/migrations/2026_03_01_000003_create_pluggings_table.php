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
        Schema::create('pluggings', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai')->nullable();
            $table->string('customer');
            $table->string('no_container_no_polisi');
            $table->decimal('suhu_awal', 8, 2);
            $table->decimal('suhu_akhir', 8, 2)->nullable();
            $table->string('lokasi');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('status', ['active', 'selesai'])->default('active');
            $table->string('signature_image')->nullable();
            $table->unsignedInteger('durasi_menit')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tanggal');
            $table->index('customer');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pluggings');
    }
};

