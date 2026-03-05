<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roster_upload_batches', function (Blueprint $table) {
            $table->foreignId('revision_of_batch_id')->nullable()->after('id')->constrained('roster_upload_batches')->nullOnDelete();
            $table->unsignedInteger('version')->default(1)->after('year');
            $table->boolean('is_current')->default(false)->after('status');
            $table->text('change_reason')->nullable()->after('reject_reason');

            $table->index(['department_id', 'year', 'month', 'is_current'], 'roster_batches_dept_period_current_idx');
        });

        // Backfill old rows: mark latest approved batch per department+period as current.
        $approvedRows = DB::table('roster_upload_batches')
            ->select('id', 'department_id', 'year', 'month')
            ->where('status', 'approved')
            ->orderBy('department_id')
            ->orderBy('year')
            ->orderBy('month')
            ->orderByDesc('id')
            ->get();

        $seen = [];
        foreach ($approvedRows as $row) {
            $key = "{$row->department_id}-{$row->year}-{$row->month}";
            if (!isset($seen[$key])) {
                DB::table('roster_upload_batches')->where('id', $row->id)->update(['is_current' => true]);
                $seen[$key] = true;
            }
        }
    }

    public function down(): void
    {
        Schema::table('roster_upload_batches', function (Blueprint $table) {
            $table->dropIndex('roster_batches_dept_period_current_idx');
            $table->dropConstrainedForeignId('revision_of_batch_id');
            $table->dropColumn(['version', 'is_current', 'change_reason']);
        });
    }
};
