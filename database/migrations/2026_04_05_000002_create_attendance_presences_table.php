<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_presences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('attendance_lock_area_id')->nullable()->constrained('attendance_lock_areas')->nullOnDelete();
            $table->foreignId('roster_entry_id')->nullable()->constrained('roster_entries')->nullOnDelete();
            $table->date('attendance_date');
            $table->string('shift_source', 30)->default('non_roster');
            $table->string('shift_name')->nullable();
            $table->time('shift_start_time')->nullable();
            $table->time('shift_end_time')->nullable();
            $table->boolean('is_off')->default(false);
            $table->string('holiday_name')->nullable();
            $table->dateTime('check_in_at')->nullable();
            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->decimal('check_in_accuracy', 8, 2)->nullable();
            $table->string('check_in_area_name')->nullable();
            $table->dateTime('check_out_at')->nullable();
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->decimal('check_out_accuracy', 8, 2)->nullable();
            $table->string('check_out_area_name')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'attendance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_presences');
    }
};
