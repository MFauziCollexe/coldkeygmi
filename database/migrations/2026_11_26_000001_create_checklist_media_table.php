<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_header_id')->constrained('checklist_headers')->cascadeOnDelete();
            $table->string('media_type', 40);
            $table->string('scope_key', 120);
            $table->string('path', 500);
            $table->string('url', 1000)->nullable();
            $table->string('original_name')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['checklist_header_id', 'media_type', 'scope_key'], 'chk_media_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_media');
    }
};
