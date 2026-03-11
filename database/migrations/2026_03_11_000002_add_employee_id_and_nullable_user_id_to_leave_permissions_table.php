<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('leave_permissions')) {
            return;
        }

        // Make `user_id` nullable + set-null FK (raw SQL to avoid doctrine/dbal dependency).
        // Original FK name follows Laravel convention: `leave_permissions_user_id_foreign`.
        try {
            DB::statement('ALTER TABLE `leave_permissions` DROP FOREIGN KEY `leave_permissions_user_id_foreign`');
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            DB::statement('ALTER TABLE `leave_permissions` MODIFY `user_id` BIGINT UNSIGNED NULL');
        } catch (\Throwable $e) {
            // ignore (column may already be nullable or different type)
        }

        try {
            DB::statement(
                'ALTER TABLE `leave_permissions` ' .
                'ADD CONSTRAINT `leave_permissions_user_id_foreign` ' .
                'FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL'
            );
        } catch (\Throwable $e) {
            // ignore (constraint may already exist)
        }

        Schema::table('leave_permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_permissions', 'employee_id')) {
                $table->foreignId('employee_id')
                    ->nullable()
                    ->constrained('employees')
                    ->nullOnDelete()
                    ->after('user_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('leave_permissions')) {
            return;
        }

        Schema::table('leave_permissions', function (Blueprint $table) {
            if (Schema::hasColumn('leave_permissions', 'employee_id')) {
                try {
                    $table->dropForeign(['employee_id']);
                } catch (\Throwable $e) {
                    // ignore
                }
                $table->dropColumn('employee_id');
            }
        });

        // Restore `user_id` FK as NOT NULL with cascade delete (raw SQL).
        try {
            DB::statement('ALTER TABLE `leave_permissions` DROP FOREIGN KEY `leave_permissions_user_id_foreign`');
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            DB::statement('ALTER TABLE `leave_permissions` MODIFY `user_id` BIGINT UNSIGNED NOT NULL');
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            DB::statement(
                'ALTER TABLE `leave_permissions` ' .
                'ADD CONSTRAINT `leave_permissions_user_id_foreign` ' .
                'FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE'
            );
        } catch (\Throwable $e) {
            // ignore
        }
    }
};

