<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('t_tally', function (Blueprint $table) {
            $table->id();
            $table->foreignId('t_po_id')->constrained('t_po')->cascadeOnDelete();
            $table->string('item');
            $table->integer('pallet');
            $table->decimal('kg', 12, 3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('t_tally');
    }
};
