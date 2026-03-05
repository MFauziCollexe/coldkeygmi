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
        Schema::create('request_accesses', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->enum('type', ['existing_user', 'new_user']); // Flow 1 = existing_user, Flow 2 = new_user
            
            // For existing user (Flow 1)
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            
            // For new user (Flow 2)
            $table->string('target_user_name')->nullable();
            $table->string('target_user_email')->nullable();
            $table->foreignId('target_department_id')->nullable()->constrained('departments');
            
            // Request details - support multiple modules
            $table->json('module_keys'); // array of module keys they want access to
            $table->string('permission_type'); // specific permission (view, create, edit, etc.) - applied to all modules
            $table->text('reason'); // reason for request
            
            // Status: pending -> approved/rejected -> processed
            $table->enum('status', ['pending', 'approved', 'rejected', 'processed'])->default('pending');
            
            // Manager review
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            
            // IT processing
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->timestamp('processed_at')->nullable();
            $table->text('processing_notes')->nullable();
            
            // Metadata
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            // Indexes
            $table->index(['status', 'type']);
            $table->index('user_id');
            $table->index('module_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_accesses');
    }
};
