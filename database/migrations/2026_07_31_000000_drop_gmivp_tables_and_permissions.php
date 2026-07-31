<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $removedModuleKeys = [
            'gmi_visitor_permit',
            'gmi_visitor_permit.visitor_form',
            'gmi_visitor_permit.exit_permit',
        ];

        DB::table('module_permissions')
            ->whereIn('module_key', $removedModuleKeys)
            ->delete();

        Schema::dropIfExists('visitor_form_attachments');
        Schema::dropIfExists('visitor_forms');
        Schema::dropIfExists('exit_permits');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('visitor_forms', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_name');
            $table->string('company')->nullable();
            $table->string('phone')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('visit_date');
            $table->time('visit_time')->nullable();
            $table->time('appointment_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('check_in')->nullable();
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->foreignId('host_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approval_status')->default('pending');
            $table->foreignId('security_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('security_approved_at')->nullable();
            $table->foreignId('host_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('host_approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('visitor_form_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_form_id')->constrained('visitor_forms')->cascadeOnDelete();
            $table->string('filename');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
        });

        Schema::create('exit_permits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->date('exit_date');
            $table->time('exit_time')->nullable();
            $table->time('time_back')->nullable();
            $table->string('destination');
            $table->text('reason');
            $table->string('approval_status')->default('pending');
            $table->foreignId('security_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('security_approved_at')->nullable();
            $table->foreignId('hrd_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hrd_approved_at')->nullable();
            $table->foreignId('manager_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('manager_approved_at')->nullable();
            $table->timestamps();
        });
    }
};
