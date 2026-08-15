<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('tallies', 't_po');
    }

    public function down(): void
    {
        Schema::rename('t_po', 'tallies');
    }
};
