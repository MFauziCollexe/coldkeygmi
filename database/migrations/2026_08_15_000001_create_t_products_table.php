<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('customer')->nullable();
            $table->string('barcode')->nullable();
            $table->string('internal_reference')->nullable();
            $table->string('name')->nullable();
            $table->string('product_type')->nullable();
            $table->string('product_category')->nullable();
            $table->string('unit_of_measure')->nullable();
            $table->decimal('standard_qty_pallet', 18, 4)->default(0);
            $table->string('pack_size_cf')->nullable();
            $table->decimal('length', 18, 4)->default(0);
            $table->decimal('width', 18, 4)->default(0);
            $table->decimal('height', 18, 4)->default(0);
            $table->decimal('weight', 18, 4)->default(0);
            $table->string('layer_stack')->nullable();
            $table->decimal('volume', 18, 4)->default(0);
            $table->string('track_inventory')->nullable();
            $table->string('valuation_by_lot_serial_number')->nullable();
            $table->string('use_expiration_date')->nullable();
            $table->string('tags_name')->nullable();
            $table->string('routes')->nullable();
            $table->string('customer_id')->nullable();
            $table->string('optional_products_external_id')->nullable();
            $table->string('optional_products_id')->nullable();
            $table->string('products_external_id')->nullable();
            $table->string('products_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_products');
    }
};
