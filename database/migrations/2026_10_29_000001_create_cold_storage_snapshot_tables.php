<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cold_storage_snapshot_batches', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date')->unique();
            $table->dateTime('captured_at');
            $table->timestamps();
        });

        Schema::create('cold_storage_snapshot_storages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_batch_id')
                ->constrained('cold_storage_snapshot_batches')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('odoo_location_id');
            $table->string('name');
            $table->string('complete_name')->nullable();
            $table->unsignedInteger('capacity')->default(0);
            $table->unsignedInteger('used')->default(0);
            $table->unsignedInteger('available')->default(0);
            $table->decimal('occupancy', 5, 1)->default(0);
            $table->string('status', 16);
            $table->timestamps();

            $table->unique(['snapshot_batch_id', 'odoo_location_id'], 'cold_storage_snapshot_storage_unique');
        });

        Schema::create('cold_storage_snapshot_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_storage_id')
                ->constrained('cold_storage_snapshot_storages')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('odoo_owner_id');
            $table->string('name');
            $table->unsignedInteger('occupied_slots')->default(0);
            $table->decimal('percentage', 5, 1)->default(0);
            $table->timestamps();

            $table->unique(['snapshot_storage_id', 'odoo_owner_id'], 'cold_storage_snapshot_customer_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cold_storage_snapshot_customers');
        Schema::dropIfExists('cold_storage_snapshot_storages');
        Schema::dropIfExists('cold_storage_snapshot_batches');
    }
};
