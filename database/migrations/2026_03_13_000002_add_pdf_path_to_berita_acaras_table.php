<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('berita_acaras', function (Blueprint $table) {
            if (!Schema::hasColumn('berita_acaras', 'pdf_path')) {
                $table->string('pdf_path', 255)->nullable()->after('chronology');
                $table->index('pdf_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('berita_acaras', function (Blueprint $table) {
            if (Schema::hasColumn('berita_acaras', 'pdf_path')) {
                $table->dropIndex(['pdf_path']);
                $table->dropColumn('pdf_path');
            }
        });
    }
};

