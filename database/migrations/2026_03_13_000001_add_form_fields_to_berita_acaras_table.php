<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('berita_acaras', function (Blueprint $table) {
            if (!Schema::hasColumn('berita_acaras', 'document_number')) {
                $table->string('document_number', 100)->nullable()->after('number');
            }
            if (!Schema::hasColumn('berita_acaras', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('document_number');
                $table->index('customer_id');
            }
            if (!Schema::hasColumn('berita_acaras', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('customer_id');
                $table->index('department_id');
            }
            if (!Schema::hasColumn('berita_acaras', 'vehicle_no')) {
                $table->string('vehicle_no', 50)->nullable()->after('department_id');
            }
            if (!Schema::hasColumn('berita_acaras', 'incident_time')) {
                $table->time('incident_time')->nullable()->after('vehicle_no');
            }
            if (!Schema::hasColumn('berita_acaras', 'chronology')) {
                $table->text('chronology')->nullable()->after('incident_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('berita_acaras', function (Blueprint $table) {
            if (Schema::hasColumn('berita_acaras', 'chronology')) {
                $table->dropColumn('chronology');
            }
            if (Schema::hasColumn('berita_acaras', 'incident_time')) {
                $table->dropColumn('incident_time');
            }
            if (Schema::hasColumn('berita_acaras', 'vehicle_no')) {
                $table->dropColumn('vehicle_no');
            }
            if (Schema::hasColumn('berita_acaras', 'department_id')) {
                $table->dropIndex(['department_id']);
                $table->dropColumn('department_id');
            }
            if (Schema::hasColumn('berita_acaras', 'customer_id')) {
                $table->dropIndex(['customer_id']);
                $table->dropColumn('customer_id');
            }
            if (Schema::hasColumn('berita_acaras', 'document_number')) {
                $table->dropColumn('document_number');
            }
        });
    }
};

