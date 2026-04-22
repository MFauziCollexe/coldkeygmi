<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compress_pdf_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('compressed_filename')->nullable();
            $table->string('original_path');
            $table->string('compressed_path')->nullable();
            $table->unsignedBigInteger('original_size')->default(0);
            $table->unsignedBigInteger('compressed_size')->nullable();
            $table->decimal('compression_ratio', 5, 2)->nullable()->default(0);
            $table->enum('compression_level', ['low', 'medium', 'high'])->default('medium');
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
        Schema::dropIfExists('compress_pdf_jobs');
    }
};
