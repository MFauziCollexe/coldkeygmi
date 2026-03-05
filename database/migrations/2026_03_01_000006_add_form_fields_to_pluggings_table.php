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
        Schema::table('pluggings', function (Blueprint $table) {
            $table->string('transporter')->nullable()->after('status');
            $table->string('nomor_dokumen')->nullable()->after('transporter');
            $table->string('rencana_waktu_kedatangan')->nullable()->after('nomor_dokumen');
            $table->unsignedInteger('jumlah_kendaraan')->nullable()->after('rencana_waktu_kedatangan');
            $table->string('jenis_kendaraan')->nullable()->after('jumlah_kendaraan');
            $table->string('pintu_loading')->nullable()->after('jenis_kendaraan');
            $table->text('keterangan')->nullable()->after('pintu_loading');
            $table->string('pic_customer')->nullable()->after('keterangan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pluggings', function (Blueprint $table) {
            $table->dropColumn([
                'transporter',
                'nomor_dokumen',
                'rencana_waktu_kedatangan',
                'jumlah_kendaraan',
                'jenis_kendaraan',
                'pintu_loading',
                'keterangan',
                'pic_customer',
            ]);
        });
    }
};

