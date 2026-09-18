<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('module_permissions')
            ->where('module_key', 'like', 'portal%')
            ->delete();
    }

    public function down(): void
    {
        // Irreversible: removed module keys are not restored automatically.
    }
};
