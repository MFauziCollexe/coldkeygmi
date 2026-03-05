<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendance_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('holiday_date')->unique();
            $table->string('name', 150);
            $table->text('notes')->nullable();
            $table->boolean('is_national')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('holiday_date');
            $table->index('is_national');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_holidays');
    }
};

