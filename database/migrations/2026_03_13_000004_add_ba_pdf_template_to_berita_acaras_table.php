<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('berita_acaras', function (Blueprint $table) {
            if (!Schema::hasColumn('berita_acaras', 'ba_pdf_template')) {
                $table->string('ba_pdf_template', 64)->nullable()->after('pdf_path');
                $table->index('ba_pdf_template');
            }
        });
    }

    public function down(): void
    {
        Schema::table('berita_acaras', function (Blueprint $table) {
            if (Schema::hasColumn('berita_acaras', 'ba_pdf_template')) {
                $table->dropIndex(['ba_pdf_template']);
                $table->dropColumn('ba_pdf_template');
            }
        });
    }
};

