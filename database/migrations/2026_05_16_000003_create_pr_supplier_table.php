<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pr_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requisition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            
            $table->unique(['purchase_requisition_id', 'supplier_id'], 'pr_supplier_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pr_supplier');
    }
};