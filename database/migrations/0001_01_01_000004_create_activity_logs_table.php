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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->string('table_name');
            $table->unsignedBigInteger('record_id');
            $table->enum('action', ['insert', 'update', 'delete']);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('user_id')->default('system');
            $table->string('user_email')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->text('description')->nullable();
            
            $table->index('table_name');
            $table->index('record_id');
            $table->index('action');
            $table->index('created_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
