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
        Schema::create('exit_permits', function (Blueprint $table) {
            $table->id();
            $table->string('permit_number', 50)->nullable()->unique();
            $table->date('request_date');
            $table->string('employee_name', 150);
            $table->string('department_name', 150)->nullable();
            $table->text('purpose');
            $table->time('time_out');
            $table->time('time_back');

            $table->string('status', 30)->default('pending');

            $table->string('security_status', 20)->default('pending');
            $table->foreignId('security_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('security_approved_at')->nullable();

            $table->string('hrd_status', 20)->default('pending');
            $table->foreignId('hrd_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hrd_approved_at')->nullable();

            $table->string('manager_status', 20)->default('pending');
            $table->foreignId('manager_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('manager_approved_at')->nullable();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();

            $table->timestamps();

            $table->index(['request_date', 'status']);
            $table->index('user_id');
            $table->index('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exit_permits');
    }
};

