<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('face_reference_photo_path')->nullable()->after('education');
            $table->longText('face_reference_descriptor')->nullable()->after('face_reference_photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['face_reference_photo_path', 'face_reference_descriptor']);
        });
    }
};
