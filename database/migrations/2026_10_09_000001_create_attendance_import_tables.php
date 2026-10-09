<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->integer('total_rows')->default(0);
            $table->integer('valid_rows')->default(0);
            $table->integer('saved_rows')->default(0);
            $table->timestamps();

            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['year', 'month']);
        });

        Schema::create('attendance_import_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('batch_id');
            $table->date('attendance_date');
            $table->string('pin', 64);
            $table->string('name', 255);
            $table->string('check_in', 5)->nullable();
            $table->string('check_out', 5)->nullable();
            $table->string('status', 64)->default('');
            $table->boolean('is_off')->default(false);
            $table->string('leave_type', 32)->nullable();
            $table->timestamps();

            $table->foreign('batch_id')->references('id')->on('attendance_import_batches')->cascadeOnDelete();
            $table->unique(['pin', 'attendance_date']);
            $table->index(['attendance_date', 'pin']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_import_entries');
        Schema::dropIfExists('attendance_import_batches');
    }
};