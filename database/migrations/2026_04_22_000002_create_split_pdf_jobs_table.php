<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('split_pdf_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('original_path');
            $table->json('page_ranges')->nullable();
            $table->enum('split_mode', ['all_pages', 'custom_ranges'])->default('all_pages');
            $table->string('output_filename')->nullable();
            $table->string('output_path')->nullable();
            $table->string('output_type', 20)->nullable();
            $table->unsignedInteger('output_count')->default(0);
            $table->unsignedBigInteger('output_size')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('split_pdf_jobs');
    }
};
