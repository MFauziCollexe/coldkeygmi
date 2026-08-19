<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE t_tally MODIFY kg DECIMAL(12,2) NOT NULL DEFAULT 0");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE t_tally MODIFY kg DECIMAL(12,3) NOT NULL DEFAULT 0");
    }
};
