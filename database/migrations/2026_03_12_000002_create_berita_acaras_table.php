<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('berita_acaras', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('number', 100);
            $table->date('letter_date');
            $table->date('event_date');
            $table->string('event_name', 255);
            $table->string('event_location', 255);
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('duration_hours', 6, 2)->default(0);
            $table->json('attendees')->nullable();
            $table->json('results')->nullable();
            $table->json('signers');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['letter_date']);
            $table->index(['event_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berita_acaras');
    }
};

