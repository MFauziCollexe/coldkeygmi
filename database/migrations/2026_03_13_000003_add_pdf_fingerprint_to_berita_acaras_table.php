<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('berita_acaras', function (Blueprint $table) {
            if (!Schema::hasColumn('berita_acaras', 'pdf_template_fingerprint')) {
                $table->string('pdf_template_fingerprint', 64)->nullable()->after('pdf_path');
                $table->index('pdf_template_fingerprint');
            }
            if (!Schema::hasColumn('berita_acaras', 'pdf_generated_at')) {
                $table->dateTime('pdf_generated_at')->nullable()->after('pdf_template_fingerprint');
                $table->index('pdf_generated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('berita_acaras', function (Blueprint $table) {
            if (Schema::hasColumn('berita_acaras', 'pdf_generated_at')) {
                $table->dropIndex(['pdf_generated_at']);
                $table->dropColumn('pdf_generated_at');
            }
            if (Schema::hasColumn('berita_acaras', 'pdf_template_fingerprint')) {
                $table->dropIndex(['pdf_template_fingerprint']);
                $table->dropColumn('pdf_template_fingerprint');
            }
        });
    }
};

