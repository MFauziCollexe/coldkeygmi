<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_move_history', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->date('date')->nullable();
            $table->string('operation_type')->nullable();
            $table->string('from_owner')->nullable();
            $table->string('display_name')->nullable();
            $table->string('product_internal_reference')->nullable();
            $table->string('product_name')->nullable();
            $table->string('lot_serial_number')->nullable();
            $table->date('expiration_date')->nullable();
            $table->string('source_location_storage_category')->nullable();
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->string('unit_of_measure')->nullable();
            $table->decimal('product_product_standard_qty_pallet', 18, 4)->default(0);
            $table->decimal('quantity', 18, 4)->default(0);
            $table->decimal('qty_in_kgs', 18, 4)->default(0);
            $table->decimal('qty_in_actual_kgs', 18, 4)->default(0);
            $table->string('product_category')->nullable();
            $table->string('reference')->nullable();
            $table->string('source_documents')->nullable();
            $table->string('destination_package')->nullable();
            $table->string('transfer_plate_number')->nullable();
            $table->string('status')->nullable();
            $table->string('stock_operation')->nullable();
            $table->string('so_contract')->nullable();
            $table->string('room_type')->nullable();
            $table->string('product')->nullable();
            $table->string('plat_number')->nullable();
            $table->date('expiration_date_2')->nullable();
            $table->dateTime('created_on')->nullable();
            $table->string('product_customer_reference')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_move_history');
    }
};
