<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_card_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_card_item_id')->constrained('stock_card_items')->cascadeOnDelete();
            $table->date('request_date');
            $table->decimal('quantity', 15, 2);
            $table->string('requested_by_name');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_card_requests');
    }
};
