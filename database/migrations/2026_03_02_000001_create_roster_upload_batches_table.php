<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roster_upload_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->string('filename');
            $table->char('delimiter', 1);
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('saved_rows')->default(0);
            $table->timestamps();

            $table->index(['year', 'month']);
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roster_upload_batches');
    }
};
