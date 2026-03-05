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
        Schema::create('fingerprints', function (Blueprint $table) {
            $table->id();
            $table->dateTime('scan_date'); // Tanggal dan jam scan
            $table->date('scan_date_only')->nullable(); // Tanggal saja
            $table->time('scan_time')->nullable(); // Jam saja
            $table->string('pin', 50)->nullable(); // PIN dari mesin fingerprint
            $table->string('nip', 50)->nullable(); // NIP karyawan
            $table->string('name', 255)->nullable(); // Nama karyawan
            $table->string('position', 255)->nullable(); // Jabatan
            $table->string('department', 255)->nullable(); // Departemen
            $table->string('office', 255)->nullable(); // Kantor
            $table->integer('verify')->default(0); // Verifikasi
            $table->integer('io')->default(0); // I/O
            $table->integer('workcode')->default(0); // Workcode
            $table->string('sn', 100)->nullable(); // Serial Number mesin
            $table->string('machine', 100)->nullable(); // Nama mesin
            $table->timestamp('created_at')->useCurrent();
            
            // Index untuk optimasi pencarian duplikasi
            $table->index(['scan_date', 'pin'], 'idx_scan_date_pin');
            $table->index('scan_date_only');
            $table->index('pin');
            $table->index('nip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fingerprints');
    }
};
