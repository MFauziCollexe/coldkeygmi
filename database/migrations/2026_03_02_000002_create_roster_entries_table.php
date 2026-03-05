<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roster_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->nullable()->constrained('roster_upload_batches')->nullOnDelete();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->date('roster_date');
            $table->string('day_name', 10)->nullable();
            $table->string('employee_key', 120);
            $table->string('employee_nrp', 100)->nullable();
            $table->string('employee_name', 255);
            $table->string('shift_code', 20);
            $table->boolean('is_off')->default(false);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('work_hours', 4, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['year', 'month']);
            $table->index(['employee_nrp', 'roster_date']);
            $table->unique(['roster_date', 'employee_key'], 'roster_entries_date_employee_key_unique');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roster_entries');
    }
};
