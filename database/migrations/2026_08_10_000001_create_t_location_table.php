<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_location', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('customer_reference')->nullable();
            $table->string('owner')->nullable();
            $table->string('location')->nullable();
            $table->string('location_parent_location')->nullable();
            $table->string('location_name')->nullable();
            $table->string('storage_category')->nullable();
            $table->string('product_display_name')->nullable();
            $table->string('product_internal_reference')->nullable();
            $table->string('product_product_name')->nullable();
            $table->string('unit_of_measure')->nullable();
            $table->decimal('product_product_standard_qty_pallet', 18, 4)->default(0);
            $table->decimal('quantity', 18, 4)->default(0);
            $table->decimal('inventoried_quantity', 18, 4)->default(0);
            $table->decimal('available_quantity', 18, 4)->default(0);
            $table->decimal('qty_in_actual_kgs', 18, 4)->default(0);
            $table->string('lot_serial_number')->nullable();
            $table->date('expiration_date')->nullable();
            $table->date('incoming_date')->nullable();
            $table->dateTime('last_updated_on')->nullable();
            $table->string('product_pack_size_cf')->nullable();
            $table->string('product_category')->nullable();
            $table->string('product_barcode')->nullable();
            $table->string('location_location_type')->nullable();
            $table->string('location_room_type')->nullable();
            $table->string('package')->nullable();
            $table->string('display_name')->nullable();
            $table->string('owner_id')->nullable();
            $table->string('product')->nullable();
            $table->string('product_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_location');
    }
};
