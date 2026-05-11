<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $removedModuleKeys = [
            'gmisl.report_accounting',
            'gmisl.hpp',
            'gmisl.purchasing',
            'gmium',
            'gmium.plugging',
            'gmium.plugging.approval',
            'gmium.resource_monitoring',
            'gmium.resource_monitoring.electricity',
            'gmium.resource_monitoring.electricity.hv_meter',
            'gmium.resource_monitoring.electricity.standard_meter',
            'gmium.resource_monitoring.water_meter',
            'gmium.utility_report',
        ];

        DB::table('module_permissions')
            ->whereIn('module_key', $removedModuleKeys)
            ->delete();

        Schema::dropIfExists('electricity_hv_logs');
        Schema::dropIfExists('electricity_standard_logs');
        Schema::dropIfExists('water_logs');
        Schema::dropIfExists('pluggings');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
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
            $table->string('transporter')->nullable();
            $table->string('nomor_dokumen')->nullable();
            $table->dateTime('rencana_waktu_kedatangan')->nullable();
            $table->unsignedInteger('jumlah_kendaraan')->nullable();
            $table->string('pic_customer')->nullable();
            $table->string('pintu_loading')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('jenis_kendaraan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tanggal');
            $table->index('customer');
            $table->index('status');
        });

        Schema::create('electricity_standard_logs', function (Blueprint $table) {
            $table->id();
            $table->string('meter_id', 100);
            $table->date('tanggal');
            $table->time('jam');
            $table->decimal('kwh', 14, 2);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['meter_id', 'tanggal']);
            $table->index('tanggal');
        });

        Schema::create('water_logs', function (Blueprint $table) {
            $table->id();
            $table->string('meter_id', 100);
            $table->date('tanggal');
            $table->time('jam');
            $table->decimal('meter_value', 14, 2);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['meter_id', 'tanggal']);
            $table->index('tanggal');
        });

        Schema::create('electricity_hv_logs', function (Blueprint $table) {
            $table->id();
            $table->string('meter_id', 100);
            $table->date('tanggal');
            $table->time('jam');
            $table->decimal('kwh', 14, 2);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['meter_id', 'tanggal']);
            $table->index('tanggal');
        });
    }
};
