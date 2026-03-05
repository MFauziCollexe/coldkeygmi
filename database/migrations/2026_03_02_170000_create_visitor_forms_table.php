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
        Schema::create('visitor_forms', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_name', 150);
            $table->string('company', 150)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('identity_no', 100)->nullable();
            $table->string('purpose', 255);
            $table->string('host_name', 150)->nullable();
            $table->date('visit_date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->string('status', 50)->default('Waiting');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['visit_date', 'status']);
            $table->index('visitor_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitor_forms');
    }
};

