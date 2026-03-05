<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('customer_type', ['individual', 'company'])->default('company')->after('id');
            $table->string('logo_image')->nullable()->after('name');
            $table->string('address_line_1')->nullable()->after('code');
            $table->string('address_line_2')->nullable()->after('address_line_1');
            $table->string('city')->nullable()->after('address_line_2');
            $table->string('state')->nullable()->after('city');
            $table->string('country')->nullable()->after('state');
            $table->string('zip')->nullable()->after('country');
            $table->string('phone')->nullable()->after('zip');
            $table->string('mobile')->nullable()->after('phone');
            $table->string('email')->nullable()->after('mobile');
            $table->string('website')->nullable()->after('email');
            $table->string('npwp')->nullable()->after('website');
            $table->boolean('is_pkp')->default(false)->after('npwp');
            $table->string('invoice_transaction_code')->nullable()->after('is_pkp');
            $table->string('tags')->nullable()->after('invoice_transaction_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'customer_type',
                'logo_image',
                'address_line_1',
                'address_line_2',
                'city',
                'state',
                'country',
                'zip',
                'phone',
                'mobile',
                'email',
                'website',
                'npwp',
                'is_pkp',
                'invoice_transaction_code',
                'tags',
            ]);
        });
    }
};

