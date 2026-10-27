<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $unlinkedQuery = DB::table('employees')
            ->whereNull('user_id');

        if (Schema::hasColumn('employees', 'employment_status')) {
            $unlinkedQuery->where(function ($query) {
                $query->whereNull('employment_status')
                    ->orWhere('employment_status', '!=', 'resigned');
            });
        }

        $unlinkedCount = (clone $unlinkedQuery)->count();
        $unlinkedIds = $unlinkedQuery
            ->limit(10)
            ->pluck('id')
            ->all();

        $duplicateUserIds = DB::table('employees')
            ->select('user_id')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id')
            ->all();
        $duplicateUserCount = count($duplicateUserIds);
        $duplicateUserIdSamples = array_slice($duplicateUserIds, 0, 10);

        if ($unlinkedCount || $duplicateUserCount) {
            throw new RuntimeException(sprintf(
                'Cannot enforce unique user links. Found %d active employee(s) without user_id (first IDs: %s) and %d duplicate user ID group(s) (first user IDs: %s). Resolve these records and rerun migrations. Resigned employees may remain unlinked.',
                $unlinkedCount,
                $unlinkedIds ? implode(', ', $unlinkedIds) : 'none',
                $duplicateUserCount,
                $duplicateUserIdSamples ? implode(', ', $duplicateUserIdSamples) : 'none'
            ));
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->foreignId('user_id')->nullable()->change();
        });
    }
};
