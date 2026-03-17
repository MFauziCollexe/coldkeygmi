<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            if (!Schema::hasColumn('overtimes', 'employee_id')) {
                $table->foreignId('employee_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('employees')
                    ->nullOnDelete();
            }
        });

        if (!Schema::hasColumn('overtimes', 'employee_id')) {
            return;
        }

        // Backfill employee_id for existing rows using employees.user_id = overtimes.user_id.
        $employeeIdByUserId = DB::table('employees')
            ->whereNotNull('user_id')
            ->pluck('id', 'user_id')
            ->map(fn($id) => (int) $id)
            ->all();

        if (empty($employeeIdByUserId)) {
            return;
        }

        DB::table('overtimes')
            ->whereNull('employee_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($employeeIdByUserId) {
                foreach ($rows as $row) {
                    $userId = (int) ($row->user_id ?? 0);
                    $employeeId = $employeeIdByUserId[$userId] ?? null;
                    if (!$employeeId) {
                        continue;
                    }

                    DB::table('overtimes')
                        ->where('id', (int) $row->id)
                        ->update(['employee_id' => $employeeId]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('overtimes', function (Blueprint $table) {
            if (!Schema::hasColumn('overtimes', 'employee_id')) {
                return;
            }

            $table->dropConstrainedForeignId('employee_id');
        });
    }
};

