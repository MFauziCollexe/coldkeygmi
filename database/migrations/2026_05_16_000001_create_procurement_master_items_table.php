<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurement_master_items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 100)->unique();
            $table->string('item_name', 255);
            $table->text('description_of_goods');
            $table->text('specification')->nullable();
            $table->string('unit', 100);
            $table->decimal('default_price', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_master_items');
    }
};
