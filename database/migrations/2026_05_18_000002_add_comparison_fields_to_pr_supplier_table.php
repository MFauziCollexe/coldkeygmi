<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pr_supplier', function (Blueprint $table) {
            $table->string('lead_time')->nullable()->after('supplier_id');
            $table->string('payment_terms')->nullable()->after('lead_time');
            $table->boolean('is_recommended')->default(false)->after('payment_terms');
        });
    }

    public function down(): void
    {
        Schema::table('pr_supplier', function (Blueprint $table) {
            $table->dropColumn(['lead_time', 'payment_terms', 'is_recommended']);
        });
    }
};
